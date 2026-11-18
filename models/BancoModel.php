<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Modelo de Enlace Bancario y Conciliación Bancaria (Multi-Tenant)
 */
final class BancoModel
{
    public const BANCOS_VENEZOLANOS = [
        '0134' => 'Banesco Banco Universal',
        '0102' => 'Banco de Venezuela',
        '0105' => 'Banco Mercantil',
        '0108' => 'BBVA Provincial',
        '0191' => 'Banco Nacional de Crédito (BNC)',
        '0172' => 'Bancamiga Banco Universal',
        '0174' => 'Banplus Banco Universal',
        '0151' => 'BFC Banco Fondo Común',
        '0115' => 'Banco Exterior',
        '0104' => 'Venezolano de Crédito',
    ];

    public const TIPOS_CUENTA = [
        'Corriente',
        'Ahorro',
        'Custodia Divisas',
    ];

    public const TIPOS_MOVIMIENTO = [
        'Nota de Crédito / Depósito',
        'Transferencia Recibida',
        'Nota de Débito / Comisión',
        'Transferencia Emitida',
        'Pago Móvil',
        'Cheque',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->asegurarTablas();
    }

    /**
     * Garantiza la existencia de las tablas bancarias en bases de datos ya instaladas
     */
    private function asegurarTablas(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `cuentas_bancarias` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `empresa_id` INT UNSIGNED NOT NULL,
              `banco_nombre` VARCHAR(100) NOT NULL,
              `codigo_sudeban` VARCHAR(10) NOT NULL,
              `numero_cuenta` CHAR(20) NOT NULL,
              `tipo_cuenta` ENUM('Corriente', 'Ahorro', 'Custodia Divisas') NOT NULL DEFAULT 'Corriente',
              `moneda` ENUM('VES', 'USD') NOT NULL DEFAULT 'VES',
              `cuenta_contable_id` INT UNSIGNED NULL DEFAULT NULL,
              `saldo_inicial` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `saldo_actual` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `estatus` ENUM('Activa', 'Inactiva') NOT NULL DEFAULT 'Activa',
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_cuenta_bancaria_emp` (`empresa_id`, `numero_cuenta`),
              KEY `idx_cb_empresa_estatus` (`empresa_id`, `estatus`),
              KEY `idx_cb_cuenta_contable` (`cuenta_contable_id`),
              CONSTRAINT `fk_cb_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk_cb_plan_cuentas` FOREIGN KEY (`cuenta_contable_id`) REFERENCES `plan_cuentas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `movimientos_bancarios` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `empresa_id` INT UNSIGNED NOT NULL,
              `cuenta_bancaria_id` INT UNSIGNED NOT NULL,
              `fecha_operacion` DATE NOT NULL,
              `referencia` VARCHAR(60) NOT NULL,
              `tipo_movimiento` ENUM(
                'Nota de Crédito / Depósito',
                'Transferencia Recibida',
                'Nota de Débito / Comisión',
                'Transferencia Emitida',
                'Pago Móvil',
                'Cheque'
              ) NOT NULL DEFAULT 'Transferencia Recibida',
              `descripcion` VARCHAR(255) NOT NULL,
              `monto_debe` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `monto_haber` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `conciliado` TINYINT(1) NOT NULL DEFAULT 0,
              `asiento_id` INT UNSIGNED NULL DEFAULT NULL,
              `periodo_conciliacion` CHAR(7) NOT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_mb_emp_cuenta_periodo` (`empresa_id`, `cuenta_bancaria_id`, `periodo_conciliacion`),
              KEY `idx_mb_dup` (`cuenta_bancaria_id`, `fecha_operacion`, `referencia`),
              KEY `idx_mb_asiento` (`asiento_id`),
              CONSTRAINT `fk_mb_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk_mb_cuenta` FOREIGN KEY (`cuenta_bancaria_id`) REFERENCES `cuentas_bancarias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk_mb_asiento` FOREIGN KEY (`asiento_id`) REFERENCES `asientos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `conciliaciones_bancarias` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `empresa_id` INT UNSIGNED NOT NULL,
              `cuenta_bancaria_id` INT UNSIGNED NOT NULL,
              `periodo` CHAR(7) NOT NULL,
              `saldo_segun_banco` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `saldo_segun_libros` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `depositos_en_transito` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `cheques_en_transito` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `notas_debito_no_registradas` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `notas_credito_no_registradas` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `diferencia` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `estatus` ENUM('Borrador', 'Conciliada') NOT NULL DEFAULT 'Borrador',
              `fecha_cierre` DATETIME NULL DEFAULT NULL,
              `observaciones` TEXT NULL DEFAULT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_conc_emp_cuenta_per` (`empresa_id`, `cuenta_bancaria_id`, `periodo`),
              KEY `idx_conc_cuenta` (`cuenta_bancaria_id`),
              CONSTRAINT `fk_conc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk_conc_cuenta` FOREIGN KEY (`cuenta_bancaria_id`) REFERENCES `cuentas_bancarias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // =========================================================================
    // 1. DIRECTORIO DE CUENTAS BANCARIAS
    // =========================================================================

    public function listarCuentas(int $empresaId, bool $soloActivas = false): array
    {
        $sql = 'SELECT cb.*,
                       pc.codigo AS cuenta_contable_codigo,
                       pc.nombre AS cuenta_contable_nombre
                FROM cuentas_bancarias cb
                LEFT JOIN plan_cuentas pc
                  ON pc.id = cb.cuenta_contable_id
                 AND pc.empresa_id = cb.empresa_id
                WHERE cb.empresa_id = :empresa_id';

        if ($soloActivas) {
            $sql .= " AND cb.estatus = 'Activa'";
        }

        $sql .= " ORDER BY cb.estatus ASC, cb.moneda ASC, cb.banco_nombre ASC, cb.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    public function obtenerCuentaPorId(int $id, int $empresaId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT cb.*,
                    pc.codigo AS cuenta_contable_codigo,
                    pc.nombre AS cuenta_contable_nombre
             FROM cuentas_bancarias cb
             LEFT JOIN plan_cuentas pc
               ON pc.id = cb.cuenta_contable_id
              AND pc.empresa_id = cb.empresa_id
             WHERE cb.id = :id AND cb.empresa_id = :empresa_id
             LIMIT 1'
        );
        $stmt->execute([
            ':id'         => $id,
            ':empresa_id' => $empresaId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function guardarCuenta(int $empresaId, array $datos): int
    {
        $id               = (int)($datos['id'] ?? 0);
        $codigoSudeban    = preg_replace('/\D/', '', (string)($datos['codigo_sudeban'] ?? ''));
        $bancoNombre      = trim((string)($datos['banco_nombre'] ?? ''));
        $numeroCuenta     = preg_replace('/\D/', '', (string)($datos['numero_cuenta'] ?? ''));
        $tipoCuenta       = (string)($datos['tipo_cuenta'] ?? 'Corriente');
        $moneda           = (string)($datos['moneda'] ?? 'VES');
        $cuentaContableId = (int)($datos['cuenta_contable_id'] ?? 0);
        $saldoInicial     = round((float)($datos['saldo_inicial'] ?? 0), 2);
        $estatus          = (string)($datos['estatus'] ?? 'Activa');

        if ($codigoSudeban === '' || strlen($codigoSudeban) !== 4) {
            throw new RuntimeException('El código SUDEBAN debe tener exactamente 4 dígitos (ej. 0134).');
        }

        if ($bancoNombre === '') {
            $bancoNombre = self::BANCOS_VENEZOLANOS[$codigoSudeban] ?? '';
        }
        if ($bancoNombre === '') {
            throw new RuntimeException('Debe indicar el nombre de la institución bancaria.');
        }

        if (!preg_match('/^\d{20}$/', $numeroCuenta)) {
            throw new RuntimeException('El número de cuenta bancaria debe contener exactamente 20 dígitos numéricos.');
        }

        if (!in_array($tipoCuenta, self::TIPOS_CUENTA, true)) {
            throw new RuntimeException('El tipo de cuenta seleccionado no es válido.');
        }

        if (!in_array($moneda, ['VES', 'USD'], true)) {
            throw new RuntimeException('La moneda debe ser VES o USD.');
        }

        if (!in_array($estatus, ['Activa', 'Inactiva'], true)) {
            $estatus = 'Activa';
        }

        $cuentaContableValida = null;
        if ($cuentaContableId > 0) {
            $stmtCta = $this->db->prepare(
                'SELECT id FROM plan_cuentas
                 WHERE id = :id AND empresa_id = :empresa_id AND acepta_movimiento = 1
                 LIMIT 1'
            );
            $stmtCta->execute([
                ':id'         => $cuentaContableId,
                ':empresa_id' => $empresaId,
            ]);
            if (!$stmtCta->fetchColumn()) {
                throw new RuntimeException('La cuenta contable seleccionada no pertenece a la empresa o no acepta movimiento.');
            }
            $cuentaContableValida = $cuentaContableId;
        }

        // Verificar duplicado de número de cuenta dentro de la misma empresa
        $stmtDup = $this->db->prepare(
            'SELECT id FROM cuentas_bancarias
             WHERE empresa_id = :empresa_id AND numero_cuenta = :numero_cuenta AND id <> :id
             LIMIT 1'
        );
        $stmtDup->execute([
            ':empresa_id'    => $empresaId,
            ':numero_cuenta' => $numeroCuenta,
            ':id'            => $id,
        ]);
        if ($stmtDup->fetchColumn()) {
            throw new RuntimeException('Ya existe una cuenta bancaria registrada con ese número de 20 dígitos.');
        }

        if ($id > 0) {
            $actual = $this->obtenerCuentaPorId($id, $empresaId);
            if (!$actual) {
                throw new RuntimeException('La cuenta bancaria solicitada no existe o no pertenece a la empresa activa.');
            }

            $deltaSaldoInicial = round($saldoInicial - (float)$actual['saldo_inicial'], 2);
            $nuevoSaldoActual  = round((float)$actual['saldo_actual'] + $deltaSaldoInicial, 2);

            $stmt = $this->db->prepare(
                'UPDATE cuentas_bancarias
                 SET banco_nombre = :banco_nombre,
                     codigo_sudeban = :codigo_sudeban,
                     numero_cuenta = :numero_cuenta,
                     tipo_cuenta = :tipo_cuenta,
                     moneda = :moneda,
                     cuenta_contable_id = :cuenta_contable_id,
                     saldo_inicial = :saldo_inicial,
                     saldo_actual = :saldo_actual,
                     estatus = :estatus
                 WHERE id = :id AND empresa_id = :empresa_id'
            );
            $stmt->execute([
                ':banco_nombre'       => $bancoNombre,
                ':codigo_sudeban'     => $codigoSudeban,
                ':numero_cuenta'      => $numeroCuenta,
                ':tipo_cuenta'        => $tipoCuenta,
                ':moneda'             => $moneda,
                ':cuenta_contable_id' => $cuentaContableValida,
                ':saldo_inicial'      => $saldoInicial,
                ':saldo_actual'       => $nuevoSaldoActual,
                ':estatus'            => $estatus,
                ':id'                 => $id,
                ':empresa_id'         => $empresaId,
            ]);
            return $id;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO cuentas_bancarias
                (empresa_id, banco_nombre, codigo_sudeban, numero_cuenta, tipo_cuenta, moneda,
                 cuenta_contable_id, saldo_inicial, saldo_actual, estatus)
             VALUES
                (:empresa_id, :banco_nombre, :codigo_sudeban, :numero_cuenta, :tipo_cuenta, :moneda,
                 :cuenta_contable_id, :saldo_inicial, :saldo_actual, :estatus)'
        );
        $stmt->execute([
            ':empresa_id'         => $empresaId,
            ':banco_nombre'       => $bancoNombre,
            ':codigo_sudeban'     => $codigoSudeban,
            ':numero_cuenta'      => $numeroCuenta,
            ':tipo_cuenta'        => $tipoCuenta,
            ':moneda'             => $moneda,
            ':cuenta_contable_id' => $cuentaContableValida,
            ':saldo_inicial'      => $saldoInicial,
            ':saldo_actual'       => $saldoInicial,
            ':estatus'            => $estatus,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function cambiarEstatusCuenta(int $id, int $empresaId, string $estatus): void
    {
        if (!in_array($estatus, ['Activa', 'Inactiva'], true)) {
            throw new RuntimeException('Estatus bancario no válido.');
        }

        $stmt = $this->db->prepare(
            'UPDATE cuentas_bancarias
             SET estatus = :estatus
             WHERE id = :id AND empresa_id = :empresa_id'
        );
        $stmt->execute([
            ':estatus'    => $estatus,
            ':id'         => $id,
            ':empresa_id' => $empresaId,
        ]);

        if ($stmt->rowCount() === 0 && !$this->obtenerCuentaPorId($id, $empresaId)) {
            throw new RuntimeException('Cuenta bancaria no encontrada para la empresa activa.');
        }
    }

    /**
     * Elimina una cuenta bancaria si no posee movimientos contabilizados ni conciliaciones cerradas
     */
    public function eliminarCuenta(int $id, int $empresaId): void
    {
        $cuenta = $this->obtenerCuentaPorId($id, $empresaId);
        if (!$cuenta) {
            throw new RuntimeException('Cuenta bancaria no encontrada para la empresa activa.');
        }

        $stmtAsientos = $this->db->prepare(
            'SELECT COUNT(*) FROM movimientos_bancarios
             WHERE cuenta_bancaria_id = :id AND empresa_id = :empresa_id AND asiento_id IS NOT NULL'
        );
        $stmtAsientos->execute([
            ':id'         => $id,
            ':empresa_id' => $empresaId,
        ]);
        if ((int)$stmtAsientos->fetchColumn() > 0) {
            throw new RuntimeException('No se puede eliminar esta cuenta porque tiene movimientos enlazados a asientos contables. Puede inactivarla en su lugar.');
        }

        $stmtConc = $this->db->prepare(
            "SELECT COUNT(*) FROM conciliaciones_bancarias
             WHERE cuenta_bancaria_id = :id AND empresa_id = :empresa_id AND estatus = 'Conciliada'"
        );
        $stmtConc->execute([
            ':id'         => $id,
            ':empresa_id' => $empresaId,
        ]);
        if ((int)$stmtConc->fetchColumn() > 0) {
            throw new RuntimeException('No se puede eliminar esta cuenta porque posee conciliaciones mensuales cerradas. Puede inactivarla en su lugar.');
        }

        $stmtDel = $this->db->prepare(
            'DELETE FROM cuentas_bancarias WHERE id = :id AND empresa_id = :empresa_id'
        );
        $stmtDel->execute([
            ':id'         => $id,
            ':empresa_id' => $empresaId,
        ]);
    }

    /**
     * Resumen de saldos bancarios para el Dashboard Ejecutivo
     */
    public function obtenerSaldoTotalBancos(int $empresaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total_cuentas,
                COALESCE(SUM(CASE WHEN moneda = 'VES' THEN saldo_actual ELSE 0 END), 0) AS saldo_ves,
                COALESCE(SUM(CASE WHEN moneda = 'USD' THEN saldo_actual ELSE 0 END), 0) AS saldo_usd
             FROM cuentas_bancarias
             WHERE empresa_id = :empresa_id AND estatus = 'Activa'"
        );
        $stmt->execute([':empresa_id' => $empresaId]);
        $row = $stmt->fetch();

        return [
            'total_cuentas' => (int)($row['total_cuentas'] ?? 0),
            'saldo_ves'     => (float)($row['saldo_ves'] ?? 0.0),
            'saldo_usd'     => (float)($row['saldo_usd'] ?? 0.0),
        ];
    }

    // =========================================================================
    // 2. MOVIMIENTOS BANCARIOS, IMPORTACIÓN CSV Y CONTABILIZACIÓN
    // =========================================================================

    public function listarMovimientos(int $empresaId, int $cuentaBancariaId, ?string $periodo = null): array
    {
        $sql = 'SELECT mb.*,
                       a.numero_asiento
                FROM movimientos_bancarios mb
                LEFT JOIN asientos a
                  ON a.id = mb.asiento_id
                 AND a.empresa_id = mb.empresa_id
                WHERE mb.empresa_id = :empresa_id
                  AND mb.cuenta_bancaria_id = :cuenta_id';

        $params = [
            ':empresa_id' => $empresaId,
            ':cuenta_id'  => $cuentaBancariaId,
        ];

        if ($periodo !== null && preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            $sql .= ' AND mb.periodo_conciliacion = :periodo';
            $params[':periodo'] = $periodo;
        }

        $sql .= ' ORDER BY mb.fecha_operacion DESC, mb.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function registrarMovimiento(int $empresaId, array $datos): int
    {
        $cuentaId       = (int)($datos['cuenta_bancaria_id'] ?? 0);
        $fecha          = trim((string)($datos['fecha_operacion'] ?? ''));
        $referencia     = trim((string)($datos['referencia'] ?? ''));
        $tipoMovimiento = (string)($datos['tipo_movimiento'] ?? 'Transferencia Recibida');
        $descripcion    = trim((string)($datos['descripcion'] ?? ''));
        $montoDebe      = round((float)($datos['monto_debe'] ?? 0), 2);
        $montoHaber     = round((float)($datos['monto_haber'] ?? 0), 2);

        $cuenta = $this->obtenerCuentaPorId($cuentaId, $empresaId);
        if (!$cuenta) {
            throw new RuntimeException('Debe seleccionar una cuenta bancaria válida.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new RuntimeException('La fecha de operación debe tener formato AAAA-MM-DD.');
        }

        if ($referencia === '') {
            throw new RuntimeException('El número de referencia bancaria es obligatorio.');
        }

        if (!in_array($tipoMovimiento, self::TIPOS_MOVIMIENTO, true)) {
            throw new RuntimeException('Tipo de movimiento bancario no válido.');
        }

        if ($descripcion === '') {
            throw new RuntimeException('La descripción del movimiento es obligatoria.');
        }

        if ($montoDebe < 0 || $montoHaber < 0) {
            throw new RuntimeException('Los montos no pueden ser negativos.');
        }

        if (($montoDebe == 0.0 && $montoHaber == 0.0) || ($montoDebe > 0.0 && $montoHaber > 0.0)) {
            throw new RuntimeException('Debe indicar un monto válido en Entrada (Debe) o en Salida (Haber), pero no en ambos.');
        }

        $periodo = substr($fecha, 0, 7);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO movimientos_bancarios
                    (empresa_id, cuenta_bancaria_id, fecha_operacion, referencia, tipo_movimiento,
                     descripcion, monto_debe, monto_haber, conciliado, asiento_id, periodo_conciliacion)
                 VALUES
                    (:empresa_id, :cuenta_bancaria_id, :fecha_operacion, :referencia, :tipo_movimiento,
                     :descripcion, :monto_debe, :monto_haber, 0, NULL, :periodo)'
            );
            $stmt->execute([
                ':empresa_id'         => $empresaId,
                ':cuenta_bancaria_id' => $cuentaId,
                ':fecha_operacion'    => $fecha,
                ':referencia'         => $referencia,
                ':tipo_movimiento'    => $tipoMovimiento,
                ':descripcion'        => $descripcion,
                ':monto_debe'         => $montoDebe,
                ':monto_haber'        => $montoHaber,
                ':periodo'            => $periodo,
            ]);

            $movId = (int)$this->db->lastInsertId();

            $delta = round($montoDebe - $montoHaber, 2);
            $stmtUpd = $this->db->prepare(
                'UPDATE cuentas_bancarias
                 SET saldo_actual = saldo_actual + :delta
                 WHERE id = :id AND empresa_id = :empresa_id'
            );
            $stmtUpd->execute([
                ':delta'      => $delta,
                ':id'         => $cuentaId,
                ':empresa_id' => $empresaId,
            ]);

            $this->db->commit();
            return $movId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function existeMovimientoDuplicado(
        int $cuentaBancariaId,
        string $fecha,
        string $referencia,
        float $montoDebe,
        float $montoHaber
    ): bool {
        $stmt = $this->db->prepare(
            'SELECT id FROM movimientos_bancarios
             WHERE cuenta_bancaria_id = :cuenta_id
               AND fecha_operacion = :fecha
               AND referencia = :referencia
               AND monto_debe = :debe
               AND monto_haber = :haber
             LIMIT 1'
        );
        $stmt->execute([
            ':cuenta_id'  => $cuentaBancariaId,
            ':fecha'      => $fecha,
            ':referencia' => $referencia,
            ':debe'       => round($montoDebe, 2),
            ':haber'      => round($montoHaber, 2),
        ]);
        return (bool)$stmt->fetchColumn();
    }

    public function importarMovimientosCsv(int $empresaId, int $cuentaBancariaId, array $filas): array
    {
        $cuenta = $this->obtenerCuentaPorId($cuentaBancariaId, $empresaId);
        if (!$cuenta) {
            throw new RuntimeException('La cuenta bancaria destino no existe o no pertenece a la empresa activa.');
        }

        if (empty($filas)) {
            throw new RuntimeException('No hay filas válidas para importar.');
        }

        $insertados = 0;
        $duplicados = 0;
        $deltaTotal = 0.0;

        $this->db->beginTransaction();
        try {
            $stmtInsert = $this->db->prepare(
                'INSERT INTO movimientos_bancarios
                    (empresa_id, cuenta_bancaria_id, fecha_operacion, referencia, tipo_movimiento,
                     descripcion, monto_debe, monto_haber, conciliado, asiento_id, periodo_conciliacion)
                 VALUES
                    (:empresa_id, :cuenta_bancaria_id, :fecha_operacion, :referencia, :tipo_movimiento,
                     :descripcion, :monto_debe, :monto_haber, 0, NULL, :periodo)'
            );

            foreach ($filas as $fila) {
                $fecha       = trim((string)($fila['fecha_operacion'] ?? ''));
                $referencia  = trim((string)($fila['referencia'] ?? ''));
                $descripcion = trim((string)($fila['descripcion'] ?? ''));
                $montoDebe   = round((float)($fila['monto_debe'] ?? 0), 2);
                $montoHaber  = round((float)($fila['monto_haber'] ?? 0), 2);

                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $referencia === '') {
                    continue;
                }
                if (($montoDebe <= 0.0 && $montoHaber <= 0.0) || ($montoDebe > 0.0 && $montoHaber > 0.0)) {
                    continue;
                }

                if ($this->existeMovimientoDuplicado($cuentaBancariaId, $fecha, $referencia, $montoDebe, $montoHaber)) {
                    $duplicados++;
                    continue;
                }

                $tipoMov = $this->inferirTipoMovimiento($descripcion, $montoDebe > 0);
                $periodo = substr($fecha, 0, 7);

                $stmtInsert->execute([
                    ':empresa_id'         => $empresaId,
                    ':cuenta_bancaria_id' => $cuentaBancariaId,
                    ':fecha_operacion'    => $fecha,
                    ':referencia'         => mb_substr($referencia, 0, 60),
                    ':tipo_movimiento'    => $tipoMov,
                    ':descripcion'        => mb_substr($descripcion !== '' ? $descripcion : 'Movimiento importado CSV', 0, 255),
                    ':monto_debe'         => $montoDebe,
                    ':monto_haber'        => $montoHaber,
                    ':periodo'            => $periodo,
                ]);

                $insertados++;
                $deltaTotal = round($deltaTotal + $montoDebe - $montoHaber, 2);
            }

            if ($insertados > 0 && $deltaTotal != 0.0) {
                $stmtUpd = $this->db->prepare(
                    'UPDATE cuentas_bancarias
                     SET saldo_actual = saldo_actual + :delta
                     WHERE id = :id AND empresa_id = :empresa_id'
                );
                $stmtUpd->execute([
                    ':delta'      => $deltaTotal,
                    ':id'         => $cuentaBancariaId,
                    ':empresa_id' => $empresaId,
                ]);
            }

            $this->db->commit();
            return [
                'insertados' => $insertados,
                'duplicados' => $duplicados,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function inferirTipoMovimiento(string $descripcion, bool $esEntrada): string
    {
        $descUpper = mb_strtoupper($descripcion);
        if (str_contains($descUpper, 'PAGO MOVIL') || str_contains($descUpper, 'PAGO MÓVIL') || str_contains($descUpper, 'PM ')) {
            return 'Pago Móvil';
        }
        if (str_contains($descUpper, 'CHEQUE') || str_contains($descUpper, 'CHQ')) {
            return 'Cheque';
        }
        if ($esEntrada) {
            if (str_contains($descUpper, 'TRANSF')) {
                return 'Transferencia Recibida';
            }
            return 'Nota de Crédito / Depósito';
        }
        if (str_contains($descUpper, 'COMISION') || str_contains($descUpper, 'COMISIÓN') || str_contains($descUpper, 'ND') || str_contains($descUpper, 'ITF') || str_contains($descUpper, 'IGTF')) {
            return 'Nota de Débito / Comisión';
        }
        return 'Transferencia Emitida';
    }

    /**
     * Genera el asiento contable en el Libro Diario desde un movimiento bancario
     */
    public function contabilizarMovimiento(
        int $empresaId,
        int $usuarioId,
        int $movimientoId,
        int $contrapartidaId,
        string $glosaCustom = ''
    ): array {
        $stmt = $this->db->prepare(
            'SELECT mb.*, cb.cuenta_contable_id, cb.banco_nombre, cb.numero_cuenta
             FROM movimientos_bancarios mb
             INNER JOIN cuentas_bancarias cb
                ON cb.id = mb.cuenta_bancaria_id
               AND cb.empresa_id = mb.empresa_id
             WHERE mb.id = :id AND mb.empresa_id = :empresa_id
             LIMIT 1'
        );
        $stmt->execute([
            ':id'         => $movimientoId,
            ':empresa_id' => $empresaId,
        ]);
        $mov = $stmt->fetch();

        if (!$mov) {
            throw new RuntimeException('El movimiento bancario no existe o no pertenece a la empresa activa.');
        }

        if (!empty($mov['asiento_id'])) {
            throw new RuntimeException('Este movimiento bancario ya fue contabilizado previamente.');
        }

        $cuentaBancoContableId = (int)($mov['cuenta_contable_id'] ?? 0);
        if ($cuentaBancoContableId <= 0) {
            throw new RuntimeException('La cuenta bancaria no tiene una Cuenta Contable del Plan de Cuentas asociada. Edite la cuenta bancaria para enlazarla.');
        }

        if ($contrapartidaId <= 0 || $contrapartidaId === $cuentaBancoContableId) {
            throw new RuntimeException('Debe seleccionar una cuenta de contrapartida válida distinta a la cuenta del banco.');
        }

        $montoDebe  = (float)$mov['monto_debe'];
        $montoHaber = (float)$mov['monto_haber'];
        $monto      = $montoDebe > 0 ? $montoDebe : $montoHaber;

        $concepto = trim($glosaCustom) !== ''
            ? trim($glosaCustom)
            : sprintf('%s (%s) — Ref. %s: %s', $mov['tipo_movimiento'], $mov['banco_nombre'], $mov['referencia'], $mov['descripcion']);

        if ($montoDebe > 0) {
            // Entrada al banco: Debe al Banco, Haber a la Contrapartida
            $renglones = [
                [
                    'cuenta_id'   => $cuentaBancoContableId,
                    'descripcion' => $concepto,
                    'debe'        => $monto,
                    'haber'       => 0.00,
                ],
                [
                    'cuenta_id'   => $contrapartidaId,
                    'descripcion' => $concepto,
                    'debe'        => 0.00,
                    'haber'       => $monto,
                ],
            ];
        } else {
            // Salida del banco: Debe a la Contrapartida, Haber al Banco
            $renglones = [
                [
                    'cuenta_id'   => $contrapartidaId,
                    'descripcion' => $concepto,
                    'debe'        => $monto,
                    'haber'       => 0.00,
                ],
                [
                    'cuenta_id'   => $cuentaBancoContableId,
                    'descripcion' => $concepto,
                    'debe'        => 0.00,
                    'haber'       => $monto,
                ],
            ];
        }

        $asientoModel = new AsientoModel();
        $asientoCreado = $asientoModel->crearAsientoPartidaDoble($empresaId, $usuarioId, [
            'fecha'      => $mov['fecha_operacion'],
            'concepto'   => $concepto,
            'referencia' => $mov['referencia'],
            'origen'     => 'BANCOS',
        ], $renglones);

        $stmtLink = $this->db->prepare(
            'UPDATE movimientos_bancarios
             SET asiento_id = :asiento_id
             WHERE id = :id AND empresa_id = :empresa_id'
        );
        $stmtLink->execute([
            ':asiento_id' => (int)$asientoCreado['id'],
            ':id'         => $movimientoId,
            ':empresa_id' => $empresaId,
        ]);

        return $asientoCreado;
    }

    // =========================================================================
    // 3. MESA DE CONCILIACIÓN BANCARIA
    // =========================================================================

    public function obtenerMovimientosLibros(int $empresaId, ?int $cuentaContableId, string $periodo): array
    {
        if (!$cuentaContableId || $cuentaContableId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            return [
                'movimientos'  => [],
                'saldo_libros' => 0.00,
            ];
        }

        $fechaInicio = $periodo . '-01';
        $fechaFin    = date('Y-m-t', strtotime($fechaInicio));

        $stmtMovs = $this->db->prepare(
            "SELECT a.id AS asiento_id,
                    a.numero_asiento,
                    a.fecha,
                    a.referencia,
                    a.concepto,
                    ad.descripcion AS descripcion_linea,
                    ad.debe,
                    ad.haber
             FROM asiento_detalles ad
             INNER JOIN asientos a
                ON a.id = ad.asiento_id
               AND a.empresa_id = ad.empresa_id
             WHERE ad.empresa_id = :empresa_id
               AND ad.cuenta_id = :cuenta_id
               AND a.estado = 'PUBLICADO'
               AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
             ORDER BY a.fecha ASC, a.id ASC, ad.id ASC"
        );
        $stmtMovs->execute([
            ':empresa_id'   => $empresaId,
            ':cuenta_id'    => $cuentaContableId,
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin'    => $fechaFin,
        ]);
        $movimientos = $stmtMovs->fetchAll();

        $stmtSaldo = $this->db->prepare(
            "SELECT COALESCE(SUM(ad.debe - ad.haber), 0) AS saldo
             FROM asiento_detalles ad
             INNER JOIN asientos a
                ON a.id = ad.asiento_id
               AND a.empresa_id = ad.empresa_id
             WHERE ad.empresa_id = :empresa_id
               AND ad.cuenta_id = :cuenta_id
               AND a.estado = 'PUBLICADO'
               AND a.fecha <= :fecha_fin"
        );
        $stmtSaldo->execute([
            ':empresa_id' => $empresaId,
            ':cuenta_id'  => $cuentaContableId,
            ':fecha_fin'  => $fechaFin,
        ]);
        $saldoLibros = round((float)$stmtSaldo->fetchColumn(), 2);

        return [
            'movimientos'  => $movimientos,
            'saldo_libros' => $saldoLibros,
        ];
    }

    public function calcularSaldoBancoAlCierrePeriodo(int $empresaId, int $cuentaBancariaId, string $periodo): float
    {
        $cuenta = $this->obtenerCuentaPorId($cuentaBancariaId, $empresaId);
        if (!$cuenta) {
            return 0.0;
        }

        $fechaFin = date('Y-m-t', strtotime($periodo . '-01'));
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(monto_debe - monto_haber), 0) AS neto
             FROM movimientos_bancarios
             WHERE empresa_id = :empresa_id
               AND cuenta_bancaria_id = :cuenta_id
               AND fecha_operacion <= :fecha_fin'
        );
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':cuenta_id'  => $cuentaBancariaId,
            ':fecha_fin'  => $fechaFin,
        ]);
        $neto = (float)$stmt->fetchColumn();

        return round((float)$cuenta['saldo_inicial'] + $neto, 2);
    }

    public function toggleConciliadoMovimiento(int $empresaId, int $movimientoId, int $conciliado): void
    {
        $valor = $conciliado === 1 ? 1 : 0;
        $stmt = $this->db->prepare(
            'UPDATE movimientos_bancarios
             SET conciliado = :conciliado
             WHERE id = :id AND empresa_id = :empresa_id'
        );
        $stmt->execute([
            ':conciliado' => $valor,
            ':id'         => $movimientoId,
            ':empresa_id' => $empresaId,
        ]);
    }

    /**
     * Cruce automático por coincidencia de monto exacto + referencia o fecha cercana (±3 días)
     */
    public function autoConciliar(int $empresaId, int $cuentaBancariaId, string $periodo): int
    {
        $cuenta = $this->obtenerCuentaPorId($cuentaBancariaId, $empresaId);
        if (!$cuenta) {
            throw new RuntimeException('Cuenta bancaria no encontrada.');
        }

        $movsBanco = $this->listarMovimientos($empresaId, $cuentaBancariaId, $periodo);
        $librosData = $this->obtenerMovimientosLibros(
            $empresaId,
            !empty($cuenta['cuenta_contable_id']) ? (int)$cuenta['cuenta_contable_id'] : null,
            $periodo
        );
        $movsLibro = $librosData['movimientos'];

        if (empty($movsBanco) || empty($movsLibro)) {
            return 0;
        }

        $usadosLibro = [];
        $idsConciliados = [];

        foreach ($movsBanco as $mb) {
            if ((int)$mb['conciliado'] === 1) {
                continue;
            }

            $debeB  = round((float)$mb['monto_debe'], 2);
            $haberB = round((float)$mb['monto_haber'], 2);
            $refB   = mb_strtoupper(trim((string)$mb['referencia']));
            $tsB    = strtotime((string)$mb['fecha_operacion']);

            foreach ($movsLibro as $idx => $ml) {
                if (isset($usadosLibro[$idx])) {
                    continue;
                }

                $debeL  = round((float)$ml['debe'], 2);
                $haberL = round((float)$ml['haber'], 2);

                if ($debeB !== $debeL || $haberB !== $haberL) {
                    continue;
                }

                $refL = mb_strtoupper(trim((string)($ml['referencia'] ?? '')));
                $tsL  = strtotime((string)$ml['fecha']);
                $diffDias = abs($tsB - $tsL) / 86400;

                $coincideRef = ($refB !== '' && $refL !== '' && (str_contains($refL, $refB) || str_contains($refB, $refL)));
                $coincideAsientoEnlazado = (!empty($mb['asiento_id']) && (int)$mb['asiento_id'] === (int)$ml['asiento_id']);

                if ($coincideAsientoEnlazado || $coincideRef || $diffDias <= 3) {
                    $idsConciliados[]  = (int)$mb['id'];
                    $usadosLibro[$idx] = true;
                    break;
                }
            }
        }

        if (empty($idsConciliados)) {
            return 0;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE movimientos_bancarios
                 SET conciliado = 1
                 WHERE id = :id AND empresa_id = :empresa_id'
            );
            foreach ($idsConciliados as $movId) {
                $stmt->execute([
                    ':id'         => $movId,
                    ':empresa_id' => $empresaId,
                ]);
            }
            $this->db->commit();
            return count($idsConciliados);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function obtenerConciliacion(int $empresaId, int $cuentaBancariaId, string $periodo): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM conciliaciones_bancarias
             WHERE empresa_id = :empresa_id
               AND cuenta_bancaria_id = :cuenta_id
               AND periodo = :periodo
             LIMIT 1'
        );
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':cuenta_id'  => $cuentaBancariaId,
            ':periodo'    => $periodo,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function guardarConciliacion(int $empresaId, array $datos): int
    {
        $cuentaId        = (int)($datos['cuenta_bancaria_id'] ?? 0);
        $periodo         = trim((string)($datos['periodo'] ?? ''));
        $saldoBanco      = round((float)($datos['saldo_segun_banco'] ?? 0), 2);
        $saldoLibros     = round((float)($datos['saldo_segun_libros'] ?? 0), 2);
        $depTransito     = round((float)($datos['depositos_en_transito'] ?? 0), 2);
        $chequesTransito = round((float)($datos['cheques_en_transito'] ?? 0), 2);
        $ndNoReg         = round((float)($datos['notas_debito_no_registradas'] ?? 0), 2);
        $ncNoReg         = round((float)($datos['notas_credito_no_registradas'] ?? 0), 2);
        $estatus         = (string)($datos['estatus'] ?? 'Borrador');
        $observaciones   = trim((string)($datos['observaciones'] ?? ''));

        if (!$this->obtenerCuentaPorId($cuentaId, $empresaId)) {
            throw new RuntimeException('Cuenta bancaria no encontrada.');
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            throw new RuntimeException('El período de conciliación debe tener formato AAAA-MM.');
        }

        if (!in_array($estatus, ['Borrador', 'Conciliada'], true)) {
            $estatus = 'Borrador';
        }

        $bancoConciliado  = round($saldoBanco + $depTransito - $chequesTransito, 2);
        $librosConciliado = round($saldoLibros + $ncNoReg - $ndNoReg, 2);
        $diferencia       = round($bancoConciliado - $librosConciliado, 2);

        if ($estatus === 'Conciliada' && abs($diferencia) > 0.009) {
            throw new RuntimeException('No se puede cerrar la conciliación mientras exista diferencia distinta de 0,00.');
        }

        $fechaCierre = $estatus === 'Conciliada' ? date('Y-m-d H:i:s') : null;

        $stmt = $this->db->prepare(
            'INSERT INTO conciliaciones_bancarias
                (empresa_id, cuenta_bancaria_id, periodo, saldo_segun_banco, saldo_segun_libros,
                 depositos_en_transito, cheques_en_transito, notas_debito_no_registradas,
                 notas_credito_no_registradas, diferencia, estatus, fecha_cierre, observaciones)
             VALUES
                (:empresa_id, :cuenta_bancaria_id, :periodo, :saldo_banco, :saldo_libros,
                 :dep_transito, :cheques_transito, :nd_no_reg,
                 :nc_no_reg, :diferencia, :estatus, :fecha_cierre, :observaciones)
             ON DUPLICATE KEY UPDATE
                saldo_segun_banco = VALUES(saldo_segun_banco),
                saldo_segun_libros = VALUES(saldo_segun_libros),
                depositos_en_transito = VALUES(depositos_en_transito),
                cheques_en_transito = VALUES(cheques_en_transito),
                notas_debito_no_registradas = VALUES(notas_debito_no_registradas),
                notas_credito_no_registradas = VALUES(notas_credito_no_registradas),
                diferencia = VALUES(diferencia),
                estatus = VALUES(estatus),
                fecha_cierre = VALUES(fecha_cierre),
                observaciones = VALUES(observaciones)'
        );
        $stmt->execute([
            ':empresa_id'         => $empresaId,
            ':cuenta_bancaria_id' => $cuentaId,
            ':periodo'            => $periodo,
            ':saldo_banco'        => $saldoBanco,
            ':saldo_libros'       => $saldoLibros,
            ':dep_transito'       => $depTransito,
            ':cheques_transito'   => $chequesTransito,
            ':nd_no_reg'          => $ndNoReg,
            ':nc_no_reg'          => $ncNoReg,
            ':diferencia'         => $diferencia,
            ':estatus'            => $estatus,
            ':fecha_cierre'       => $fechaCierre,
            ':observaciones'      => $observaciones !== '' ? $observaciones : null,
        ]);

        $conc = $this->obtenerConciliacion($empresaId, $cuentaId, $periodo);
        return (int)($conc['id'] ?? 0);
    }
}
