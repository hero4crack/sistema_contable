<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;
use RuntimeException;

/**
 * Modelo del Plan de Cuentas Contable Jerárquico (Multi-Empresa)
 */
final class PlanCuentasModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lista el catálogo de cuentas de una empresa con conteo de movimientos y subcuentas hijas
     */
    public function listarPorEmpresa(int $empresaId, ?string $tipo = null, ?string $busqueda = null, bool $soloActivas = false): array
    {
        $sql = 'SELECT
                    pc.id,
                    pc.empresa_id,
                    pc.codigo,
                    pc.nombre,
                    pc.tipo,
                    pc.naturaleza,
                    pc.nivel,
                    pc.codigo_padre,
                    pc.acepta_movimiento,
                    pc.saldo_inicial,
                    pc.activo,
                    (SELECT COUNT(*) FROM asiento_detalles ad
                     WHERE ad.empresa_id = pc.empresa_id AND ad.cuenta_id = pc.id) AS movimientos_count,
                    (SELECT COUNT(*) FROM plan_cuentas sub
                     WHERE sub.empresa_id = pc.empresa_id AND sub.codigo_padre = pc.codigo) AS hijos_count,
                    COALESCE((
                        SELECT SUM(ad.debe)
                        FROM asiento_detalles ad
                        INNER JOIN asientos a ON a.id = ad.asiento_id AND a.empresa_id = ad.empresa_id
                        WHERE ad.empresa_id = pc.empresa_id AND ad.cuenta_id = pc.id AND a.estado = "PUBLICADO"
                    ), 0.00) AS total_debe,
                    COALESCE((
                        SELECT SUM(ad.haber)
                        FROM asiento_detalles ad
                        INNER JOIN asientos a ON a.id = ad.asiento_id AND a.empresa_id = ad.empresa_id
                        WHERE ad.empresa_id = pc.empresa_id AND ad.cuenta_id = pc.id AND a.estado = "PUBLICADO"
                    ), 0.00) AS total_haber
                FROM plan_cuentas pc
                WHERE pc.empresa_id = :empresa_id';

        $params = [':empresa_id' => $empresaId];

        if ($soloActivas) {
            $sql .= ' AND pc.activo = 1';
        }

        if ($tipo !== null && $tipo !== '') {
            $sql .= ' AND pc.tipo = :tipo';
            $params[':tipo'] = $tipo;
        }

        if ($busqueda !== null && trim($busqueda) !== '') {
            $sql .= ' AND (pc.codigo LIKE :q1 OR pc.nombre LIKE :q2)';
            $termino = '%' . trim($busqueda) . '%';
            $params[':q1'] = $termino;
            $params[':q2'] = $termino;
        }

        $sql .= ' ORDER BY pc.codigo ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Lista únicamente las cuentas activas que aceptan movimiento (Imputables en asientos contables)
     */
    public function listarImputablesPorEmpresa(int $empresaId): array
    {
        $sql = 'SELECT id, codigo, nombre, tipo, naturaleza, nivel, saldo_inicial
                FROM plan_cuentas
                WHERE empresa_id = :empresa_id AND activo = 1 AND acepta_movimiento = 1
                ORDER BY codigo ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene una cuenta específica validando estrictamente su pertenencia a la empresa activa
     */
    public function obtenerPorIdYEmpresa(int $id, int $empresaId): ?array
    {
        $sql = 'SELECT
                    pc.*,
                    (SELECT COUNT(*) FROM asiento_detalles ad
                     WHERE ad.empresa_id = pc.empresa_id AND ad.cuenta_id = pc.id) AS movimientos_count,
                    (SELECT COUNT(*) FROM plan_cuentas sub
                     WHERE sub.empresa_id = pc.empresa_id AND sub.codigo_padre = pc.codigo) AS hijos_count
                FROM plan_cuentas pc
                WHERE pc.id = :id AND pc.empresa_id = :empresa_id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':empresa_id' => $empresaId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Obtiene una cuenta por su código contable dentro de una empresa
     */
    public function obtenerPorCodigoYEmpresa(string $codigo, int $empresaId): ?array
    {
        $sql = 'SELECT * FROM plan_cuentas
                WHERE empresa_id = :empresa_id AND codigo = :codigo
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':codigo'     => trim($codigo),
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Verifica si ya existe un código contable en la empresa
     */
    public function existeCodigo(int $empresaId, string $codigo, ?int $excluirId = null): bool
    {
        $sql = 'SELECT id FROM plan_cuentas WHERE empresa_id = :empresa_id AND codigo = :codigo';
        $params = [
            ':empresa_id' => $empresaId,
            ':codigo'     => trim($codigo),
        ];

        if ($excluirId !== null && $excluirId > 0) {
            $sql .= ' AND id != :excluir_id';
            $params[':excluir_id'] = $excluirId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Sugiere el próximo código hijo disponible bajo una cuenta padre
     */
    public function sugerirSiguienteSubcodigo(int $empresaId, string $codigoPadre): string
    {
        $sql = 'SELECT codigo FROM plan_cuentas
                WHERE empresa_id = :empresa_id AND codigo_padre = :padre
                ORDER BY codigo DESC
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':padre'      => $codigoPadre,
        ]);

        $ultimo = $stmt->fetchColumn();
        $segmentosPadre = count(explode('.', $codigoPadre));

        if (!$ultimo) {
            if ($segmentosPadre === 1) {
                return $codigoPadre . '.1';
            }
            if ($segmentosPadre === 2) {
                return $codigoPadre . '.01';
            }
            return $codigoPadre . '.001';
        }

        $partes = explode('.', (string)$ultimo);
        $ultimoSegmento = end($partes);
        $longitud = strlen($ultimoSegmento);
        $siguienteNum = (int)$ultimoSegmento + 1;

        $partes[count($partes) - 1] = str_pad((string)$siguienteNum, $longitud, '0', STR_PAD_LEFT);
        return implode('.', $partes);
    }

    /**
     * Crea una nueva cuenta o subcuenta en el catálogo de la empresa activa
     */
    public function crearCuenta(int $empresaId, array $datos): int
    {
        $codigo = trim((string)$datos['codigo'], " \t\n\r\0\x0B.");
        $codigoPadre = !empty($datos['codigo_padre']) ? trim((string)$datos['codigo_padre'], " \t\n\r\0\x0B.") : null;

        if ($codigoPadre === null && str_contains($codigo, '.')) {
            $partes = explode('.', $codigo);
            array_pop($partes);
            $codigoPadre = implode('.', $partes);
        }

        $nivel = count(explode('.', $codigo));

        if ($codigoPadre !== null && $codigoPadre !== '') {
            $padre = $this->obtenerPorCodigoYEmpresa($codigoPadre, $empresaId);
            if ($padre) {
                $nivel = (int)$padre['nivel'] + 1;
            }
        } else {
            $codigoPadre = null;
        }

        $sql = 'INSERT INTO plan_cuentas
                (empresa_id, codigo, nombre, tipo, naturaleza, nivel, codigo_padre, acepta_movimiento, saldo_inicial, activo)
                VALUES
                (:empresa_id, :codigo, :nombre, :tipo, :naturaleza, :nivel, :codigo_padre, :acepta_movimiento, :saldo_inicial, :activo)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id'        => $empresaId,
            ':codigo'            => $codigo,
            ':nombre'            => trim((string)$datos['nombre']),
            ':tipo'              => (string)$datos['tipo'],
            ':naturaleza'        => (string)$datos['naturaleza'],
            ':nivel'             => $nivel,
            ':codigo_padre'      => $codigoPadre,
            ':acepta_movimiento' => (int)($datos['acepta_movimiento'] ?? 1) === 1 ? 1 : 0,
            ':saldo_inicial'     => round((float)($datos['saldo_inicial'] ?? 0.00), 2),
            ':activo'            => (int)($datos['activo'] ?? 1) === 1 ? 1 : 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza una cuenta existente validando integridad de movimientos e hijos
     */
    public function actualizarCuenta(int $id, int $empresaId, array $datos): bool
    {
        $actual = $this->obtenerPorIdYEmpresa($id, $empresaId);
        if (!$actual) {
            throw new RuntimeException('La cuenta contable no existe en la empresa activa.');
        }

        $nuevoAceptaMov = (int)($datos['acepta_movimiento'] ?? $actual['acepta_movimiento']) === 1 ? 1 : 0;
        $nuevoActivo    = (int)($datos['activo'] ?? $actual['activo']) === 1 ? 1 : 0;

        if ((int)$actual['movimientos_count'] > 0 && $nuevoAceptaMov === 0) {
            throw new RuntimeException('No puede convertir en cuenta agrupadora una cuenta que ya posee movimientos en el Libro Diario.');
        }

        if ((int)$actual['hijos_count'] > 0 && $nuevoAceptaMov === 1) {
            throw new RuntimeException('Una cuenta que posee subcuentas hijas debe permanecer como cuenta agrupadora.');
        }

        if ($nuevoActivo === 0 && ((int)$actual['movimientos_count'] > 0 || (int)$actual['hijos_count'] > 0)) {
            throw new RuntimeException('No se puede inactivar una cuenta que posee movimientos registrados o subcuentas dependientes.');
        }

        $sql = 'UPDATE plan_cuentas SET
                    nombre = :nombre,
                    tipo = :tipo,
                    naturaleza = :naturaleza,
                    acepta_movimiento = :acepta_movimiento,
                    saldo_inicial = :saldo_inicial,
                    activo = :activo
                WHERE id = :id AND empresa_id = :empresa_id';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre'            => trim((string)$datos['nombre']),
            ':tipo'              => (string)$datos['tipo'],
            ':naturaleza'        => (string)$datos['naturaleza'],
            ':acepta_movimiento' => $nuevoAceptaMov,
            ':saldo_inicial'     => round((float)($datos['saldo_inicial'] ?? 0.00), 2),
            ':activo'            => $nuevoActivo,
            ':id'                => $id,
            ':empresa_id'        => $empresaId,
        ]);
    }

    /**
     * Elimina o inactiva una cuenta ÚNICAMENTE si no tiene movimientos registrados ni subcuentas hijas
     */
    public function eliminarCuentaSegura(int $id, int $empresaId): void
    {
        $cuenta = $this->obtenerPorIdYEmpresa($id, $empresaId);
        if (!$cuenta) {
            throw new RuntimeException('La cuenta solicitada no existe.');
        }

        if ((int)$cuenta['movimientos_count'] > 0) {
            throw new RuntimeException(
                sprintf(
                    'Integridad Contable: La cuenta "%s - %s" tiene %d movimiento(s) en asiento_detalles y no puede ser eliminada.',
                    $cuenta['codigo'],
                    $cuenta['nombre'],
                    (int)$cuenta['movimientos_count']
                )
            );
        }

        if ((int)$cuenta['hijos_count'] > 0) {
            throw new RuntimeException(
                sprintf(
                    'Integridad Jerárquica: La cuenta "%s - %s" posee %d subcuenta(s) hija(s). Elimine primero sus subcuentas.',
                    $cuenta['codigo'],
                    $cuenta['nombre'],
                    (int)$cuenta['hijos_count']
                )
            );
        }

        $sql = 'DELETE FROM plan_cuentas WHERE id = :id AND empresa_id = :empresa_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id'         => $id,
            ':empresa_id' => $empresaId,
        ]);
    }

    /**
     * Cuenta el total de cuentas y cuentas de movimiento para una empresa
     */
    public function contarEstadisticasPorEmpresa(int $empresaId): array
    {
        $sql = 'SELECT
                    COUNT(*) AS total_cuentas,
                    SUM(CASE WHEN acepta_movimiento = 1 THEN 1 ELSE 0 END) AS cuentas_movimiento,
                    SUM(CASE WHEN acepta_movimiento = 0 THEN 1 ELSE 0 END) AS cuentas_grupo
                FROM plan_cuentas
                WHERE empresa_id = :empresa_id AND activo = 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);
        $row = $stmt->fetch();

        return [
            'total_cuentas'      => (int)($row['total_cuentas'] ?? 0),
            'cuentas_movimiento' => (int)($row['cuentas_movimiento'] ?? 0),
            'cuentas_grupo'      => (int)($row['cuentas_grupo'] ?? 0),
        ];
    }

    /**
     * Obtiene el resumen de cuentas agrupadas por rubro contable
     */
    public function resumenPorRubro(int $empresaId): array
    {
        $sql = 'SELECT
                    tipo,
                    COUNT(*) AS total,
                    SUM(CASE WHEN acepta_movimiento = 1 THEN 1 ELSE 0 END) AS imputables
                FROM plan_cuentas
                WHERE empresa_id = :empresa_id AND activo = 1
                GROUP BY tipo
                ORDER BY FIELD(tipo, "ACTIVO", "PASIVO", "CAPITAL", "INGRESOS", "COSTO", "GASTOS")';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll();
    }

    /**
     * Clona el Plan de Cuentas Base oficial de Juris Contable para una nueva empresa cliente.
     */
    public function clonarPlanBaseParaEmpresa(int $empresaId): int
    {
        $catalogo = self::obtenerCatalogoBaseVenezolano();

        $sql = 'INSERT INTO plan_cuentas
                (empresa_id, codigo, nombre, tipo, naturaleza, nivel, codigo_padre, acepta_movimiento, saldo_inicial, activo)
                VALUES
                (:empresa_id, :codigo, :nombre, :tipo, :naturaleza, :nivel, :codigo_padre, :acepta_movimiento, 0.00, 1)';

        $stmt = $this->db->prepare($sql);
        $insertados = 0;

        foreach ($catalogo as $cuenta) {
            $stmt->execute([
                ':empresa_id'        => $empresaId,
                ':codigo'            => $cuenta[0],
                ':nombre'            => $cuenta[1],
                ':tipo'              => $cuenta[2],
                ':naturaleza'        => $cuenta[3],
                ':nivel'             => $cuenta[4],
                ':codigo_padre'      => $cuenta[5],
                ':acepta_movimiento' => $cuenta[6],
            ]);
            $insertados++;
        }

        return $insertados;
    }

    /**
     * Catálogo Base Venezolano (Estructura oficial importada de PLAN DE CUENTAS.xls)
     */
    public static function obtenerCatalogoBaseVenezolano(): array
    {
        return [
            // 1. ACTIVO
            ['1', 'ACTIVO', 'ACTIVO', 'DEUDORA', 1, null, 0],
            ['1.1', 'Efectivo y Equivalente de Efectivo', 'ACTIVO', 'DEUDORA', 2, '1', 0],
            ['1.1.01', 'Caja', 'ACTIVO', 'DEUDORA', 3, '1.1', 0],
            ['1.1.01.001', 'Caja', 'ACTIVO', 'DEUDORA', 4, '1.1.01', 1],
            ['1.1.01.002', 'Caja Chica', 'ACTIVO', 'DEUDORA', 4, '1.1.01', 1],
            ['1.1.02', 'Bancos', 'ACTIVO', 'DEUDORA', 3, '1.1', 0],
            ['1.1.02.001', 'BANCO PROVINCIAL', 'ACTIVO', 'DEUDORA', 4, '1.1.02', 1],
            ['1.1.02.002', 'BANCAMIGA', 'ACTIVO', 'DEUDORA', 4, '1.1.02', 1],
            ['1.1.02.003', 'BANCO DE VENEZUELA', 'ACTIVO', 'DEUDORA', 4, '1.1.02', 1],
            ['1.1.02.004', 'PROVINCIAL DIVISAS', 'ACTIVO', 'DEUDORA', 4, '1.1.02', 1],
            ['1.1.02.005', 'BANCAMIGA DIVISAS', 'ACTIVO', 'DEUDORA', 4, '1.1.02', 1],
            ['1.2', 'Cuentas por cobrar', 'ACTIVO', 'DEUDORA', 2, '1', 0],
            ['1.2.01', 'Cuentas por cobrar', 'ACTIVO', 'DEUDORA', 3, '1.2', 0],
            ['1.2.01.001', 'Cuentas por Cobrar', 'ACTIVO', 'DEUDORA', 4, '1.2.01', 1],
            ['1.2.01.002', 'Cuentas por Cobrar Clientes', 'ACTIVO', 'DEUDORA', 4, '1.2.01', 1],
            ['1.2.02', 'Cuentas por cobrar accionistas', 'ACTIVO', 'DEUDORA', 3, '1.2', 0],
            ['1.2.02.001', 'Cuenta por cobrar Dumelys Gonzalez', 'ACTIVO', 'DEUDORA', 4, '1.2.02', 1],
            ['1.2.02.002', 'Cuenta por cobrar Maria Virginia Rodriguez', 'ACTIVO', 'DEUDORA', 4, '1.2.02', 1],
            ['1.2.03', 'Cuentas por cobrar empleados', 'ACTIVO', 'DEUDORA', 3, '1.2', 0],
            ['1.2.03.001', 'CUENTAS POR COBRAR EMPLEADOS', 'ACTIVO', 'DEUDORA', 4, '1.2.03', 1],
            ['1.3', 'Mercancía', 'ACTIVO', 'DEUDORA', 2, '1', 0],
            ['1.3.01', 'Mercancía en existencia', 'ACTIVO', 'DEUDORA', 3, '1.3', 0],
            ['1.3.01.001', 'Mercancía en existencia', 'ACTIVO', 'DEUDORA', 4, '1.3.01', 1],
            ['1.4', 'Activo por Impuesto Corriente', 'ACTIVO', 'DEUDORA', 2, '1', 0],
            ['1.4.01', 'Activo por Impuesto Corriente', 'ACTIVO', 'DEUDORA', 3, '1.4', 0],
            ['1.4.01.001', 'Excedente Credito Fiscal Iva', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1],
            ['1.4.01.002', 'Retenciones de IVA', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1],
            ['1.4.01.003', 'Retenciones de ISLR', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1],
            ['1.4.01.004', 'ISLR Pagado en Exceso', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1],
            ['1.4.01.005', 'Credito Fiscal IVA', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1],
            ['1.4.01.006', 'Impuesto Sobre la Renta Estimada', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1],
            ['1.4.01.007', 'Ret Islr por Compensar', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1],
            ['1.4.01.008', 'Ret Islr', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1],
            ['1.5', 'Prepagados', 'ACTIVO', 'DEUDORA', 2, '1', 0],
            ['1.5.01', 'Prepagados', 'ACTIVO', 'DEUDORA', 3, '1.5', 0],
            ['1.5.01.001', 'Anticipo Proveedores', 'ACTIVO', 'DEUDORA', 4, '1.5.01', 1],
            ['1.5.01.002', 'Anticipo Beneficio Alimentacion', 'ACTIVO', 'DEUDORA', 4, '1.5.01', 1],
            ['1.5.01.003', 'PATENTE PAGADA POR ANTICIPADO', 'ACTIVO', 'DEUDORA', 4, '1.5.01', 1],
            ['1.6', 'Propiedad planta y equipo', 'ACTIVO', 'DEUDORA', 2, '1', 0],
            ['1.6.01', 'Propiedad planta y equipo', 'ACTIVO', 'DEUDORA', 3, '1.6', 0],
            ['1.6.01.001', 'Terrenos', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1],
            ['1.6.01.002', 'Edificios', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1],
            ['1.6.01.003', 'Instalaciones', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1],
            ['1.6.01.004', 'Maquinarias', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1],
            ['1.6.01.005', 'Mobiliario y Equipos de Oficina', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1],
            ['1.6.01.006', 'Equipos y Programas de Computacion', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1],
            ['1.6.01.007', 'Vehiculos', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1],
            ['1.6.01.008', 'Obras en Proceso', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1],
            ['1.7', 'Intangible', 'ACTIVO', 'ACREEDORA', 2, '1', 0],
            ['1.7.01', 'Depreciación y amortización acumulada', 'ACTIVO', 'ACREEDORA', 3, '1.7', 0],
            ['1.7.01.001', 'Depreciacion Acumulada Edificios', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1],
            ['1.7.01.002', 'Depreciacion Acumulada Instalaciones', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1],
            ['1.7.01.003', 'Depreciacion Acumulada Maquinarias', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1],
            ['1.7.01.004', 'Depreciacion Acumulada Mobiliario y Equi', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1],
            ['1.7.01.005', 'Depreciacion acumulada Equipos y Program', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1],
            ['1.7.01.006', 'Depreciacion Acumulada Vehiculos', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1],

            // 2. PASIVO
            ['2', 'PASIVO', 'PASIVO', 'ACREEDORA', 1, null, 0],
            ['2.1', 'Pasivo', 'PASIVO', 'ACREEDORA', 2, '2', 0],
            ['2.1.01', 'Pasivo financieros', 'PASIVO', 'ACREEDORA', 3, '2.1', 0],
            ['2.1.01.001', 'Credito Bancarios', 'PASIVO', 'ACREEDORA', 4, '2.1.01', 1],
            ['2.2', 'Cuentas por Pagar', 'PASIVO', 'ACREEDORA', 2, '2', 0],
            ['2.2.01', 'Cuentas por Pagar', 'PASIVO', 'ACREEDORA', 3, '2.2', 0],
            ['2.2.01.001', 'Cuentas por Pagar', 'PASIVO', 'ACREEDORA', 4, '2.2.01', 1],
            ['2.3', 'Cuentas por Pagar otros', 'PASIVO', 'ACREEDORA', 2, '2', 0],
            ['2.3.01', 'Cuentas por Pagar otros', 'PASIVO', 'ACREEDORA', 3, '2.3', 0],
            ['2.3.01.001', 'Cuenta por pagar Accionistas', 'PASIVO', 'ACREEDORA', 4, '2.3.01', 1],
            ['2.3.01.002', 'Anticipo Cliente', 'PASIVO', 'ACREEDORA', 4, '2.3.01', 1],
            ['2.3.02', 'Retenciones y aportes por pagar', 'PASIVO', 'ACREEDORA', 3, '2.3', 0],
            ['2.3.02.001', 'Retencion SSO', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1],
            ['2.3.02.002', 'Retencion Paro Forzoso', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1],
            ['2.3.02.003', 'Retenciones Ahorro Habitacional', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1],
            ['2.3.02.004', 'Retenciones Ince', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1],
            ['2.3.02.005', 'APORTE PATRONAL FAOV POR PAGAR', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1],
            ['2.3.02.006', 'APORTE PATRONAL SSO POR PAGAR', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1],
            ['2.3.02.007', 'Impuesto sobre Actividad Economica por pagar', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1],
            ['2.3.03', 'Pasivo por Impuesto Corriente', 'PASIVO', 'ACREEDORA', 3, '2.3', 0],
            ['2.3.03.001', 'IVA Debito fiscal', 'PASIVO', 'ACREEDORA', 4, '2.3.03', 1],
            ['2.3.03.002', 'Retencion de iva', 'PASIVO', 'ACREEDORA', 4, '2.3.03', 1],
            ['2.3.03.003', 'Retenciones de I.S.L.R', 'PASIVO', 'ACREEDORA', 4, '2.3.03', 1],
            ['2.3.03.004', 'Impuesto Sobre la Renta', 'PASIVO', 'ACREEDORA', 4, '2.3.03', 1],
            ['2.4', 'Gastos acumulados', 'PASIVO', 'ACREEDORA', 2, '2', 0],
            ['2.4.01', 'Gastos acumulados por pagar', 'PASIVO', 'ACREEDORA', 3, '2.4', 0],
            ['2.4.01.001', 'Ley Politica Habitacional', 'PASIVO', 'ACREEDORA', 4, '2.4.01', 1],
            ['2.4.01.002', 'Seguro Social Obligatorio', 'PASIVO', 'ACREEDORA', 4, '2.4.01', 1],
            ['2.4.01.003', 'Sueldos Directivos por Pagar', 'PASIVO', 'ACREEDORA', 4, '2.4.01', 1],
            ['2.4.01.004', 'Sueldos por Pagar', 'PASIVO', 'ACREEDORA', 4, '2.4.01', 1],
            ['2.5', 'Pasivos a largo plazo', 'PASIVO', 'ACREEDORA', 2, '2', 0],
            ['2.5.01', 'Prestaciones Sociales', 'PASIVO', 'ACREEDORA', 3, '2.5', 0],
            ['2.5.01.001', 'Anticipo de prestaciones Sociales', 'PASIVO', 'ACREEDORA', 4, '2.5.01', 1],
            ['2.5.02', 'Intereses sobre prestaciones sociales', 'PASIVO', 'ACREEDORA', 3, '2.5', 0],
            ['2.5.02.001', 'Intereses sobre prestaciones sociales', 'PASIVO', 'ACREEDORA', 4, '2.5.02', 1],
            ['2.5.03', 'Proviciones intereses bancarios', 'PASIVO', 'ACREEDORA', 3, '2.5', 0],
            ['2.5.03.001', 'Provicion Intereses', 'PASIVO', 'ACREEDORA', 4, '2.5.03', 1],

            // 3. CAPITAL / PATRIMONIO
            ['3', 'CAPITAL', 'CAPITAL', 'ACREEDORA', 1, null, 0],
            ['3.1', 'CAPITAL SOCIAL', 'CAPITAL', 'ACREEDORA', 2, '3', 0],
            ['3.1.01', 'CAPITAL SOCIAL', 'CAPITAL', 'ACREEDORA', 3, '3.1', 0],
            ['3.1.01.001', 'CAPITAL SOCIAL', 'CAPITAL', 'ACREEDORA', 4, '3.1.01', 1],
            ['3.2', 'RECONVERSION MONETARIA', 'CAPITAL', 'ACREEDORA', 2, '3', 0],
            ['3.2.01', 'Reservado', 'CAPITAL', 'ACREEDORA', 3, '3.2', 0],
            ['3.2.01.001', 'Reserva Legal', 'CAPITAL', 'ACREEDORA', 4, '3.2.01', 1],
            ['3.2.01.002', 'Reserva Estatutaria', 'CAPITAL', 'ACREEDORA', 4, '3.2.01', 1],
            ['3.2.02', 'RESULTADO EJERCICIOS ANTERIORES', 'CAPITAL', 'ACREEDORA', 3, '3.2', 0],
            ['3.2.02.001', 'RESULTADO EJERCICIOS ANTERIORES', 'CAPITAL', 'ACREEDORA', 4, '3.2.02', 1],
            ['3.2.02.002', 'RESULTADO DEL EJERCICIO', 'CAPITAL', 'ACREEDORA', 4, '3.2.02', 1],
            ['3.2.02.003', 'RECONVERSION MONETARIA', 'CAPITAL', 'ACREEDORA', 4, '3.2.02', 1],

            // 4. INGRESOS
            ['4', 'INGRESOS', 'INGRESOS', 'ACREEDORA', 1, null, 0],
            ['4.1', 'Ingresos', 'INGRESOS', 'ACREEDORA', 2, '4', 0],
            ['4.1.01', 'Ingresos', 'INGRESOS', 'ACREEDORA', 3, '4.1', 0],
            ['4.1.01.001', 'Ventas', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1],
            ['4.1.01.002', 'Ingresos por Administración de Alquiler', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1],
            ['4.1.01.003', 'Ingresos por Administración de Condominio', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1],
            ['4.1.01.004', 'Ingresos por Controladores de Inmuebles', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1],
            ['4.1.01.005', 'Ingresos por Mantenimiento de Inmuebles', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1],
            ['4.1.01.006', 'Ingresos por Honorarios Profesionales', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1],
            ['4.1.01.007', 'Ingresos por Cuenta de Terceros', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1],

            // 5. COSTO DE VENTAS
            ['5', 'COSTO DE VENTAS', 'COSTO', 'DEUDORA', 1, null, 0],
            ['5.1', 'Costos', 'COSTO', 'DEUDORA', 2, '5', 0],
            ['5.1.01', 'Costo de Servicios', 'COSTO', 'DEUDORA', 3, '5.1', 0],
            ['5.1.01.001', 'Vigilancia', 'COSTO', 'DEUDORA', 4, '5.1.01', 1],
            ['5.1.01.002', 'Limpieza de Inmuebles', 'COSTO', 'DEUDORA', 4, '5.1.01', 1],
            ['5.1.01.003', 'Mantenimiento de Inmuebles', 'COSTO', 'DEUDORA', 4, '5.1.01', 1],
            ['5.1.01.004', 'Articulos y materiales de Oficina', 'COSTO', 'DEUDORA', 4, '5.1.01', 1],
            ['5.1.01.005', 'Otros costos de ventas', 'COSTO', 'DEUDORA', 4, '5.1.01', 1],

            // 6. GASTOS
            ['6', 'GASTOS', 'GASTOS', 'DEUDORA', 1, null, 0],
            ['6.1', 'Egresos', 'GASTOS', 'DEUDORA', 2, '6', 0],
            ['6.1.01', 'Gastos personal', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.01.001', 'Sueldos Empleados', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.002', 'Salarios Obreros', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.003', 'Sueldos Directores', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.004', 'Comisiones', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.005', 'Bonificaciones', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.006', 'Domingos y Feriados', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.007', 'Horas Extras', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.008', 'Comision Socio', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.009', 'Beneficio de Alimentacion', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.010', 'Bonificacion de Empleados', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.011', 'Vacaciones', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.012', 'Bono Vacacional', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.013', 'Utilidades', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.014', 'Prestaciones Sociales', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.015', 'Intereses Sobre Prestaciones Sociales', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.016', 'Seguro Social obligatorio', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.017', 'Aporte Ince', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.018', 'Ley Politica Habitacional', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.019', 'RETENCION PARO FORZOSO', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.01.020', 'APORTE NO ENTERADOS FAOV', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1],
            ['6.1.02', 'Suministros', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.02.001', 'Articulos y materiales de Oficina', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1],
            ['6.1.02.002', 'Artículos de Limpieza', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1],
            ['6.1.02.003', 'Suministros de Seguridad', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1],
            ['6.1.02.004', 'Suministros y Articulos de Computacion', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1],
            ['6.1.02.005', 'Uniforme', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1],
            ['6.1.02.006', 'Agua Potable', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1],
            ['6.1.02.007', 'Regalos y Obsequios', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1],
            ['6.1.02.008', 'Otros Suministros', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1],
            ['6.1.03', 'Servicios', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.03.001', 'Asesoria Financiera', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.002', 'Gastos Corporativos', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.003', 'Correos y Encomiendas', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.004', 'Reparacion y Mtto Vehiculos', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.005', 'Fletes y Acarreos', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.006', 'Honorarios Profesionales', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.007', 'Gastos legales', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.008', 'Transporte', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.009', 'Servicios de Mensajeria', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.010', 'Servicio de Limpieza Local', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.011', 'Publicidad y Propaganda', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.012', 'Gastos de Instalacion', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.013', 'Mantenimiento y Equipo de Computacion', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.03.014', 'Gastos Camara de Comercio', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1],
            ['6.1.04', 'Gastos Oficina', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.04.001', 'Gastos Oficina', 'GASTOS', 'DEUDORA', 4, '6.1.04', 1],
            ['6.1.04.002', 'Mejoras Propiedad', 'GASTOS', 'DEUDORA', 4, '6.1.04', 1],
            ['6.1.04.003', 'Alquiler Oficina', 'GASTOS', 'DEUDORA', 4, '6.1.04', 1],
            ['6.1.04.004', 'Gasto Condominio', 'GASTOS', 'DEUDORA', 4, '6.1.04', 1],
            ['6.1.04.005', 'Servicio Técnico', 'GASTOS', 'DEUDORA', 4, '6.1.04', 1],
            ['6.1.05', 'Gastos Varios', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.05.001', 'Alimentos y Bebidas', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1],
            ['6.1.05.002', 'Gastos Medicos', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1],
            ['6.1.05.003', 'Trabajos Eventuales', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1],
            ['6.1.05.004', 'Alquileres Equipos', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1],
            ['6.1.05.005', 'Equipos Oficina', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1],
            ['6.1.05.006', 'Gastos de Sistemas Legales', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1],
            ['6.1.05.007', 'Gastos Varios', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1],
            ['6.1.05.008', 'Afiliaciones', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1],
            ['6.1.05.009', 'Personal Contratado', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1],
            ['6.1.05.010', 'Patrocinio', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1],
            ['6.1.06', 'Servicios Publicos', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.06.001', 'Energia Electrica', 'GASTOS', 'DEUDORA', 4, '6.1.06', 1],
            ['6.1.06.002', 'Aseo urbano', 'GASTOS', 'DEUDORA', 4, '6.1.06', 1],
            ['6.1.06.003', 'Saneamiento Ambiental', 'GASTOS', 'DEUDORA', 4, '6.1.06', 1],
            ['6.1.06.004', 'Servicio de Internet', 'GASTOS', 'DEUDORA', 4, '6.1.06', 1],
            ['6.1.06.005', 'Telefonía', 'GASTOS', 'DEUDORA', 4, '6.1.06', 1],
            ['6.1.07', 'Impuestos', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.07.001', 'Patente de Industria y Comercio (IAE)', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1],
            ['6.1.07.002', 'Gasto de Impuesto Sobre la Renta', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1],
            ['6.1.07.003', 'Otros Impuestos', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1],
            ['6.1.07.004', 'Publicidad', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1],
            ['6.1.07.005', 'Tasas y contribuciones', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1],
            ['6.1.07.006', 'Prorrateo IVA', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1],
            ['6.1.07.007', 'Gasto de IGTF', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1],
            ['6.1.08', 'Otros gastos', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.08.001', 'Iva no Deducible', 'GASTOS', 'DEUDORA', 4, '6.1.08', 1],
            ['6.1.08.002', 'Donaciones', 'GASTOS', 'DEUDORA', 4, '6.1.08', 1],
            ['6.1.08.003', 'Gastos de Viaje', 'GASTOS', 'DEUDORA', 4, '6.1.08', 1],
            ['6.1.09', 'Comisiones e intereses', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.09.001', 'Comisiones Bancarias', 'GASTOS', 'DEUDORA', 4, '6.1.09', 1],
            ['6.1.09.002', 'Intereses Pagare', 'GASTOS', 'DEUDORA', 4, '6.1.09', 1],
            ['6.1.09.003', 'Intereses a Terceros', 'GASTOS', 'DEUDORA', 4, '6.1.09', 1],
            ['6.1.10', 'Gastos no deducibles', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.10.001', 'Gastos no Deducibles', 'GASTOS', 'DEUDORA', 4, '6.1.10', 1],
            ['6.1.10.002', 'Intereses por Mora', 'GASTOS', 'DEUDORA', 4, '6.1.10', 1],
            ['6.1.11', 'Gastos de depreciación / amortización', 'GASTOS', 'DEUDORA', 3, '6.1', 0],
            ['6.1.11.001', 'Gastos de Depreciacion Maquinaria', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1],
            ['6.1.11.002', 'Depreciacion Acumulada Instalaciones', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1],
            ['6.1.11.003', 'Gastos de Depreciacion Mobiliarios', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1],
            ['6.1.11.004', 'Gastos de Depreciacion Equipo', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1],
            ['6.1.11.005', 'Gastos de Depreciacion Vehiculos', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1],
            ['6.1.11.006', 'Amortizacion Sistemas de Computacion', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1],
            ['6.1.11.007', 'Perdida de Venta Activo', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1],
        ];
    }
}
