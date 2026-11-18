<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Modelo de Contabilidad General: Asientos de Partida Doble, Libro Diario,
 * Libro Mayor Analítico, Balance de Comprobación, Estado de Resultados y Balance General.
 * Filtrado estrictamente por empresa_id (Multi-Tenant).
 */
final class AsientoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Genera el próximo número correlativo de asiento para una empresa y período (Ej: ASI-202611-0002)
     */
    public function generarProximoNumero(int $empresaId, string $periodo): string
    {
        $prefijo = 'ASI-' . str_replace('-', '', $periodo) . '-';

        $sql = 'SELECT numero_asiento
                FROM asientos
                WHERE empresa_id = :empresa_id AND numero_asiento LIKE :prefijo
                ORDER BY id DESC
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':prefijo'    => $prefijo . '%',
        ]);

        $ultimo = $stmt->fetchColumn();
        $secuencia = 1;

        if (is_string($ultimo) && preg_match('/-(\d+)$/', $ultimo, $matches)) {
            $secuencia = (int)$matches[1] + 1;
        }

        return $prefijo . str_pad((string)$secuencia, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Lista los asientos del Libro Diario de la empresa activa con sus líneas de detalle
     */
    public function listarAsientos(int $empresaId, array $filtros = []): array
    {
        $sql = 'SELECT
                    a.id,
                    a.empresa_id,
                    a.numero_asiento,
                    a.fecha,
                    a.periodo,
                    a.concepto,
                    a.referencia,
                    a.origen,
                    a.total_debe,
                    a.total_haber,
                    a.estado,
                    a.created_at,
                    u.nombre AS usuario_nombre
                FROM asientos a
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                WHERE a.empresa_id = :empresa_id';

        $params = [':empresa_id' => $empresaId];

        if (!empty($filtros['fecha_desde'])) {
            $sql .= ' AND a.fecha >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $sql .= ' AND a.fecha <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['estado']) && in_array($filtros['estado'], ['PUBLICADO', 'ANULADO', 'BORRADOR'], true)) {
            $sql .= ' AND a.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['busqueda'])) {
            $termino = '%' . trim((string)$filtros['busqueda']) . '%';
            $sql .= ' AND (a.numero_asiento LIKE :q1 OR a.concepto LIKE :q2 OR a.referencia LIKE :q3)';
            $params[':q1'] = $termino;
            $params[':q2'] = $termino;
            $params[':q3'] = $termino;
        }

        $sql .= ' ORDER BY a.fecha DESC, a.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $asientos = $stmt->fetchAll();

        if (empty($asientos)) {
            return [];
        }

        // Cargar detalles de todos los asientos listados en una sola consulta preparada
        $ids = array_map(static fn(array $r): int => (int)$r['id'], $asientos);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sqlDet = "SELECT
                        ad.id,
                        ad.asiento_id,
                        ad.cuenta_id,
                        ad.descripcion,
                        ad.debe,
                        ad.haber,
                        pc.codigo AS cuenta_codigo,
                        pc.nombre AS cuenta_nombre,
                        pc.tipo AS cuenta_tipo
                   FROM asiento_detalles ad
                   INNER JOIN plan_cuentas pc ON pc.id = ad.cuenta_id
                   WHERE ad.empresa_id = ? AND ad.asiento_id IN ($placeholders)
                   ORDER BY ad.id ASC";

        $stmtDet = $this->db->prepare($sqlDet);
        $stmtDet->execute(array_merge([$empresaId], $ids));
        $detallesRows = $stmtDet->fetchAll();

        $detallesPorAsiento = [];
        foreach ($detallesRows as $det) {
            $detallesPorAsiento[(int)$det['asiento_id']][] = $det;
        }

        foreach ($asientos as &$asiento) {
            $asiento['detalles'] = $detallesPorAsiento[(int)$asiento['id']] ?? [];
        }
        unset($asiento);

        return $asientos;
    }

    /**
     * Obtiene un asiento contable específico con todos sus renglones de partida doble
     */
    public function obtenerAsientoConDetalles(int $asientoId, int $empresaId): ?array
    {
        $sql = 'SELECT
                    a.*,
                    u.nombre AS usuario_nombre
                FROM asientos a
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                WHERE a.id = :id AND a.empresa_id = :empresa_id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $asientoId, ':empresa_id' => $empresaId]);
        $asiento = $stmt->fetch();

        if (!$asiento) {
            return null;
        }

        $sqlDet = 'SELECT
                        ad.id,
                        ad.cuenta_id,
                        ad.descripcion,
                        ad.debe,
                        ad.haber,
                        pc.codigo AS cuenta_codigo,
                        pc.nombre AS cuenta_nombre,
                        pc.tipo AS cuenta_tipo,
                        pc.naturaleza AS cuenta_naturaleza
                   FROM asiento_detalles ad
                   INNER JOIN plan_cuentas pc ON pc.id = ad.cuenta_id
                   WHERE ad.asiento_id = :asiento_id AND ad.empresa_id = :empresa_id
                   ORDER BY ad.id ASC';

        $stmtDet = $this->db->prepare($sqlDet);
        $stmtDet->execute([':asiento_id' => $asientoId, ':empresa_id' => $empresaId]);
        $asiento['detalles'] = $stmtDet->fetchAll();

        return $asiento;
    }

    /**
     * Registra un nuevo asiento contable validando estrictamente la Partida Doble (SUM(debe) === SUM(haber))
     * dentro de una transacción PDO atómica.
     */
    public function crearAsientoPartidaDoble(int $empresaId, int $usuarioId, array $cabecera, array $renglones): array
    {
        $fecha      = trim((string)($cabecera['fecha'] ?? ''));
        $concepto   = trim((string)($cabecera['concepto'] ?? ''));
        $referencia = trim((string)($cabecera['referencia'] ?? ''));
        $origen     = (string)($cabecera['origen'] ?? 'MANUAL');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new RuntimeException('La fecha del asiento contable no tiene un formato válido (AAAA-MM-DD).');
        }

        if ($concepto === '' || mb_strlen($concepto) < 4) {
            throw new RuntimeException('La glosa o concepto del asiento es obligatorio (mínimo 4 caracteres).');
        }

        if (count($renglones) < 2) {
            throw new RuntimeException('Todo asiento de partida doble requiere al menos dos (2) líneas de detalle.');
        }

        $periodo = substr($fecha, 0, 7);
        $totalDebe  = 0.0;
        $totalHaber = 0.0;
        $lineasLimpias = [];

        $stmtVerificarCuenta = $this->db->prepare(
            'SELECT id, codigo, nombre, acepta_movimiento, activo
             FROM plan_cuentas
             WHERE id = :id AND empresa_id = :empresa_id
             LIMIT 1'
        );

        foreach ($renglones as $idx => $linea) {
            $numFila  = $idx + 1;
            $cuentaId = (int)($linea['cuenta_id'] ?? 0);
            $desc     = trim((string)($linea['descripcion'] ?? ''));
            $debe     = round((float)($linea['debe'] ?? 0), 2);
            $haber    = round((float)($linea['haber'] ?? 0), 2);

            if ($cuentaId <= 0) {
                throw new RuntimeException("Línea #{$numFila}: Debe seleccionar una cuenta contable válida.");
            }

            if ($debe < 0 || $haber < 0) {
                throw new RuntimeException("Línea #{$numFila}: No se permiten montos negativos en Debe o Haber.");
            }

            if ($debe == 0.0 && $haber == 0.0) {
                throw new RuntimeException("Línea #{$numFila}: El monto en Debe o Haber debe ser mayor a cero (0,00).");
            }

            if ($debe > 0.0 && $haber > 0.0) {
                throw new RuntimeException("Línea #{$numFila}: Una misma línea no puede tener saldo simultáneo en Debe y Haber.");
            }

            $stmtVerificarCuenta->execute([
                ':id'         => $cuentaId,
                ':empresa_id' => $empresaId,
            ]);
            $cuentaRow = $stmtVerificarCuenta->fetch();

            if (!$cuentaRow) {
                throw new RuntimeException("Línea #{$numFila}: La cuenta seleccionada no pertenece a la empresa activa.");
            }

            if ((int)$cuentaRow['activo'] !== 1) {
                throw new RuntimeException("Línea #{$numFila}: La cuenta {$cuentaRow['codigo']} se encuentra inactiva.");
            }

            if ((int)$cuentaRow['acepta_movimiento'] !== 1) {
                throw new RuntimeException("Línea #{$numFila}: La cuenta {$cuentaRow['codigo']} ({$cuentaRow['nombre']}) es de grupo y no acepta movimientos directos.");
            }

            $totalDebe  = round($totalDebe + $debe, 2);
            $totalHaber = round($totalHaber + $haber, 2);

            $lineasLimpias[] = [
                'cuenta_id'   => $cuentaId,
                'descripcion' => $desc !== '' ? $desc : $concepto,
                'debe'        => $debe,
                'haber'       => $haber,
            ];
        }

        if ($totalDebe <= 0.0 || $totalHaber <= 0.0) {
            throw new RuntimeException('El total del asiento contable debe ser mayor a Bs. 0,00.');
        }

        $diferencia = round(abs($totalDebe - $totalHaber), 2);
        if ($diferencia > 0.001) {
            throw new RuntimeException(
                sprintf(
                    'Violación de Partida Doble: Total Debe (Bs. %s) no coincide con Total Haber (Bs. %s). Diferencia: Bs. %s.',
                    number_format($totalDebe, 2, ',', '.'),
                    number_format($totalHaber, 2, ',', '.'),
                    number_format($diferencia, 2, ',', '.')
                )
            );
        }

        $inTransaction = $this->db->inTransaction();
        if (!$inTransaction) {
            Database::beginTransaction();
        }

        try {
            $numeroAsiento = $this->generarProximoNumero($empresaId, $periodo);

            $sqlCabecera = 'INSERT INTO asientos
                            (empresa_id, numero_asiento, fecha, periodo, concepto, referencia, origen, total_debe, total_haber, estado, usuario_id)
                            VALUES
                            (:empresa_id, :numero_asiento, :fecha, :periodo, :concepto, :referencia, :origen, :total_debe, :total_haber, "PUBLICADO", :usuario_id)';

            $stmtCab = $this->db->prepare($sqlCabecera);
            $stmtCab->execute([
                ':empresa_id'     => $empresaId,
                ':numero_asiento' => $numeroAsiento,
                ':fecha'          => $fecha,
                ':periodo'        => $periodo,
                ':concepto'       => $concepto,
                ':referencia'     => $referencia !== '' ? $referencia : null,
                ':origen'         => in_array($origen, ['MANUAL', 'COMPRAS', 'VENTAS', 'NOMINA', 'CIERRE'], true) ? $origen : 'MANUAL',
                ':total_debe'     => $totalDebe,
                ':total_haber'    => $totalHaber,
                ':usuario_id'     => $usuarioId > 0 ? $usuarioId : null,
            ]);

            $asientoId = (int)$this->db->lastInsertId();

            $sqlDetalle = 'INSERT INTO asiento_detalles
                           (asiento_id, empresa_id, cuenta_id, descripcion, debe, haber)
                           VALUES
                           (:asiento_id, :empresa_id, :cuenta_id, :descripcion, :debe, :haber)';

            $stmtDet = $this->db->prepare($sqlDetalle);

            foreach ($lineasLimpias as $renglon) {
                $stmtDet->execute([
                    ':asiento_id'  => $asientoId,
                    ':empresa_id'  => $empresaId,
                    ':cuenta_id'   => $renglon['cuenta_id'],
                    ':descripcion' => $renglon['descripcion'],
                    ':debe'        => $renglon['debe'],
                    ':haber'       => $renglon['haber'],
                ]);
            }

            if (!$inTransaction) {
                Database::commit();
            }

            return [
                'id'             => $asientoId,
                'numero_asiento' => $numeroAsiento,
                'total_debe'     => $totalDebe,
                'total_haber'    => $totalHaber,
            ];
        } catch (Throwable $e) {
            if (!$inTransaction && $this->db->inTransaction()) {
                Database::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Alias compatible que registra un asiento de partida doble y retorna su ID entero
     */
    public function crearAsiento(int $empresaId, int $usuarioId, array $cabecera, array $renglones): int
    {
        $res = $this->crearAsientoPartidaDoble($empresaId, $usuarioId, $cabecera, $renglones);
        return (int)$res['id'];
    }

    /**
     * Obtiene el Resumen de Diario agrupado por cuenta contable (Formato Registro Mercantil / Auto-Eléctrico Ítalo)
     */
    public function obtenerResumenDiarioPorCuenta(int $empresaId, string $fechaDesde, string $fechaHasta): array
    {
        $sql = 'SELECT
                    pc.codigo,
                    pc.nombre,
                    COALESCE(SUM(ad.debe), 0.00) AS total_debe,
                    COALESCE(SUM(ad.haber), 0.00) AS total_haber
                FROM asiento_detalles ad
                INNER JOIN asientos a ON a.id = ad.asiento_id AND a.empresa_id = ad.empresa_id
                INNER JOIN plan_cuentas pc ON pc.id = ad.cuenta_id AND pc.empresa_id = ad.empresa_id
                WHERE ad.empresa_id = :empresa_id
                  AND a.estado = "PUBLICADO"
                  AND a.fecha >= :fecha_desde
                  AND a.fecha <= :fecha_hasta
                GROUP BY pc.id, pc.codigo, pc.nombre
                HAVING total_debe > 0 OR total_haber > 0
                ORDER BY pc.codigo ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id'  => $empresaId,
            ':fecha_desde' => $fechaDesde,
            ':fecha_hasta' => $fechaHasta,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Anula un asiento contable marcando su estado como ANULADO sin borrar el registro histórico
     */
    public function anularAsiento(int $asientoId, int $empresaId, string $motivo = ''): void
    {
        $asiento = $this->obtenerAsientoConDetalles($asientoId, $empresaId);
        if (!$asiento) {
            throw new RuntimeException('El asiento contable no existe en la empresa activa.');
        }

        if ($asiento['estado'] === 'ANULADO') {
            throw new RuntimeException('El asiento ' . $asiento['numero_asiento'] . ' ya se encuentra anulado.');
        }

        $notaAnulacion = $motivo !== ''
            ? ' [ANULADO: ' . trim($motivo) . ']'
            : ' [ANULADO EL ' . date('Y-m-d H:i') . ']';

        $nuevoConcepto = mb_substr((string)$asiento['concepto'] . $notaAnulacion, 0, 500);

        $sql = 'UPDATE asientos
                SET estado = "ANULADO", concepto = :concepto
                WHERE id = :id AND empresa_id = :empresa_id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':concepto'   => $nuevoConcepto,
            ':id'         => $asientoId,
            ':empresa_id' => $empresaId,
        ]);
    }

    /**
     * Genera el Libro Mayor Analítico por cuenta (o todas las cuentas con movimiento)
     * calculando Saldo Anterior al fechaDesde, movimientos cronológicos y Saldo Acumulado fila por fila.
     */
    public function obtenerLibroMayor(int $empresaId, string $fechaDesde, string $fechaHasta, ?int $cuentaId = null): array
    {
        // 1. Obtener las cuentas imputables a evaluar
        $sqlCuentas = 'SELECT id, codigo, nombre, tipo, naturaleza, saldo_inicial
                       FROM plan_cuentas
                       WHERE empresa_id = :empresa_id AND acepta_movimiento = 1';
        $paramsCuentas = [':empresa_id' => $empresaId];

        if ($cuentaId !== null && $cuentaId > 0) {
            $sqlCuentas .= ' AND id = :cuenta_id';
            $paramsCuentas[':cuenta_id'] = $cuentaId;
        }

        $sqlCuentas .= ' ORDER BY codigo ASC';
        $stmtCuentas = $this->db->prepare($sqlCuentas);
        $stmtCuentas->execute($paramsCuentas);
        $cuentas = $stmtCuentas->fetchAll();

        if (empty($cuentas)) {
            return [];
        }

        // 2. Sumatorias previas a $fechaDesde para el Saldo Anterior
        $sqlPrevios = 'SELECT
                            ad.cuenta_id,
                            COALESCE(SUM(ad.debe), 0.00) AS debe_previo,
                            COALESCE(SUM(ad.haber), 0.00) AS haber_previo
                       FROM asiento_detalles ad
                       INNER JOIN asientos a ON a.id = ad.asiento_id AND a.empresa_id = ad.empresa_id
                       WHERE ad.empresa_id = :empresa_id
                         AND a.estado = "PUBLICADO"
                         AND a.fecha < :fecha_desde
                       GROUP BY ad.cuenta_id';

        $stmtPrev = $this->db->prepare($sqlPrevios);
        $stmtPrev->execute([
            ':empresa_id'  => $empresaId,
            ':fecha_desde' => $fechaDesde,
        ]);

        $mapaPrevios = [];
        foreach ($stmtPrev->fetchAll() as $rowPrev) {
            $mapaPrevios[(int)$rowPrev['cuenta_id']] = [
                'debe'  => (float)$rowPrev['debe_previo'],
                'haber' => (float)$rowPrev['haber_previo'],
            ];
        }

        // 3. Movimientos dentro del rango [$fechaDesde, $fechaHasta]
        $sqlMov = 'SELECT
                        ad.id AS detalle_id,
                        ad.cuenta_id,
                        ad.descripcion AS descripcion_linea,
                        ad.debe,
                        ad.haber,
                        a.id AS asiento_id,
                        a.numero_asiento,
                        a.fecha,
                        a.referencia,
                        a.concepto
                   FROM asiento_detalles ad
                   INNER JOIN asientos a ON a.id = ad.asiento_id AND a.empresa_id = ad.empresa_id
                   WHERE ad.empresa_id = :empresa_id
                     AND a.estado = "PUBLICADO"
                     AND a.fecha >= :fecha_desde
                     AND a.fecha <= :fecha_hasta';

        $paramsMov = [
            ':empresa_id'  => $empresaId,
            ':fecha_desde' => $fechaDesde,
            ':fecha_hasta' => $fechaHasta,
        ];

        if ($cuentaId !== null && $cuentaId > 0) {
            $sqlMov .= ' AND ad.cuenta_id = :cuenta_id';
            $paramsMov[':cuenta_id'] = $cuentaId;
        }

        $sqlMov .= ' ORDER BY a.fecha ASC, a.id ASC, ad.id ASC';

        $stmtMov = $this->db->prepare($sqlMov);
        $stmtMov->execute($paramsMov);

        $movimientosPorCuenta = [];
        foreach ($stmtMov->fetchAll() as $mov) {
            $movimientosPorCuenta[(int)$mov['cuenta_id']][] = $mov;
        }

        // 4. Construir estructura analítica con Saldo Acumulado fila por fila
        $resultadoMayor = [];

        foreach ($cuentas as $cta) {
            $idCta        = (int)$cta['id'];
            $esDeudora    = ($cta['naturaleza'] === 'DEUDORA');
            $saldoInicial = (float)$cta['saldo_inicial'];
            $prevDebe     = $mapaPrevios[$idCta]['debe'] ?? 0.0;
            $prevHaber    = $mapaPrevios[$idCta]['haber'] ?? 0.0;

            $saldoAnterior = $esDeudora
                ? round($saldoInicial + $prevDebe - $prevHaber, 2)
                : round($saldoInicial + $prevHaber - $prevDebe, 2);

            $movs = $movimientosPorCuenta[$idCta] ?? [];

            // Si se consultan todas las cuentas, omitir las que no tienen saldo anterior ni movimientos en el período
            if ($cuentaId === null && abs($saldoAnterior) < 0.001 && empty($movs)) {
                continue;
            }

            $saldoCorriente = $saldoAnterior;
            $totalDebePeriodo  = 0.0;
            $totalHaberPeriodo = 0.0;
            $renglonesConSaldo = [];

            foreach ($movs as $m) {
                $debe  = round((float)$m['debe'], 2);
                $haber = round((float)$m['haber'], 2);

                $saldoCorriente = $esDeudora
                    ? round($saldoCorriente + $debe - $haber, 2)
                    : round($saldoCorriente + $haber - $debe, 2);

                $totalDebePeriodo  = round($totalDebePeriodo + $debe, 2);
                $totalHaberPeriodo = round($totalHaberPeriodo + $haber, 2);

                $m['saldo_acumulado'] = $saldoCorriente;
                $renglonesConSaldo[] = $m;
            }

            $resultadoMayor[] = [
                'cuenta'              => $cta,
                'saldo_anterior'      => $saldoAnterior,
                'movimientos'         => $renglonesConSaldo,
                'total_debe_periodo'  => $totalDebePeriodo,
                'total_haber_periodo' => $totalHaberPeriodo,
                'saldo_final'         => $saldoCorriente,
            ];
        }

        return $resultadoMayor;
    }

    /**
     * Calcula los 3 Estados Financieros interconectados para la empresa activa y rango de fechas:
     * 1. Balance de Comprobación
     * 2. Estado de Resultados (Ganancias y Pérdidas)
     * 3. Balance General (Estado de Situación Financiera con cuadre Activo = Pasivo + Patrimonio)
     */
    public function obtenerEstadosFinancieros(int $empresaId, string $fechaDesde, string $fechaHasta): array
    {
        $sql = 'SELECT
                    pc.id,
                    pc.codigo,
                    pc.nombre,
                    pc.tipo,
                    pc.naturaleza,
                    pc.nivel,
                    pc.codigo_padre,
                    pc.acepta_movimiento,
                    pc.saldo_inicial,
                    COALESCE(SUM(CASE WHEN a.id IS NOT NULL AND a.fecha < :f_desde1 THEN ad.debe ELSE 0 END), 0.00) AS debe_anterior,
                    COALESCE(SUM(CASE WHEN a.id IS NOT NULL AND a.fecha < :f_desde2 THEN ad.haber ELSE 0 END), 0.00) AS haber_anterior,
                    COALESCE(SUM(CASE WHEN a.id IS NOT NULL AND a.fecha >= :f_desde3 AND a.fecha <= :f_hasta1 THEN ad.debe ELSE 0 END), 0.00) AS debe_periodo,
                    COALESCE(SUM(CASE WHEN a.id IS NOT NULL AND a.fecha >= :f_desde4 AND a.fecha <= :f_hasta2 THEN ad.haber ELSE 0 END), 0.00) AS haber_periodo
                FROM plan_cuentas pc
                LEFT JOIN asiento_detalles ad
                    ON ad.cuenta_id = pc.id AND ad.empresa_id = pc.empresa_id
                LEFT JOIN asientos a
                    ON a.id = ad.asiento_id AND a.empresa_id = ad.empresa_id AND a.estado = "PUBLICADO"
                WHERE pc.empresa_id = :empresa_id AND pc.acepta_movimiento = 1 AND pc.activo = 1
                GROUP BY pc.id
                ORDER BY pc.codigo ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':f_desde1'   => $fechaDesde,
            ':f_desde2'   => $fechaDesde,
            ':f_desde3'   => $fechaDesde,
            ':f_hasta1'   => $fechaHasta,
            ':f_desde4'   => $fechaDesde,
            ':f_hasta2'   => $fechaHasta,
            ':empresa_id' => $empresaId,
        ]);

        $cuentasMov = $stmt->fetchAll();

        // 1. Estructuras para Balance de Comprobación
        $filasComprobacion = [];
        $totalesComprobacion = [
            'saldo_anterior_deudor'   => 0.0,
            'saldo_anterior_acreedor' => 0.0,
            'debitos_periodo'         => 0.0,
            'creditos_periodo'        => 0.0,
            'saldo_actual_deudor'     => 0.0,
            'saldo_actual_acreedor'   => 0.0,
        ];

        // 2. Estructuras para Estado de Resultados (Período seleccionado)
        $ingresosList = [];
        $costosList   = [];
        $gastosList   = [];
        $totalIngresosPeriodo = 0.0;
        $totalCostosPeriodo   = 0.0;
        $totalGastosPeriodo   = 0.0;

        // Resultado acumulado de ejercicios/períodos anteriores a $fechaDesde (para cuadrar el Balance General)
        $resultadoEjerciciosAnteriores = 0.0;

        // 3. Estructuras para Balance General (Situación Financiera al $fechaHasta)
        $activosList    = [];
        $pasivosList    = [];
        $patrimonioList = [];
        $totalActivos    = 0.0;
        $totalPasivos    = 0.0;
        $totalPatrimonio = 0.0;

        foreach ($cuentasMov as $c) {
            $saldoInicial = (float)$c['saldo_inicial'];
            $debeAnt      = (float)$c['debe_anterior'];
            $haberAnt     = (float)$c['haber_anterior'];
            $debePer      = (float)$c['debe_periodo'];
            $haberPer     = (float)$c['haber_periodo'];

            $esNaturalezaDeudora = ($c['naturaleza'] === 'DEUDORA');

            // Neto anterior (positivo = deudor, negativo = acreedor)
            $ajusteInicialDeudor = $esNaturalezaDeudora ? $saldoInicial : -$saldoInicial;
            $netoAnterior = round($ajusteInicialDeudor + $debeAnt - $haberAnt, 2);
            $netoActual   = round($netoAnterior + $debePer - $haberPer, 2);

            $tieneActividad = abs($netoAnterior) >= 0.005
                || abs($debePer) >= 0.005
                || abs($haberPer) >= 0.005
                || abs($netoActual) >= 0.005;

            if ($tieneActividad) {
                $saldoAntDeudor   = $netoAnterior > 0 ? $netoAnterior : 0.0;
                $saldoAntAcreedor = $netoAnterior < 0 ? abs($netoAnterior) : 0.0;
                $saldoActDeudor   = $netoActual > 0 ? $netoActual : 0.0;
                $saldoActAcreedor = $netoActual < 0 ? abs($netoActual) : 0.0;

                $filasComprobacion[] = [
                    'codigo'                  => $c['codigo'],
                    'nombre'                  => $c['nombre'],
                    'tipo'                    => $c['tipo'],
                    'naturaleza'              => $c['naturaleza'],
                    'saldo_anterior_neto'     => $netoAnterior,
                    'saldo_anterior_deudor'   => $saldoAntDeudor,
                    'saldo_anterior_acreedor' => $saldoAntAcreedor,
                    'debitos_periodo'         => $debePer,
                    'creditos_periodo'        => $haberPer,
                    'saldo_actual_deudor'     => $saldoActDeudor,
                    'saldo_actual_acreedor'   => $saldoActAcreedor,
                ];

                $totalesComprobacion['saldo_anterior_deudor']   = round($totalesComprobacion['saldo_anterior_deudor'] + $saldoAntDeudor, 2);
                $totalesComprobacion['saldo_anterior_acreedor'] = round($totalesComprobacion['saldo_anterior_acreedor'] + $saldoAntAcreedor, 2);
                $totalesComprobacion['debitos_periodo']         = round($totalesComprobacion['debitos_periodo'] + $debePer, 2);
                $totalesComprobacion['creditos_periodo']        = round($totalesComprobacion['creditos_periodo'] + $haberPer, 2);
                $totalesComprobacion['saldo_actual_deudor']     = round($totalesComprobacion['saldo_actual_deudor'] + $saldoActDeudor, 2);
                $totalesComprobacion['saldo_actual_acreedor']   = round($totalesComprobacion['saldo_actual_acreedor'] + $saldoActAcreedor, 2);
            }

            // Clasificación para Estado de Resultados y Balance General
            $tipo = (string)$c['tipo'];

            if ($tipo === 'INGRESOS') {
                // En cuentas nominales de Ingresos: movimiento del período (Haber - Debe)
                $montoPeriodo  = round($haberPer - $debePer, 2);
                $montoAnterior = round(-$netoAnterior, 2); // Crédito acumulado previo
                $resultadoEjerciciosAnteriores = round($resultadoEjerciciosAnteriores + $montoAnterior, 2);

                if (abs($montoPeriodo) >= 0.005) {
                    $ingresosList[] = [
                        'codigo' => $c['codigo'],
                        'nombre' => $c['nombre'],
                        'monto'  => $montoPeriodo,
                    ];
                    $totalIngresosPeriodo = round($totalIngresosPeriodo + $montoPeriodo, 2);
                }
            } elseif ($tipo === 'COSTO') {
                $montoPeriodo  = round($debePer - $haberPer, 2);
                $montoAnterior = round($netoAnterior, 2);
                $resultadoEjerciciosAnteriores = round($resultadoEjerciciosAnteriores - $montoAnterior, 2);

                if (abs($montoPeriodo) >= 0.005) {
                    $costosList[] = [
                        'codigo' => $c['codigo'],
                        'nombre' => $c['nombre'],
                        'monto'  => $montoPeriodo,
                    ];
                    $totalCostosPeriodo = round($totalCostosPeriodo + $montoPeriodo, 2);
                }
            } elseif ($tipo === 'GASTOS') {
                $montoPeriodo  = round($debePer - $haberPer, 2);
                $montoAnterior = round($netoAnterior, 2);
                $resultadoEjerciciosAnteriores = round($resultadoEjerciciosAnteriores - $montoAnterior, 2);

                if (abs($montoPeriodo) >= 0.005) {
                    $gastosList[] = [
                        'codigo' => $c['codigo'],
                        'nombre' => $c['nombre'],
                        'monto'  => $montoPeriodo,
                    ];
                    $totalGastosPeriodo = round($totalGastosPeriodo + $montoPeriodo, 2);
                }
            } elseif ($tipo === 'ACTIVO') {
                // Para Activos: saldo deudor suma al activo, saldo acreedor (ej. depreciación acumulada) resta al activo
                $montoActivo = round($netoActual, 2);
                if (abs($montoActivo) >= 0.005) {
                    $activosList[] = [
                        'codigo'     => $c['codigo'],
                        'nombre'     => $c['nombre'],
                        'naturaleza' => $c['naturaleza'],
                        'monto'      => $montoActivo,
                    ];
                    $totalActivos = round($totalActivos + $montoActivo, 2);
                }
            } elseif ($tipo === 'PASIVO') {
                // Para Pasivos: saldo acreedor es positivo en el pasivo
                $montoPasivo = round(-$netoActual, 2);
                if (abs($montoPasivo) >= 0.005) {
                    $pasivosList[] = [
                        'codigo' => $c['codigo'],
                        'nombre' => $c['nombre'],
                        'monto'  => $montoPasivo,
                    ];
                    $totalPasivos = round($totalPasivos + $montoPasivo, 2);
                }
            } elseif ($tipo === 'CAPITAL') {
                // Para Patrimonio / Capital: saldo acreedor es positivo en el patrimonio
                $montoPatrimonio = round(-$netoActual, 2);
                if (abs($montoPatrimonio) >= 0.005) {
                    $patrimonioList[] = [
                        'codigo' => $c['codigo'],
                        'nombre' => $c['nombre'],
                        'monto'  => $montoPatrimonio,
                    ];
                    $totalPatrimonio = round($totalPatrimonio + $montoPatrimonio, 2);
                }
            }
        }

        $utilidadBruta = round($totalIngresosPeriodo - $totalCostosPeriodo, 2);
        $utilidadNetaEjercicio = round($utilidadBruta - $totalGastosPeriodo, 2);

        // Integrar automáticamente en el Patrimonio el Resultado Acumulado previo y el Resultado del Ejercicio Actual
        $totalPatrimonioConResultados = round($totalPatrimonio + $resultadoEjerciciosAnteriores + $utilidadNetaEjercicio, 2);
        $totalPasivoMasPatrimonio     = round($totalPasivos + $totalPatrimonioConResultados, 2);
        $diferenciaEcuacionPatrimonial= round(abs($totalActivos - $totalPasivoMasPatrimonio), 2);

        return [
            'balance_comprobacion' => [
                'filas'   => $filasComprobacion,
                'totales' => $totalesComprobacion,
            ],
            'estado_resultados' => [
                'ingresos'        => $ingresosList,
                'costos'          => $costosList,
                'gastos'          => $gastosList,
                'total_ingresos'  => $totalIngresosPeriodo,
                'total_costos'    => $totalCostosPeriodo,
                'utilidad_bruta'  => $utilidadBruta,
                'total_gastos'    => $totalGastosPeriodo,
                'resultado_neto'  => $utilidadNetaEjercicio,
            ],
            'balance_general' => [
                'activos'                         => $activosList,
                'pasivos'                         => $pasivosList,
                'patrimonio'                      => $patrimonioList,
                'resultado_ejercicios_anteriores' => $resultadoEjerciciosAnteriores,
                'resultado_ejercicio_actual'      => $utilidadNetaEjercicio,
                'total_activos'                   => $totalActivos,
                'total_pasivos'                   => $totalPasivos,
                'total_patrimonio_cuentas'        => $totalPatrimonio,
                'total_patrimonio'                => $totalPatrimonioConResultados,
                'total_pasivo_patrimonio'         => $totalPasivoMasPatrimonio,
                'diferencia_ecuacion'             => $diferenciaEcuacionPatrimonial,
                'ecuacion_cuadrada'               => $diferenciaEcuacionPatrimonial < 0.01,
            ],
        ];
    }
}
