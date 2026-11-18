<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Modelo del Módulo Laboral y Nómina LOTTT (Multi-Empresa):
 * - Configuración de Parámetros Laborales (IVSS, RPE, FAOV, INCES, Cestaticket, Horas Extras)
 * - Directorio de Trabajadores con cálculo automático de Antigüedad y Prestaciones Sociales (Art. 142 LOTTT)
 * - Procesamiento de Nómina Quincenal / Mensual / Semanal con desglose de días hábiles, descanso y feriados
 * - Submódulo de Bono de Alimentación (Cestaticket Socialista)
 * - Emisión de Recibos de Pago e Integración Contable automática con el Libro Diario
 */
final class NominaModel
{
    private PDO $db;
    private static bool $esquemaVerificado = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->asegurarEsquemaFase4();
    }

    /**
     * Garantiza idempotentemente que las columnas y tablas de Fase 4 existan en la BD activa
     * utilizando information_schema.COLUMNS (compatible con ATTR_EMULATE_PREPARES => false en MariaDB).
     */
    private function asegurarEsquemaFase4(): void
    {
        if (self::$esquemaVerificado) {
            return;
        }

        try {
            // 1. Columnas adicionales en configuracion_fiscal
            $this->agregarColumnaSiNoExiste('configuracion_fiscal', 'tasa_bcv_usd', 'DECIMAL(18,4) NOT NULL DEFAULT 45.0000');
            $this->agregarColumnaSiNoExiste('configuracion_fiscal', 'salario_minimo_referencia', 'DECIMAL(18,2) NOT NULL DEFAULT 15000.00');
            $this->agregarColumnaSiNoExiste('configuracion_fiscal', 'tope_salarios_minimos_sso', 'TINYINT UNSIGNED NOT NULL DEFAULT 5');
            $this->agregarColumnaSiNoExiste('configuracion_fiscal', 'porcentaje_inces_patronal', 'DECIMAL(5,2) NOT NULL DEFAULT 2.00');
            $this->agregarColumnaSiNoExiste('configuracion_fiscal', 'porcentaje_inces_empleado', 'DECIMAL(5,2) NOT NULL DEFAULT 0.50');
            $this->agregarColumnaSiNoExiste('configuracion_fiscal', 'dias_base_calculo', 'TINYINT UNSIGNED NOT NULL DEFAULT 30');
            $this->agregarColumnaSiNoExiste('configuracion_fiscal', 'factor_hora_extra_diurna', 'DECIMAL(5,2) NOT NULL DEFAULT 1.50');
            $this->agregarColumnaSiNoExiste('configuracion_fiscal', 'factor_bono_nocturno', 'DECIMAL(5,2) NOT NULL DEFAULT 0.30');

            // 2. Columnas adicionales en trabajadores
            $this->agregarColumnaSiNoExiste('trabajadores', 'telefono', 'VARCHAR(30) NULL DEFAULT NULL AFTER `apellidos`');
            $this->agregarColumnaSiNoExiste('trabajadores', 'email', 'VARCHAR(120) NULL DEFAULT NULL AFTER `telefono`');
            $this->agregarColumnaSiNoExiste('trabajadores', 'fecha_nacimiento', 'DATE NULL DEFAULT NULL AFTER `email`');
            $this->agregarColumnaSiNoExiste('trabajadores', 'tipo_nomina', "ENUM('QUINCENAL', 'MENSUAL', 'SEMANAL') NOT NULL DEFAULT 'QUINCENAL' AFTER `tipo_trabajador`");
            $this->agregarColumnaSiNoExiste('trabajadores', 'porcentaje_islr', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `aplica_cestaticket`');
            $this->agregarColumnaSiNoExiste('trabajadores', 'estatus_laboral', "ENUM('ACTIVO', 'VACACIONES', 'REPOSO', 'EGRESADO') NOT NULL DEFAULT 'ACTIVO' AFTER `numero_cuenta`");

            // 3. Columnas adicionales en nominas
            $this->agregarColumnaSiNoExiste('nominas', 'descripcion', 'VARCHAR(200) NULL DEFAULT NULL AFTER `lunes_del_mes`');

            // 4. Columnas adicionales en nomina_detalles
            $this->agregarColumnaSiNoExiste('nomina_detalles', 'dias_habiles', 'DECIMAL(5,2) NOT NULL DEFAULT 11.00 AFTER `dias_trabajados`');
            $this->agregarColumnaSiNoExiste('nomina_detalles', 'dias_descanso', 'DECIMAL(5,2) NOT NULL DEFAULT 4.00 AFTER `dias_habiles`');
            $this->agregarColumnaSiNoExiste('nomina_detalles', 'dias_feriados', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `dias_descanso`');
            $this->agregarColumnaSiNoExiste('nomina_detalles', 'sueldo_diario', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `dias_feriados`');
            $this->agregarColumnaSiNoExiste('nomina_detalles', 'monto_dias_habiles', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `sueldo_basico_periodo`');
            $this->agregarColumnaSiNoExiste('nomina_detalles', 'monto_dias_descanso', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `monto_dias_habiles`');
            $this->agregarColumnaSiNoExiste('nomina_detalles', 'monto_dias_feriados', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `monto_dias_descanso`');
            $this->agregarColumnaSiNoExiste('nomina_detalles', 'retencion_islr', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `retencion_faov`');
            $this->agregarColumnaSiNoExiste('nomina_detalles', 'aporte_patronal_inces', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `aporte_patronal_faov`');

            // 5. Tabla cestaticket_lotes
            $this->db->exec("CREATE TABLE IF NOT EXISTS `cestaticket_lotes` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `empresa_id` INT UNSIGNED NOT NULL,
              `codigo_lote` VARCHAR(30) NOT NULL,
              `periodo` CHAR(7) NOT NULL,
              `fecha_emision` DATE NOT NULL,
              `monto_mensual_base` DECIMAL(18,2) NOT NULL DEFAULT 1800.00,
              `total_trabajadores` INT UNSIGNED NOT NULL DEFAULT 0,
              `total_pagar` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `asiento_id` INT UNSIGNED NULL DEFAULT NULL,
              `observaciones` VARCHAR(255) NULL DEFAULT NULL,
              `estado` ENUM('PROCESADO', 'ANULADO') NOT NULL DEFAULT 'PROCESADO',
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_cestaticket_empresa_codigo` (`empresa_id`, `codigo_lote`),
              KEY `idx_cestaticket_empresa_periodo` (`empresa_id`, `periodo`),
              CONSTRAINT `fk_cestaticket_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 6. Tabla cestaticket_detalles
            $this->db->exec("CREATE TABLE IF NOT EXISTS `cestaticket_detalles` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `lote_id` INT UNSIGNED NOT NULL,
              `empresa_id` INT UNSIGNED NOT NULL,
              `trabajador_id` INT UNSIGNED NOT NULL,
              `dias_habiles` DECIMAL(5,2) NOT NULL DEFAULT 22.00,
              `dias_descanso` DECIMAL(5,2) NOT NULL DEFAULT 8.00,
              `dias_feriados` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
              `dias_inasistencia` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
              `dias_efectivos` DECIMAL(5,2) NOT NULL DEFAULT 30.00,
              `monto_mensual_base` DECIMAL(18,2) NOT NULL DEFAULT 1800.00,
              `valor_diario` DECIMAL(18,2) NOT NULL DEFAULT 60.00,
              `monto_descuento` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `total_cestaticket` DECIMAL(18,2) NOT NULL DEFAULT 1800.00,
              PRIMARY KEY (`id`),
              KEY `idx_cest_det_lote` (`lote_id`),
              KEY `idx_cest_det_trabajador` (`empresa_id`, `trabajador_id`),
              CONSTRAINT `fk_cest_det_lote` FOREIGN KEY (`lote_id`) REFERENCES `cestaticket_lotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk_cest_det_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk_cest_det_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 7. Enriquecer trabajadores iniciales si aún no tienen cuenta bancaria/teléfono
            $this->db->exec("UPDATE `trabajadores`
                SET `telefono` = COALESCE(`telefono`, '0414-2893104'),
                    `email` = COALESCE(`email`, 'cmendez@elrosalca.com.ve'),
                    `fecha_nacimiento` = COALESCE(`fecha_nacimiento`, '1988-06-14'),
                    `banco` = COALESCE(`banco`, 'BANCO PROVINCIAL'),
                    `numero_cuenta` = COALESCE(`numero_cuenta`, '01080025140100984512')
                WHERE `empresa_id` = 1 AND `cedula` = 'V-18452310'");

            $this->db->exec("UPDATE `trabajadores`
                SET `telefono` = COALESCE(`telefono`, '0412-9014421'),
                    `email` = COALESCE(`email`, 'gparedes@elrosalca.com.ve'),
                    `fecha_nacimiento` = COALESCE(`fecha_nacimiento`, '1995-11-22'),
                    `banco` = COALESCE(`banco`, 'BANCAMIGA'),
                    `numero_cuenta` = COALESCE(`numero_cuenta`, '01720110550004918201')
                WHERE `empresa_id` = 1 AND `cedula` = 'V-24891023'");

            $this->db->exec("UPDATE `trabajadores`
                SET `telefono` = COALESCE(`telefono`, '0424-1558902'),
                    `email` = COALESCE(`email`, 'jcastillo@elrosalca.com.ve'),
                    `fecha_nacimiento` = COALESCE(`fecha_nacimiento`, '1981-03-09'),
                    `banco` = COALESCE(`banco`, 'BANCO DE VENEZUELA'),
                    `numero_cuenta` = COALESCE(`numero_cuenta`, '01020145880000192834')
                WHERE `empresa_id` = 1 AND `cedula` = 'V-15223981'");

            $this->db->exec("UPDATE `trabajadores`
                SET `telefono` = COALESCE(`telefono`, '0414-8861209'),
                    `email` = COALESCE(`email`, 'lsalazar@tecnoorinoco.com.ve'),
                    `fecha_nacimiento` = COALESCE(`fecha_nacimiento`, '1985-08-19'),
                    `banco` = COALESCE(`banco`, 'BANCO PROVINCIAL'),
                    `numero_cuenta` = COALESCE(`numero_cuenta`, '01080098120100441290')
                WHERE `empresa_id` = 2 AND `cedula` = 'V-19882104'");

            $this->db->exec("UPDATE `trabajadores`
                SET `telefono` = COALESCE(`telefono`, '0424-9127730'),
                    `email` = COALESCE(`email`, 'mbrito@tecnoorinoco.com.ve'),
                    `fecha_nacimiento` = COALESCE(`fecha_nacimiento`, '1997-02-28'),
                    `banco` = COALESCE(`banco`, 'BANCAMIGA'),
                    `numero_cuenta` = COALESCE(`numero_cuenta`, '01720110990008172635')
                WHERE `empresa_id` = 2 AND `cedula` = 'V-26114789'");

            // 8. Sembrar un lote inicial de Nómina y Cestaticket para Empresa 1 si está vacío
            $this->sembrarDatosInicialesSiVacio();

            self::$esquemaVerificado = true;
        } catch (Throwable) {
            self::$esquemaVerificado = true;
        }
    }

    /**
     * Agrega una columna si no existe consultando information_schema.COLUMNS
     */
    private function agregarColumnaSiNoExiste(string $tabla, string $columna, string $definicion): void
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tabla
               AND COLUMN_NAME = :columna'
        );
        $stmt->execute([
            ':tabla'   => $tabla,
            ':columna' => $columna,
        ]);

        if ((int)$stmt->fetchColumn() === 0) {
            $this->db->exec(sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $tabla, $columna, $definicion));
        }
    }

    /**
     * Si la empresa 1 o 2 aún no tienen lotes completos de nómina o cestaticket, crea un lote de muestra
     * procesado para Noviembre 2026 para que las vistas e impresiones tengan datos reales de inmediato.
     */
    private function sembrarDatosInicialesSiVacio(): void
    {
        // Limpiar cualquier cabecera de nómina huérfana sin detalles
        $this->db->exec('DELETE FROM nominas WHERE id NOT IN (SELECT DISTINCT nomina_id FROM nomina_detalles)');

        foreach ([1, 2] as $empSeedId) {
            $stmtCount = $this->db->prepare('SELECT COUNT(*) FROM nomina_detalles WHERE empresa_id = :emp_id');
            $stmtCount->execute([':emp_id' => $empSeedId]);
            if ((int)$stmtCount->fetchColumn() === 0) {
                $precalculo = $this->precalcularLoteNomina($empSeedId, [
                    'periodo'         => '2026-11',
                    'tipo_frecuencia' => 'PRIMERA_QUINCENA',
                    'fecha_desde'     => '2026-11-01',
                    'fecha_hasta'     => '2026-11-15',
                    'lunes_del_mes'   => 5,
                ]);

                if (!empty($precalculo['filas'])) {
                    $precalculo['filas'][0]['bonificaciones'] = 1200.00;
                    $this->procesarYGuardarNomina($empSeedId, 1, [
                        'periodo'         => '2026-11',
                        'tipo_frecuencia' => 'PRIMERA_QUINCENA',
                        'fecha_desde'     => '2026-11-01',
                        'fecha_hasta'     => '2026-11-15',
                        'lunes_del_mes'   => 5,
                        'descripcion'     => 'Nómina Primera Quincena Noviembre 2026 - Personal Activo',
                        'generar_asiento' => true,
                        'filas'           => $precalculo['filas'],
                    ]);
                }
            }

            $stmtCest = $this->db->prepare('SELECT COUNT(*) FROM cestaticket_detalles WHERE empresa_id = :emp_id');
            $stmtCest->execute([':emp_id' => $empSeedId]);
            if ((int)$stmtCest->fetchColumn() === 0) {
                $preCest = $this->precalcularCestaticket($empSeedId, '2026-11');
                if (!empty($preCest['filas'])) {
                    $this->guardarLoteCestaticket($empSeedId, 1, [
                        'periodo'            => '2026-11',
                        'fecha_emision'      => '2026-11-15',
                        'monto_mensual_base' => (float)$preCest['monto_mensual_base'],
                        'observaciones'      => 'Beneficio de Alimentación (Cestaticket Socialista) Noviembre 2026',
                        'generar_asiento'    => false,
                        'filas'              => $preCest['filas'],
                    ]);
                }
            }
        }
    }

    // =========================================================================
    // 1. CONFIGURACIÓN DE PARÁMETROS LABORALES (POR EMPRESA)
    // =========================================================================

    /**
     * Obtiene los parámetros laborales configurados para la empresa activa
     */
    public function obtenerConfiguracionLaboral(int $empresaId): array
    {
        $sql = 'SELECT
                    empresa_id,
                    porcentaje_sso_empleado,
                    porcentaje_rpe_empleado,
                    porcentaje_faov_empleado,
                    porcentaje_sso_patronal,
                    porcentaje_rpe_patronal,
                    porcentaje_faov_patronal,
                    porcentaje_inces_patronal,
                    porcentaje_inces_empleado,
                    monto_cestaticket_mensual,
                    tasa_bcv_usd,
                    salario_minimo_referencia,
                    tope_salarios_minimos_sso,
                    dias_base_calculo,
                    factor_hora_extra_diurna,
                    factor_bono_nocturno
                FROM configuracion_fiscal
                WHERE empresa_id = :empresa_id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'empresa_id'                => $empresaId,
                'porcentaje_sso_empleado'   => 4.00,
                'porcentaje_rpe_empleado'   => 0.50,
                'porcentaje_faov_empleado'  => 1.00,
                'porcentaje_sso_patronal'   => 9.00,
                'porcentaje_rpe_patronal'   => 2.00,
                'porcentaje_faov_patronal'  => 2.00,
                'porcentaje_inces_patronal' => 2.00,
                'porcentaje_inces_empleado' => 0.50,
                'monto_cestaticket_mensual' => 1800.00,
                'tasa_bcv_usd'              => 45.00,
                'salario_minimo_referencia' => 15000.00,
                'tope_salarios_minimos_sso' => 5,
                'dias_base_calculo'         => 30,
                'factor_hora_extra_diurna'  => 1.50,
                'factor_bono_nocturno'      => 0.30,
            ];
        }

        return [
            'empresa_id'                => (int)$row['empresa_id'],
            'porcentaje_sso_empleado'   => (float)$row['porcentaje_sso_empleado'],
            'porcentaje_rpe_empleado'   => (float)$row['porcentaje_rpe_empleado'],
            'porcentaje_faov_empleado'  => (float)$row['porcentaje_faov_empleado'],
            'porcentaje_sso_patronal'   => (float)$row['porcentaje_sso_patronal'],
            'porcentaje_rpe_patronal'   => (float)$row['porcentaje_rpe_patronal'],
            'porcentaje_faov_patronal'  => (float)$row['porcentaje_faov_patronal'],
            'porcentaje_inces_patronal' => (float)($row['porcentaje_inces_patronal'] ?? 2.00),
            'porcentaje_inces_empleado' => (float)($row['porcentaje_inces_empleado'] ?? 0.50),
            'monto_cestaticket_mensual' => (float)$row['monto_cestaticket_mensual'],
            'tasa_bcv_usd'              => (float)($row['tasa_bcv_usd'] ?? 45.00),
            'salario_minimo_referencia' => (float)($row['salario_minimo_referencia'] ?? 15000.00),
            'tope_salarios_minimos_sso' => (int)($row['tope_salarios_minimos_sso'] ?? 5),
            'dias_base_calculo'         => (int)($row['dias_base_calculo'] ?? 30),
            'factor_hora_extra_diurna'  => (float)($row['factor_hora_extra_diurna'] ?? 1.50),
            'factor_bono_nocturno'      => (float)($row['factor_bono_nocturno'] ?? 0.30),
        ];
    }

    /**
     * Actualiza los parámetros laborales de la empresa activa
     */
    public function actualizarConfiguracionLaboral(int $empresaId, array $datos): void
    {
        $sql = 'UPDATE configuracion_fiscal
                SET porcentaje_sso_empleado   = :sso_emp,
                    porcentaje_rpe_empleado   = :rpe_emp,
                    porcentaje_faov_empleado  = :faov_emp,
                    porcentaje_sso_patronal   = :sso_pat,
                    porcentaje_rpe_patronal   = :rpe_pat,
                    porcentaje_faov_patronal  = :faov_pat,
                    porcentaje_inces_patronal = :inces_pat,
                    porcentaje_inces_empleado = :inces_emp,
                    monto_cestaticket_mensual = :cestaticket,
                    tasa_bcv_usd              = :tasa_bcv,
                    salario_minimo_referencia = :sal_min,
                    tope_salarios_minimos_sso = :tope_sso,
                    dias_base_calculo         = :dias_base,
                    factor_hora_extra_diurna  = :factor_he,
                    factor_bono_nocturno      = :factor_bn
                WHERE empresa_id = :empresa_id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':sso_emp'     => max(0.0, round((float)($datos['porcentaje_sso_empleado'] ?? 4.00), 2)),
            ':rpe_emp'     => max(0.0, round((float)($datos['porcentaje_rpe_empleado'] ?? 0.50), 2)),
            ':faov_emp'    => max(0.0, round((float)($datos['porcentaje_faov_empleado'] ?? 1.00), 2)),
            ':sso_pat'     => max(0.0, round((float)($datos['porcentaje_sso_patronal'] ?? 9.00), 2)),
            ':rpe_pat'     => max(0.0, round((float)($datos['porcentaje_rpe_patronal'] ?? 2.00), 2)),
            ':faov_pat'    => max(0.0, round((float)($datos['porcentaje_faov_patronal'] ?? 2.00), 2)),
            ':inces_pat'   => max(0.0, round((float)($datos['porcentaje_inces_patronal'] ?? 2.00), 2)),
            ':inces_emp'   => max(0.0, round((float)($datos['porcentaje_inces_empleado'] ?? 0.50), 2)),
            ':cestaticket' => max(0.0, round((float)($datos['monto_cestaticket_mensual'] ?? 1800.00), 2)),
            ':tasa_bcv'    => max(0.01, round((float)($datos['tasa_bcv_usd'] ?? 45.00), 4)),
            ':sal_min'     => max(1.0, round((float)($datos['salario_minimo_referencia'] ?? 15000.00), 2)),
            ':tope_sso'    => max(1, (int)($datos['tope_salarios_minimos_sso'] ?? 5)),
            ':dias_base'   => max(15, min(31, (int)($datos['dias_base_calculo'] ?? 30))),
            ':factor_he'   => max(1.0, round((float)($datos['factor_hora_extra_diurna'] ?? 1.50), 2)),
            ':factor_bn'   => max(0.0, round((float)($datos['factor_bono_nocturno'] ?? 0.30), 2)),
            ':empresa_id'  => $empresaId,
        ]);
    }

    // =========================================================================
    // 2. DIRECTORIO DE TRABAJADORES Y CÁLCULO DE PRESTACIONES (ART. 142 LOTTT)
    // =========================================================================

    /**
     * Lista los trabajadores de la empresa activa enriquecidos con Antigüedad y Prestaciones Sociales
     */
    public function listarTrabajadores(int $empresaId, string $filtroEstatus = 'TODOS', string $busqueda = ''): array
    {
        $sql = 'SELECT *
                FROM trabajadores
                WHERE empresa_id = :empresa_id';
        $params = [':empresa_id' => $empresaId];

        if ($filtroEstatus !== '' && $filtroEstatus !== 'TODOS') {
            $sql .= ' AND estatus_laboral = :estatus';
            $params[':estatus'] = $filtroEstatus;
        }

        if ($busqueda !== '') {
            $sql .= ' AND (cedula LIKE :q OR nombres LIKE :q OR apellidos LIKE :q OR cargo LIKE :q OR departamento LIKE :q)';
            $params[':q'] = '%' . $busqueda . '%';
        }

        $sql .= ' ORDER BY activo DESC, apellidos ASC, nombres ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $config = $this->obtenerConfiguracionLaboral($empresaId);
        $diasBase = max(1, (int)$config['dias_base_calculo']);

        $resultado = [];
        foreach ($rows as $row) {
            $resultado[] = $this->enriquecerFichaTrabajador($row, $diasBase);
        }

        return $resultado;
    }

    /**
     * Obtiene un trabajador específico verificando pertenencia a la empresa activa
     */
    public function obtenerTrabajador(int $empresaId, int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM trabajadores WHERE id = :id AND empresa_id = :empresa_id LIMIT 1'
        );
        $stmt->execute([
            ':id'         => $id,
            ':empresa_id' => $empresaId,
        ]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $config = $this->obtenerConfiguracionLaboral($empresaId);
        return $this->enriquecerFichaTrabajador($row, max(1, (int)$config['dias_base_calculo']));
    }

    /**
     * Calcula la antigüedad exacta (años, meses, días) y las Prestaciones Sociales estimadas (Art. 142 LOTTT)
     */
    private function enriquecerFichaTrabajador(array $row, int $diasBase = 30): array
    {
        $fechaIngresoStr = (string)($row['fecha_ingreso'] ?? date('Y-m-d'));
        $fechaCorteStr   = !empty($row['fecha_egreso']) && ($row['estatus_laboral'] ?? '') === 'EGRESADO'
            ? (string)$row['fecha_egreso']
            : date('Y-m-d');

        try {
            $inicio = new DateTimeImmutable($fechaIngresoStr);
            $corte  = new DateTimeImmutable($fechaCorteStr);
            if ($corte < $inicio) {
                $corte = $inicio;
            }
            $diff = $inicio->diff($corte);
            $anios = (int)$diff->y;
            $meses = (int)$diff->m;
            $dias  = (int)$diff->d;
        } catch (Throwable) {
            $anios = 0;
            $meses = 0;
            $dias  = 0;
        }

        $partes = [];
        if ($anios > 0) {
            $partes[] = $anios . ($anios === 1 ? ' año' : ' años');
        }
        if ($meses > 0) {
            $partes[] = $meses . ($meses === 1 ? ' mes' : ' meses');
        }
        if ($dias > 0 || empty($partes)) {
            $partes[] = $dias . ($dias === 1 ? ' día' : ' días');
        }
        $antiguedadTexto = implode(', ', $partes);

        // Cálculo de garantía de Prestaciones Sociales según Art. 142 literal a y b LOTTT:
        // - 15 días de salario integral por cada trimestre (5 días por mes completo / trimestre)
        // - 2 días adicionales por cada año de servicio después del primer año, acumulativos hasta 30 días
        $mesesTotales = ($anios * 12) + $meses;
        $trimestres   = (int)floor($mesesTotales / 3);
        $diasGarantiaTrimestral = max(5, $trimestres * 15);
        $diasAdicionalesAnuales = $anios > 1 ? min(30, ($anios - 1) * 2) : 0;
        $diasPrestacionesTotales = $diasGarantiaTrimestral + $diasAdicionalesAnuales;

        $salarioMensual = (float)($row['salario_mensual'] ?? 0.0);
        $sueldoDiario   = round($salarioMensual / max(1, $diasBase), 2);

        // Salario Integral Diario (Sueldo Diario + Alícuota Utilidades 30 días/360 + Alícuota Bono Vacacional 15 días/360)
        $alicuotaUtilidades     = round(($sueldoDiario * 30) / 360, 2);
        $alicuotaBonoVacacional = round(($sueldoDiario * (15 + min(15, max(0, $anios - 1)))) / 360, 2);
        $salarioIntegralDiario  = round($sueldoDiario + $alicuotaUtilidades + $alicuotaBonoVacacional, 2);

        $prestacionesEstimadas = round($diasPrestacionesTotales * $salarioIntegralDiario, 2);

        $row['nombre_completo']               = trim((string)$row['nombres'] . ' ' . (string)$row['apellidos']);
        $row['sueldo_diario']                 = $sueldoDiario;
        $row['salario_integral_diario']       = $salarioIntegralDiario;
        $row['anios_servicio']                = $anios;
        $row['meses_servicio']                = $meses;
        $row['dias_servicio']                 = $dias;
        $row['antiguedad_texto']              = $antiguedadTexto;
        $row['trimestres_servicio']           = $trimestres;
        $row['dias_prestaciones_acumulados']  = $diasPrestacionesTotales;
        $row['prestaciones_acumuladas_bs']    = $prestacionesEstimadas;

        return $row;
    }

    /**
     * Crea o actualiza un trabajador dentro de la empresa activa
     */
    public function guardarTrabajador(int $empresaId, array $datos): int
    {
        $id              = (int)($datos['id'] ?? 0);
        $cedula          = strtoupper(trim((string)($datos['cedula'] ?? '')));
        $nombres         = trim((string)($datos['nombres'] ?? ''));
        $apellidos       = trim((string)($datos['apellidos'] ?? ''));
        $telefono        = trim((string)($datos['telefono'] ?? ''));
        $email           = trim((string)($datos['email'] ?? ''));
        $fechaNacimiento = trim((string)($datos['fecha_nacimiento'] ?? ''));
        $cargo           = trim((string)($datos['cargo'] ?? ''));
        $departamento    = trim((string)($datos['departamento'] ?? 'Administración'));
        $tipoTrabajador  = strtoupper(trim((string)($datos['tipo_trabajador'] ?? 'EMPLEADO')));
        $tipoNomina      = strtoupper(trim((string)($datos['tipo_nomina'] ?? 'QUINCENAL')));
        $fechaIngreso    = trim((string)($datos['fecha_ingreso'] ?? date('Y-m-d')));
        $fechaEgreso     = trim((string)($datos['fecha_egreso'] ?? ''));
        $salarioMensual  = round((float)($datos['salario_mensual'] ?? 0.0), 2);
        $aplicaSso       = !empty($datos['aplica_sso']) ? 1 : 0;
        $aplicaRpe       = !empty($datos['aplica_rpe']) ? 1 : 0;
        $aplicaFaov      = !empty($datos['aplica_faov']) ? 1 : 0;
        $aplicaCest      = !empty($datos['aplica_cestaticket']) ? 1 : 0;
        $porcentajeIslr  = max(0.0, min(34.0, round((float)($datos['porcentaje_islr'] ?? 0.0), 2)));
        $banco           = strtoupper(trim((string)($datos['banco'] ?? '')));
        $numeroCuenta    = preg_replace('/\s+/', '', trim((string)($datos['numero_cuenta'] ?? ''))) ?? '';
        $estatusLaboral  = strtoupper(trim((string)($datos['estatus_laboral'] ?? 'ACTIVO')));

        if (!preg_match('/^[VEJG]-\d{5,10}$/', $cedula)) {
            throw new RuntimeException('La Cédula de Identidad debe tener formato válido (Ej: V-18452310 o E-81234567).');
        }

        if ($nombres === '' || $apellidos === '' || $cargo === '') {
            throw new RuntimeException('Los nombres, apellidos y cargo del trabajador son obligatorios.');
        }

        if ($salarioMensual <= 0) {
            throw new RuntimeException('El salario base mensual debe ser mayor a Bs. 0,00.');
        }

        if ($numeroCuenta !== '' && !preg_match('/^\d{20}$/', $numeroCuenta)) {
            throw new RuntimeException('El número de cuenta bancaria debe contener exactamente 20 dígitos numéricos.');
        }

        if (!in_array($tipoTrabajador, ['EMPLEADO', 'OBRERO', 'DIRECTIVO'], true)) {
            $tipoTrabajador = 'EMPLEADO';
        }
        if (!in_array($tipoNomina, ['QUINCENAL', 'MENSUAL', 'SEMANAL'], true)) {
            $tipoNomina = 'QUINCENAL';
        }
        if (!in_array($estatusLaboral, ['ACTIVO', 'VACACIONES', 'REPOSO', 'EGRESADO'], true)) {
            $estatusLaboral = 'ACTIVO';
        }

        $activo = $estatusLaboral === 'EGRESADO' ? 0 : 1;

        // Validar duplicidad de cédula en la misma empresa
        $stmtDup = $this->db->prepare(
            'SELECT id FROM trabajadores WHERE empresa_id = :empresa_id AND cedula = :cedula AND id <> :id LIMIT 1'
        );
        $stmtDup->execute([
            ':empresa_id' => $empresaId,
            ':cedula'     => $cedula,
            ':id'         => $id,
        ]);
        if ($stmtDup->fetch()) {
            throw new RuntimeException("Ya existe un trabajador con la cédula {$cedula} registrado en esta empresa.");
        }

        if ($id > 0) {
            $sql = 'UPDATE trabajadores
                    SET cedula             = :cedula,
                        nombres            = :nombres,
                        apellidos          = :apellidos,
                        telefono           = :telefono,
                        email              = :email,
                        fecha_nacimiento   = :fecha_nac,
                        cargo              = :cargo,
                        departamento       = :departamento,
                        tipo_trabajador    = :tipo_trabajador,
                        tipo_nomina        = :tipo_nomina,
                        fecha_ingreso      = :fecha_ingreso,
                        fecha_egreso       = :fecha_egreso,
                        salario_mensual    = :salario_mensual,
                        aplica_sso         = :aplica_sso,
                        aplica_rpe         = :aplica_rpe,
                        aplica_faov        = :aplica_faov,
                        aplica_cestaticket = :aplica_cest,
                        porcentaje_islr    = :porcentaje_islr,
                        banco              = :banco,
                        numero_cuenta      = :numero_cuenta,
                        estatus_laboral    = :estatus_laboral,
                        activo             = :activo
                    WHERE id = :id AND empresa_id = :empresa_id';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cedula'          => $cedula,
                ':nombres'         => $nombres,
                ':apellidos'       => $apellidos,
                ':telefono'        => $telefono !== '' ? $telefono : null,
                ':email'           => $email !== '' ? $email : null,
                ':fecha_nac'       => $fechaNacimiento !== '' ? $fechaNacimiento : null,
                ':cargo'           => $cargo,
                ':departamento'    => $departamento,
                ':tipo_trabajador' => $tipoTrabajador,
                ':tipo_nomina'     => $tipoNomina,
                ':fecha_ingreso'   => $fechaIngreso,
                ':fecha_egreso'    => $fechaEgreso !== '' ? $fechaEgreso : null,
                ':salario_mensual' => $salarioMensual,
                ':aplica_sso'      => $aplicaSso,
                ':aplica_rpe'      => $aplicaRpe,
                ':aplica_faov'     => $aplicaFaov,
                ':aplica_cest'     => $aplicaCest,
                ':porcentaje_islr' => $porcentajeIslr,
                ':banco'           => $banco !== '' ? $banco : null,
                ':numero_cuenta'   => $numeroCuenta !== '' ? $numeroCuenta : null,
                ':estatus_laboral' => $estatusLaboral,
                ':activo'          => $activo,
                ':id'              => $id,
                ':empresa_id'      => $empresaId,
            ]);

            return $id;
        }

        $sql = 'INSERT INTO trabajadores (
                    empresa_id, cedula, nombres, apellidos, telefono, email, fecha_nacimiento,
                    cargo, departamento, tipo_trabajador, tipo_nomina, fecha_ingreso, fecha_egreso,
                    salario_mensual, aplica_sso, aplica_rpe, aplica_faov, aplica_cestaticket,
                    porcentaje_islr, banco, numero_cuenta, estatus_laboral, activo
                ) VALUES (
                    :empresa_id, :cedula, :nombres, :apellidos, :telefono, :email, :fecha_nac,
                    :cargo, :departamento, :tipo_trabajador, :tipo_nomina, :fecha_ingreso, :fecha_egreso,
                    :salario_mensual, :aplica_sso, :aplica_rpe, :aplica_faov, :aplica_cest,
                    :porcentaje_islr, :banco, :numero_cuenta, :estatus_laboral, :activo
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id'      => $empresaId,
            ':cedula'          => $cedula,
            ':nombres'         => $nombres,
            ':apellidos'       => $apellidos,
            ':telefono'        => $telefono !== '' ? $telefono : null,
            ':email'           => $email !== '' ? $email : null,
            ':fecha_nac'       => $fechaNacimiento !== '' ? $fechaNacimiento : null,
            ':cargo'           => $cargo,
            ':departamento'    => $departamento,
            ':tipo_trabajador' => $tipoTrabajador,
            ':tipo_nomina'     => $tipoNomina,
            ':fecha_ingreso'   => $fechaIngreso,
            ':fecha_egreso'    => $fechaEgreso !== '' ? $fechaEgreso : null,
            ':salario_mensual' => $salarioMensual,
            ':aplica_sso'      => $aplicaSso,
            ':aplica_rpe'      => $aplicaRpe,
            ':aplica_faov'     => $aplicaFaov,
            ':aplica_cest'     => $aplicaCest,
            ':porcentaje_islr' => $porcentajeIslr,
            ':banco'           => $banco !== '' ? $banco : null,
            ':numero_cuenta'   => $numeroCuenta !== '' ? $numeroCuenta : null,
            ':estatus_laboral' => $estatusLaboral,
            ':activo'          => $activo,
        ]);

        return (int)$this->db->lastInsertId();
    }

    // =========================================================================
    // 3. PROCESAMIENTO DE NÓMINA GENERAL (QUINCENAL / MENSUAL / SEMANAL)
    // =========================================================================

    /**
     * Lista todos los lotes de nómina procesados de la empresa activa
     */
    public function listarNominas(int $empresaId, ?string $periodo = null): array
    {
        $sql = 'SELECT
                    n.*,
                    a.numero_asiento,
                    (SELECT COUNT(*) FROM nomina_detalles nd WHERE nd.nomina_id = n.id) AS cantidad_trabajadores
                FROM nominas n
                LEFT JOIN asientos a ON a.id = n.asiento_id AND a.empresa_id = n.empresa_id
                WHERE n.empresa_id = :empresa_id';
        $params = [':empresa_id' => $empresaId];

        if ($periodo !== null && $periodo !== '') {
            $sql .= ' AND n.periodo = :periodo';
            $params[':periodo'] = $periodo;
        }

        $sql .= ' ORDER BY n.fecha_hasta DESC, n.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene la cabecera de una nómina junto con el detalle por cada trabajador
     */
    public function obtenerNominaConDetalles(int $empresaId, int $nominaId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT n.*, a.numero_asiento
             FROM nominas n
             LEFT JOIN asientos a ON a.id = n.asiento_id AND a.empresa_id = n.empresa_id
             WHERE n.id = :id AND n.empresa_id = :empresa_id
             LIMIT 1'
        );
        $stmt->execute([
            ':id'         => $nominaId,
            ':empresa_id' => $empresaId,
        ]);
        $cabecera = $stmt->fetch();

        if (!$cabecera) {
            return null;
        }

        $stmtDet = $this->db->prepare(
            'SELECT
                nd.*,
                t.cedula,
                t.nombres,
                t.apellidos,
                CONCAT(t.nombres, " ", t.apellidos) AS nombre_completo,
                t.cargo,
                t.departamento,
                t.tipo_trabajador,
                t.fecha_ingreso,
                t.salario_mensual,
                t.banco,
                t.numero_cuenta
             FROM nomina_detalles nd
             INNER JOIN trabajadores t ON t.id = nd.trabajador_id AND t.empresa_id = nd.empresa_id
             WHERE nd.nomina_id = :nomina_id AND nd.empresa_id = :empresa_id
             ORDER BY t.apellidos ASC, t.nombres ASC'
        );
        $stmtDet->execute([
            ':nomina_id'  => $nominaId,
            ':empresa_id' => $empresaId,
        ]);

        $cabecera['detalles'] = $stmtDet->fetchAll();
        return $cabecera;
    }

    /**
     * Obtiene un recibo individual por su ID de detalle (nomina_detalles.id)
     */
    public function obtenerReciboIndividual(int $empresaId, int $detalleId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT
                nd.*,
                n.codigo_nomina,
                n.periodo,
                n.tipo_frecuencia,
                n.fecha_desde,
                n.fecha_hasta,
                n.lunes_del_mes,
                n.descripcion AS descripcion_nomina,
                n.estado AS estado_nomina,
                t.cedula,
                t.nombres,
                t.apellidos,
                CONCAT(t.nombres, " ", t.apellidos) AS nombre_completo,
                t.cargo,
                t.departamento,
                t.tipo_trabajador,
                t.fecha_ingreso,
                t.salario_mensual,
                t.banco,
                t.numero_cuenta
             FROM nomina_detalles nd
             INNER JOIN nominas n ON n.id = nd.nomina_id AND n.empresa_id = nd.empresa_id
             INNER JOIN trabajadores t ON t.id = nd.trabajador_id AND t.empresa_id = nd.empresa_id
             WHERE nd.id = :id AND nd.empresa_id = :empresa_id
             LIMIT 1'
        );
        $stmt->execute([
            ':id'         => $detalleId,
            ':empresa_id' => $empresaId,
        ]);

        $recibo = $stmt->fetch();
        return $recibo ?: null;
    }

    /**
     * Precalcula la hoja de trabajo de nómina para todos los trabajadores activos de la empresa
     */
    public function precalcularLoteNomina(int $empresaId, array $parametros): array
    {
        $config         = $this->obtenerConfiguracionLaboral($empresaId);
        $tipoFrecuencia = strtoupper(trim((string)($parametros['tipo_frecuencia'] ?? 'PRIMERA_QUINCENA')));
        $lunesInput     = (int)($parametros['lunes_del_mes'] ?? 4);
        $lunesDelMes    = in_array($lunesInput, [4, 5], true) ? $lunesInput : 4;

        $diasBase = max(1, (int)$config['dias_base_calculo']);

        if ($tipoFrecuencia === 'MENSUAL') {
            $diasHabilesDefault  = 22.0;
            $diasDescansoDefault = 8.0;
            $factorPeriodoSso    = 1.0;
        } elseif ($tipoFrecuencia === 'SEMANAL') {
            $diasHabilesDefault  = 5.0;
            $diasDescansoDefault = 2.0;
            $factorPeriodoSso    = 1.0 / max(4, $lunesDelMes);
        } else {
            // PRIMERA_QUINCENA o SEGUNDA_QUINCENA
            $diasHabilesDefault  = 11.0;
            $diasDescansoDefault = 4.0;
            $factorPeriodoSso    = 0.5;
        }

        $trabajadores = $this->listarTrabajadores($empresaId);
        $filas = [];

        foreach ($trabajadores as $t) {
            if ((int)$t['activo'] !== 1 || ($t['estatus_laboral'] ?? 'ACTIVO') === 'EGRESADO') {
                continue;
            }

            $salarioMensual = (float)$t['salario_mensual'];
            $sueldoDiario   = round($salarioMensual / $diasBase, 2);

            $diasHabiles    = $diasHabilesDefault;
            $diasDescanso   = $diasDescansoDefault;
            $diasFeriados   = 0.00;
            $diasTrabajados = $diasHabiles + $diasDescanso + $diasFeriados;

            $montoHabiles   = round($sueldoDiario * $diasHabiles, 2);
            $montoDescanso  = round($sueldoDiario * $diasDescanso, 2);
            $montoFeriados  = 0.00;
            $sueldoBasico   = round($montoHabiles + $montoDescanso + $montoFeriados, 2);

            $horasExtras    = 0.00;
            $bonificaciones = 0.00;

            // Cálculo IVSS y RPE según lunes del mes y tope legal
            $topeMensualSso = (float)$config['salario_minimo_referencia'] * (int)$config['tope_salarios_minimos_sso'];
            $baseSsoMensual = $topeMensualSso > 0 ? min($salarioMensual, $topeMensualSso) : $salarioMensual;
            $baseSemanalSso = ($baseSsoMensual * 12.0) / 52.0;

            $retSso = ((int)$t['aplica_sso'] === 1)
                ? round($baseSemanalSso * $lunesDelMes * ((float)$config['porcentaje_sso_empleado'] / 100.0) * $factorPeriodoSso, 2)
                : 0.00;

            $retRpe = ((int)$t['aplica_rpe'] === 1)
                ? round($baseSemanalSso * $lunesDelMes * ((float)$config['porcentaje_rpe_empleado'] / 100.0) * $factorPeriodoSso, 2)
                : 0.00;

            // FAOV: 1% sobre total remuneración del período
            $totalAsignaciones = round($sueldoBasico + $horasExtras + $bonificaciones, 2);
            $retFaov = ((int)$t['aplica_faov'] === 1)
                ? round($totalAsignaciones * ((float)$config['porcentaje_faov_empleado'] / 100.0), 2)
                : 0.00;

            // Retención ISLR (AR-I)
            $porcIslr = (float)($t['porcentaje_islr'] ?? 0.0);
            $retIslr  = $porcIslr > 0 ? round($totalAsignaciones * ($porcIslr / 100.0), 2) : 0.00;

            $otrasDeducciones = 0.00;
            $totalDeducciones = round($retSso + $retRpe + $retFaov + $retIslr + $otrasDeducciones, 2);
            $netoPagar        = round($totalAsignaciones - $totalDeducciones, 2);

            // Aportes Patronales
            $aporteSso   = ((int)$t['aplica_sso'] === 1)
                ? round($baseSemanalSso * $lunesDelMes * ((float)$config['porcentaje_sso_patronal'] / 100.0) * $factorPeriodoSso, 2)
                : 0.00;
            $aporteRpe   = ((int)$t['aplica_rpe'] === 1)
                ? round($baseSemanalSso * $lunesDelMes * ((float)$config['porcentaje_rpe_patronal'] / 100.0) * $factorPeriodoSso, 2)
                : 0.00;
            $aporteFaov  = ((int)$t['aplica_faov'] === 1)
                ? round($totalAsignaciones * ((float)$config['porcentaje_faov_patronal'] / 100.0), 2)
                : 0.00;
            $aporteInces = round($totalAsignaciones * ((float)$config['porcentaje_inces_patronal'] / 100.0), 2);

            $filas[] = [
                'trabajador_id'         => (int)$t['id'],
                'cedula'                => (string)$t['cedula'],
                'nombre_completo'       => (string)$t['nombre_completo'],
                'cargo'                 => (string)$t['cargo'],
                'departamento'          => (string)$t['departamento'],
                'tipo_trabajador'       => (string)$t['tipo_trabajador'],
                'fecha_ingreso'         => (string)$t['fecha_ingreso'],
                'salario_mensual'       => $salarioMensual,
                'sueldo_diario'         => $sueldoDiario,
                'aplica_sso'            => (int)$t['aplica_sso'],
                'aplica_rpe'            => (int)$t['aplica_rpe'],
                'aplica_faov'           => (int)$t['aplica_faov'],
                'porcentaje_islr'       => $porcIslr,
                'dias_habiles'          => $diasHabiles,
                'dias_descanso'         => $diasDescanso,
                'dias_feriados'         => $diasFeriados,
                'dias_trabajados'       => $diasTrabajados,
                'monto_dias_habiles'    => $montoHabiles,
                'monto_dias_descanso'   => $montoDescanso,
                'monto_dias_feriados'   => $montoFeriados,
                'sueldo_basico_periodo' => $sueldoBasico,
                'horas_extras'          => $horasExtras,
                'bonificaciones'        => $bonificaciones,
                'retencion_sso'         => $retSso,
                'retencion_rpe'         => $retRpe,
                'retencion_faov'        => $retFaov,
                'retencion_islr'        => $retIslr,
                'otras_deducciones'     => $otrasDeducciones,
                'aporte_patronal_sso'   => $aporteSso,
                'aporte_patronal_rpe'   => $aporteRpe,
                'aporte_patronal_faov'  => $aporteFaov,
                'aporte_patronal_inces' => $aporteInces,
                'total_asignaciones'    => $totalAsignaciones,
                'total_deducciones'     => $totalDeducciones,
                'neto_pagar'            => $netoPagar,
            ];
        }

        return [
            'config'          => $config,
            'tipo_frecuencia' => $tipoFrecuencia,
            'lunes_del_mes'   => $lunesDelMes,
            'filas'           => $filas,
        ];
    }

    /**
     * Genera el próximo código correlativo de lote de nómina para una empresa y período
     */
    public function generarProximoCodigoNomina(int $empresaId, string $periodo, string $tipoFrecuencia): string
    {
        $sufijo = match ($tipoFrecuencia) {
            'PRIMERA_QUINCENA' => 'Q1',
            'SEGUNDA_QUINCENA' => 'Q2',
            'MENSUAL'          => 'MEN',
            default            => 'LOT',
        };
        $prefijo = 'NOM-' . str_replace('-', '', $periodo) . '-' . $sufijo . '-';

        $stmt = $this->db->prepare(
            'SELECT codigo_nomina
             FROM nominas
             WHERE empresa_id = :empresa_id AND codigo_nomina LIKE :prefijo
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':prefijo'    => $prefijo . '%',
        ]);

        $ultimo = $stmt->fetchColumn();
        $secuencia = 1;
        if (is_string($ultimo) && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $secuencia = (int)$m[1] + 1;
        }

        return $prefijo . str_pad((string)$secuencia, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Procesa y guarda un lote de nómina dentro de una transacción PDO y opcionalmente genera su Asiento Contable
     */
    public function procesarYGuardarNomina(int $empresaId, int $usuarioId, array $payload): array
    {
        $periodo        = trim((string)($payload['periodo'] ?? date('Y-m')));
        $tipoFrecuencia = strtoupper(trim((string)($payload['tipo_frecuencia'] ?? 'PRIMERA_QUINCENA')));
        $fechaDesde     = trim((string)($payload['fecha_desde'] ?? date('Y-m-01')));
        $fechaHasta     = trim((string)($payload['fecha_hasta'] ?? date('Y-m-15')));
        $lunesInput     = (int)($payload['lunes_del_mes'] ?? 4);
        $lunesDelMes    = in_array($lunesInput, [4, 5], true) ? $lunesInput : 4;
        $descripcion    = trim((string)($payload['descripcion'] ?? 'Nómina Regular ' . $periodo));
        $generarAsiento = !empty($payload['generar_asiento']);
        $filas          = is_array($payload['filas'] ?? null) ? $payload['filas'] : [];

        if (empty($filas)) {
            throw new RuntimeException('El lote de nómina debe incluir al menos un trabajador.');
        }

        if (!in_array($tipoFrecuencia, ['PRIMERA_QUINCENA', 'SEGUNDA_QUINCENA', 'MENSUAL', 'CESTATICKET'], true)) {
            $tipoFrecuencia = 'PRIMERA_QUINCENA';
        }

        $config   = $this->obtenerConfiguracionLaboral($empresaId);
        $diasBase = max(1, (int)$config['dias_base_calculo']);

        $inTransaction = $this->db->inTransaction();
        if (!$inTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $codigoNomina = $this->generarProximoCodigoNomina($empresaId, $periodo, $tipoFrecuencia);

            $totalAsignacionesLote   = 0.0;
            $totalDeduccionesLote    = 0.0;
            $totalNetoLote           = 0.0;
            $totalAportePatronalLote = 0.0;

            $acumCuentas = [
                'sueldos_empleados'  => 0.0,
                'salarios_obreros'   => 0.0,
                'sueldos_directivos' => 0.0,
                'domingos_feriados'  => 0.0,
                'horas_extras'       => 0.0,
                'bonificaciones'     => 0.0,
                'gasto_sso_pat'      => 0.0,
                'gasto_rpe_pat'      => 0.0,
                'gasto_faov_pat'     => 0.0,
                'gasto_inces_pat'    => 0.0,
                'ret_sso'            => 0.0,
                'ret_rpe'            => 0.0,
                'ret_faov'           => 0.0,
                'ret_islr'           => 0.0,
                'otras_ded'          => 0.0,
            ];

            $detallesPreparados = [];

            foreach ($filas as $f) {
                $trabajadorId = (int)($f['trabajador_id'] ?? 0);
                $trabajador   = $this->obtenerTrabajador($empresaId, $trabajadorId);
                if (!$trabajador) {
                    continue;
                }

                $salarioMensual = (float)$trabajador['salario_mensual'];
                $sueldoDiario   = round($salarioMensual / $diasBase, 2);

                $diasHabiles    = max(0.0, round((float)($f['dias_habiles'] ?? 11.0), 2));
                $diasDescanso   = max(0.0, round((float)($f['dias_descanso'] ?? 4.0), 2));
                $diasFeriados   = max(0.0, round((float)($f['dias_feriados'] ?? 0.0), 2));
                $diasTrabajados = round($diasHabiles + $diasDescanso + $diasFeriados, 2);

                $montoHabiles   = round($sueldoDiario * $diasHabiles, 2);
                $montoDescanso  = round($sueldoDiario * $diasDescanso, 2);
                $montoFeriados  = round($sueldoDiario * $diasFeriados * 1.5, 2); // Recargo legal día feriado trabajado
                $sueldoBasico   = round($montoHabiles + $montoDescanso + $montoFeriados, 2);

                $horasExtras      = max(0.0, round((float)($f['horas_extras'] ?? 0.0), 2));
                $bonificaciones   = max(0.0, round((float)($f['bonificaciones'] ?? 0.0), 2));
                $retSso           = max(0.0, round((float)($f['retencion_sso'] ?? 0.0), 2));
                $retRpe           = max(0.0, round((float)($f['retencion_rpe'] ?? 0.0), 2));
                $retFaov          = max(0.0, round((float)($f['retencion_faov'] ?? 0.0), 2));
                $retIslr          = max(0.0, round((float)($f['retencion_islr'] ?? 0.0), 2));
                $otrasDeducciones = max(0.0, round((float)($f['otras_deducciones'] ?? 0.0), 2));

                $totalAsignaciones = round($sueldoBasico + $horasExtras + $bonificaciones, 2);
                $totalDeducciones  = round($retSso + $retRpe + $retFaov + $retIslr + $otrasDeducciones, 2);
                $netoPagar         = round($totalAsignaciones - $totalDeducciones, 2);

                if ($netoPagar < 0) {
                    throw new RuntimeException("El neto a pagar del trabajador {$trabajador['nombre_completo']} no puede ser negativo.");
                }

                // Aportes patronales
                $factorPeriodoSso = $tipoFrecuencia === 'MENSUAL' ? 1.0 : 0.5;
                $topeMensualSso   = (float)$config['salario_minimo_referencia'] * (int)$config['tope_salarios_minimos_sso'];
                $baseSsoMensual   = $topeMensualSso > 0 ? min($salarioMensual, $topeMensualSso) : $salarioMensual;
                $baseSemanalSso   = ($baseSsoMensual * 12.0) / 52.0;

                $aporteSso   = ((int)$trabajador['aplica_sso'] === 1)
                    ? round($baseSemanalSso * $lunesDelMes * ((float)$config['porcentaje_sso_patronal'] / 100.0) * $factorPeriodoSso, 2)
                    : 0.00;
                $aporteRpe   = ((int)$trabajador['aplica_rpe'] === 1)
                    ? round($baseSemanalSso * $lunesDelMes * ((float)$config['porcentaje_rpe_patronal'] / 100.0) * $factorPeriodoSso, 2)
                    : 0.00;
                $aporteFaov  = ((int)$trabajador['aplica_faov'] === 1)
                    ? round($totalAsignaciones * ((float)$config['porcentaje_faov_patronal'] / 100.0), 2)
                    : 0.00;
                $aporteInces = round($totalAsignaciones * ((float)$config['porcentaje_inces_patronal'] / 100.0), 2);

                $totalAsignacionesLote   = round($totalAsignacionesLote + $totalAsignaciones, 2);
                $totalDeduccionesLote    = round($totalDeduccionesLote + $totalDeducciones, 2);
                $totalNetoLote           = round($totalNetoLote + $netoPagar, 2);
                $totalAportePatronalLote = round($totalAportePatronalLote + $aporteSso + $aporteRpe + $aporteFaov + $aporteInces, 2);

                // Acumular rubros contables según tipo de trabajador
                $tipoTrab = (string)$trabajador['tipo_trabajador'];
                if ($tipoTrab === 'OBRERO') {
                    $acumCuentas['salarios_obreros'] += $montoHabiles;
                } elseif ($tipoTrab === 'DIRECTIVO') {
                    $acumCuentas['sueldos_directivos'] += $montoHabiles;
                } else {
                    $acumCuentas['sueldos_empleados'] += $montoHabiles;
                }

                $acumCuentas['domingos_feriados'] += ($montoDescanso + $montoFeriados);
                $acumCuentas['horas_extras']      += $horasExtras;
                $acumCuentas['bonificaciones']    += $bonificaciones;
                $acumCuentas['gasto_sso_pat']     += $aporteSso;
                $acumCuentas['gasto_rpe_pat']     += $aporteRpe;
                $acumCuentas['gasto_faov_pat']    += $aporteFaov;
                $acumCuentas['gasto_inces_pat']   += $aporteInces;
                $acumCuentas['ret_sso']           += $retSso;
                $acumCuentas['ret_rpe']           += $retRpe;
                $acumCuentas['ret_faov']          += $retFaov;
                $acumCuentas['ret_islr']          += $retIslr;
                $acumCuentas['otras_ded']         += $otrasDeducciones;

                $detallesPreparados[] = [
                    'trabajador_id'         => $trabajadorId,
                    'dias_trabajados'       => $diasTrabajados,
                    'dias_habiles'          => $diasHabiles,
                    'dias_descanso'         => $diasDescanso,
                    'dias_feriados'         => $diasFeriados,
                    'sueldo_diario'         => $sueldoDiario,
                    'sueldo_basico_periodo' => $sueldoBasico,
                    'monto_dias_habiles'    => $montoHabiles,
                    'monto_dias_descanso'   => $montoDescanso,
                    'monto_dias_feriados'   => $montoFeriados,
                    'horas_extras'          => $horasExtras,
                    'bonificaciones'        => $bonificaciones,
                    'retencion_sso'         => $retSso,
                    'retencion_rpe'         => $retRpe,
                    'retencion_faov'        => $retFaov,
                    'retencion_islr'        => $retIslr,
                    'otras_deducciones'     => $otrasDeducciones,
                    'aporte_patronal_sso'   => $aporteSso,
                    'aporte_patronal_rpe'   => $aporteRpe,
                    'aporte_patronal_faov'  => $aporteFaov,
                    'aporte_patronal_inces' => $aporteInces,
                    'total_asignaciones'    => $totalAsignaciones,
                    'total_deducciones'     => $totalDeducciones,
                    'neto_pagar'            => $netoPagar,
                ];
            }

            if (empty($detallesPreparados)) {
                throw new RuntimeException('No se encontraron trabajadores válidos para procesar el lote.');
            }

            // Insertar cabecera en nominas
            $stmtNom = $this->db->prepare(
                'INSERT INTO nominas (
                    empresa_id, codigo_nomina, periodo, tipo_frecuencia,
                    fecha_desde, fecha_hasta, lunes_del_mes, descripcion,
                    total_asignaciones, total_deducciones, total_cestaticket,
                    total_neto_pagar, total_aporte_patronal, estado
                ) VALUES (
                    :empresa_id, :codigo_nomina, :periodo, :tipo_frecuencia,
                    :fecha_desde, :fecha_hasta, :lunes_del_mes, :descripcion,
                    :total_asignaciones, :total_deducciones, 0.00,
                    :total_neto_pagar, :total_aporte_patronal, "PROCESADA"
                )'
            );
            $stmtNom->execute([
                ':empresa_id'            => $empresaId,
                ':codigo_nomina'         => $codigoNomina,
                ':periodo'               => $periodo,
                ':tipo_frecuencia'       => $tipoFrecuencia,
                ':fecha_desde'           => $fechaDesde,
                ':fecha_hasta'           => $fechaHasta,
                ':lunes_del_mes'         => $lunesDelMes,
                ':descripcion'           => $descripcion,
                ':total_asignaciones'    => $totalAsignacionesLote,
                ':total_deducciones'     => $totalDeduccionesLote,
                ':total_neto_pagar'      => $totalNetoLote,
                ':total_aporte_patronal' => $totalAportePatronalLote,
            ]);

            $nominaId = (int)$this->db->lastInsertId();

            // Insertar detalles por trabajador
            $stmtDet = $this->db->prepare(
                'INSERT INTO nomina_detalles (
                    nomina_id, empresa_id, trabajador_id,
                    dias_trabajados, dias_habiles, dias_descanso, dias_feriados, sueldo_diario,
                    sueldo_basico_periodo, monto_dias_habiles, monto_dias_descanso, monto_dias_feriados,
                    horas_extras, bonificaciones, cestaticket,
                    retencion_sso, retencion_rpe, retencion_faov, retencion_islr, otras_deducciones,
                    aporte_patronal_sso, aporte_patronal_rpe, aporte_patronal_faov, aporte_patronal_inces,
                    total_asignaciones, total_deducciones, neto_pagar
                ) VALUES (
                    :nomina_id, :empresa_id, :trabajador_id,
                    :dias_trabajados, :dias_habiles, :dias_descanso, :dias_feriados, :sueldo_diario,
                    :sueldo_basico_periodo, :monto_dias_habiles, :monto_dias_descanso, :monto_dias_feriados,
                    :horas_extras, :bonificaciones, 0.00,
                    :retencion_sso, :retencion_rpe, :retencion_faov, :retencion_islr, :otras_deducciones,
                    :aporte_patronal_sso, :aporte_patronal_rpe, :aporte_patronal_faov, :aporte_patronal_inces,
                    :total_asignaciones, :total_deducciones, :neto_pagar
                )'
            );

            foreach ($detallesPreparados as $d) {
                $stmtDet->execute([
                    ':nomina_id'             => $nominaId,
                    ':empresa_id'            => $empresaId,
                    ':trabajador_id'         => $d['trabajador_id'],
                    ':dias_trabajados'       => $d['dias_trabajados'],
                    ':dias_habiles'          => $d['dias_habiles'],
                    ':dias_descanso'         => $d['dias_descanso'],
                    ':dias_feriados'         => $d['dias_feriados'],
                    ':sueldo_diario'         => $d['sueldo_diario'],
                    ':sueldo_basico_periodo' => $d['sueldo_basico_periodo'],
                    ':monto_dias_habiles'    => $d['monto_dias_habiles'],
                    ':monto_dias_descanso'   => $d['monto_dias_descanso'],
                    ':monto_dias_feriados'   => $d['monto_dias_feriados'],
                    ':horas_extras'          => $d['horas_extras'],
                    ':bonificaciones'        => $d['bonificaciones'],
                    ':retencion_sso'         => $d['retencion_sso'],
                    ':retencion_rpe'         => $d['retencion_rpe'],
                    ':retencion_faov'        => $d['retencion_faov'],
                    ':retencion_islr'        => $d['retencion_islr'],
                    ':otras_deducciones'     => $d['otras_deducciones'],
                    ':aporte_patronal_sso'   => $d['aporte_patronal_sso'],
                    ':aporte_patronal_rpe'   => $d['aporte_patronal_rpe'],
                    ':aporte_patronal_faov'  => $d['aporte_patronal_faov'],
                    ':aporte_patronal_inces' => $d['aporte_patronal_inces'],
                    ':total_asignaciones'    => $d['total_asignaciones'],
                    ':total_deducciones'     => $d['total_deducciones'],
                    ':neto_pagar'            => $d['neto_pagar'],
                ]);
            }

            $asientoId = null;
            if ($generarAsiento && $totalAsignacionesLote > 0) {
                $asientoId = $this->generarAsientoContableNomina(
                    $empresaId,
                    $usuarioId,
                    $codigoNomina,
                    $fechaHasta,
                    $descripcion,
                    $acumCuentas,
                    $totalNetoLote
                );

                if ($asientoId !== null) {
                    $stmtUpd = $this->db->prepare(
                        'UPDATE nominas SET asiento_id = :asiento_id WHERE id = :id AND empresa_id = :empresa_id'
                    );
                    $stmtUpd->execute([
                        ':asiento_id' => $asientoId,
                        ':id'         => $nominaId,
                        ':empresa_id' => $empresaId,
                    ]);
                }
            }

            if (!$inTransaction) {
                $this->db->commit();
            }

            return [
                'nomina_id'     => $nominaId,
                'codigo_nomina' => $codigoNomina,
                'asiento_id'    => $asientoId,
                'neto_total'    => $totalNetoLote,
            ];
        } catch (Throwable $e) {
            if (!$inTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new RuntimeException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Construye y registra el asiento contable de partida doble para el lote de nómina
     */
    private function generarAsientoContableNomina(
        int $empresaId,
        int $usuarioId,
        string $codigoNomina,
        string $fecha,
        string $descripcion,
        array $acumCuentas,
        float $totalNeto
    ): ?int {
        $lineas = [];

        // Cargos a Gastos (DEBE)
        $mapaDebe = [
            'sueldos_empleados'  => ['6.1.01.001', 'Gasto Sueldos Empleados - ' . $codigoNomina],
            'salarios_obreros'   => ['6.1.01.002', 'Gasto Salarios Obreros - ' . $codigoNomina],
            'sueldos_directivos' => ['6.1.01.003', 'Gasto Sueldos Directivos - ' . $codigoNomina],
            'domingos_feriados'  => ['6.1.01.006', 'Gasto Días de Descanso y Feriados - ' . $codigoNomina],
            'horas_extras'       => ['6.1.01.007', 'Gasto Horas Extras - ' . $codigoNomina],
            'bonificaciones'     => ['6.1.01.005', 'Gasto Bonificaciones y Producción - ' . $codigoNomina],
            'gasto_sso_pat'      => ['6.1.01.016', 'Aporte Patronal IVSS (SSO) - ' . $codigoNomina],
            'gasto_rpe_pat'      => ['6.1.01.019', 'Aporte Patronal Paro Forzoso (RPE) - ' . $codigoNomina],
            'gasto_faov_pat'     => ['6.1.01.018', 'Aporte Patronal FAOV / Banavih - ' . $codigoNomina],
            'gasto_inces_pat'    => ['6.1.01.017', 'Aporte Patronal INCES - ' . $codigoNomina],
        ];

        foreach ($mapaDebe as $clave => [$codigoCuenta, $glosa]) {
            $monto = round((float)($acumCuentas[$clave] ?? 0.0), 2);
            if ($monto > 0) {
                $cuentaId = $this->obtenerCuentaIdPorCodigo($empresaId, $codigoCuenta, '6.1.01.001');
                if ($cuentaId !== null) {
                    $lineas[] = [
                        'cuenta_id'   => $cuentaId,
                        'descripcion' => $glosa,
                        'debe'        => $monto,
                        'haber'       => 0.00,
                    ];
                }
            }
        }

        // Abonos a Pasivos de Retenciones, Aportes y Sueldos por Pagar (HABER)
        $pasivoSsoRet   = round((float)($acumCuentas['ret_sso'] ?? 0.0), 2);
        $pasivoRpeTotal = round((float)($acumCuentas['ret_rpe'] ?? 0.0) + (float)($acumCuentas['gasto_rpe_pat'] ?? 0.0), 2);
        $pasivoFaovRet  = round((float)($acumCuentas['ret_faov'] ?? 0.0), 2);
        $pasivoFaovPat  = round((float)($acumCuentas['gasto_faov_pat'] ?? 0.0), 2);
        $pasivoSsoPat   = round((float)($acumCuentas['gasto_sso_pat'] ?? 0.0), 2);
        $pasivoIncesPat = round((float)($acumCuentas['gasto_inces_pat'] ?? 0.0), 2);
        $pasivoIslrRet  = round((float)($acumCuentas['ret_islr'] ?? 0.0), 2);
        $pasivoOtrasDed = round((float)($acumCuentas['otras_ded'] ?? 0.0), 2);

        $mapaHaber = [
            ['2.3.02.001', $pasivoSsoRet,   'Retención IVSS Trabajadores por Pagar - ' . $codigoNomina],
            ['2.3.02.002', $pasivoRpeTotal, 'Retención y Aporte RPE por Pagar - ' . $codigoNomina],
            ['2.3.02.003', $pasivoFaovRet,  'Retención FAOV Trabajadores por Pagar - ' . $codigoNomina],
            ['2.3.02.004', $pasivoIncesPat, 'Aporte Patronal INCES por Pagar - ' . $codigoNomina],
            ['2.3.02.005', $pasivoFaovPat,  'Aporte Patronal FAOV por Pagar - ' . $codigoNomina],
            ['2.3.02.006', $pasivoSsoPat,   'Aporte Patronal IVSS por Pagar - ' . $codigoNomina],
            ['2.3.03.003', $pasivoIslrRet,  'Retención ISLR sobre Sueldos por Pagar - ' . $codigoNomina],
            ['2.2.01.001', $pasivoOtrasDed, 'Otras Deducciones / Préstamos Personal - ' . $codigoNomina],
            ['2.4.01.004', round($totalNeto, 2), 'Sueldos y Salarios por Pagar (Neto Nómina) - ' . $codigoNomina],
        ];

        foreach ($mapaHaber as [$codigoCuenta, $monto, $glosa]) {
            if ($monto > 0) {
                $cuentaId = $this->obtenerCuentaIdPorCodigo($empresaId, $codigoCuenta, '2.4.01.004');
                if ($cuentaId !== null) {
                    $lineas[] = [
                        'cuenta_id'   => $cuentaId,
                        'descripcion' => $glosa,
                        'debe'        => 0.00,
                        'haber'       => $monto,
                    ];
                }
            }
        }

        if (count($lineas) < 2) {
            return null;
        }

        $asientoModel = new AsientoModel();
        return $asientoModel->crearAsiento(
            $empresaId,
            $usuarioId,
            [
                'fecha'      => $fecha,
                'concepto'   => 'Causación de Nómina LOTTT: ' . $descripcion,
                'referencia' => $codigoNomina,
                'origen'     => 'NOMINA',
            ],
            $lineas
        );
    }

    /**
     * Busca el ID de una cuenta de movimiento por código (o código alternativo de respaldo)
     */
    private function obtenerCuentaIdPorCodigo(int $empresaId, string $codigoPreferido, string $codigoRespaldo): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM plan_cuentas
             WHERE empresa_id = :empresa_id AND codigo = :codigo AND acepta_movimiento = 1
             LIMIT 1'
        );
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':codigo'     => $codigoPreferido,
        ]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int)$id;
        }

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':codigo'     => $codigoRespaldo,
        ]);
        $idFallback = $stmt->fetchColumn();
        return $idFallback ? (int)$idFallback : null;
    }

    /**
     * Anula un lote de nómina y su asiento contable vinculado
     */
    public function anularNomina(int $empresaId, int $nominaId): void
    {
        $nomina = $this->obtenerNominaConDetalles($empresaId, $nominaId);
        if (!$nomina) {
            throw new RuntimeException('El lote de nómina seleccionado no pertenece a la empresa activa.');
        }

        if ($nomina['estado'] === 'ANULADA') {
            throw new RuntimeException('Este lote de nómina ya se encuentra anulado.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE nominas SET estado = "ANULADA" WHERE id = :id AND empresa_id = :empresa_id'
            );
            $stmt->execute([
                ':id'         => $nominaId,
                ':empresa_id' => $empresaId,
            ]);

            if (!empty($nomina['asiento_id'])) {
                $stmtAsiento = $this->db->prepare(
                    'UPDATE asientos SET estado = "ANULADO" WHERE id = :id AND empresa_id = :empresa_id'
                );
                $stmtAsiento->execute([
                    ':id'         => (int)$nomina['asiento_id'],
                    ':empresa_id' => $empresaId,
                ]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new RuntimeException('Error al anular el lote de nómina: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 4. MÓDULO: BONO DE ALIMENTACIÓN / CESTATICKET SOCIALISTA
    // =========================================================================

    /**
     * Precalcula la hoja mensual de Bono de Alimentación para los trabajadores con derecho a Cestaticket
     */
    public function precalcularCestaticket(int $empresaId, string $periodo): array
    {
        $config       = $this->obtenerConfiguracionLaboral($empresaId);
        $montoMensual = (float)$config['monto_cestaticket_mensual'];
        $diasBase     = 30;
        $valorDiario  = round($montoMensual / $diasBase, 2);

        $trabajadores = $this->listarTrabajadores($empresaId);
        $filas = [];

        foreach ($trabajadores as $t) {
            if ((int)$t['activo'] !== 1 || ($t['estatus_laboral'] ?? 'ACTIVO') === 'EGRESADO' || (int)$t['aplica_cestaticket'] !== 1) {
                continue;
            }

            $filas[] = [
                'trabajador_id'      => (int)$t['id'],
                'cedula'             => (string)$t['cedula'],
                'nombre_completo'    => (string)$t['nombre_completo'],
                'cargo'              => (string)$t['cargo'],
                'departamento'       => (string)$t['departamento'],
                'fecha_ingreso'      => (string)$t['fecha_ingreso'],
                'dias_habiles'       => 22.00,
                'dias_descanso'      => 8.00,
                'dias_feriados'      => 0.00,
                'dias_inasistencia'  => 0.00,
                'dias_efectivos'     => 30.00,
                'monto_mensual_base' => $montoMensual,
                'valor_diario'       => $valorDiario,
                'monto_descuento'    => 0.00,
                'total_cestaticket'  => $montoMensual,
            ];
        }

        return [
            'periodo'            => $periodo,
            'monto_mensual_base' => $montoMensual,
            'valor_diario'       => $valorDiario,
            'filas'              => $filas,
        ];
    }

    /**
     * Lista los lotes de Bono de Alimentación procesados por la empresa activa
     */
    public function listarLotesCestaticket(int $empresaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, a.numero_asiento
             FROM cestaticket_lotes c
             LEFT JOIN asientos a ON a.id = c.asiento_id AND a.empresa_id = c.empresa_id
             WHERE c.empresa_id = :empresa_id
             ORDER BY c.periodo DESC, c.id DESC'
        );
        $stmt->execute([':empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un lote de Cestaticket con el detalle de cada trabajador
     */
    public function obtenerLoteCestaticket(int $empresaId, int $loteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, a.numero_asiento
             FROM cestaticket_lotes c
             LEFT JOIN asientos a ON a.id = c.asiento_id AND a.empresa_id = c.empresa_id
             WHERE c.id = :id AND c.empresa_id = :empresa_id
             LIMIT 1'
        );
        $stmt->execute([
            ':id'         => $loteId,
            ':empresa_id' => $empresaId,
        ]);
        $lote = $stmt->fetch();

        if (!$lote) {
            return null;
        }

        $stmtDet = $this->db->prepare(
            'SELECT
                cd.*,
                t.cedula,
                t.nombres,
                t.apellidos,
                CONCAT(t.nombres, " ", t.apellidos) AS nombre_completo,
                t.cargo,
                t.departamento,
                t.fecha_ingreso
             FROM cestaticket_detalles cd
             INNER JOIN trabajadores t ON t.id = cd.trabajador_id AND t.empresa_id = cd.empresa_id
             WHERE cd.lote_id = :lote_id AND cd.empresa_id = :empresa_id
             ORDER BY t.apellidos ASC, t.nombres ASC'
        );
        $stmtDet->execute([
            ':lote_id'    => $loteId,
            ':empresa_id' => $empresaId,
        ]);

        $lote['detalles'] = $stmtDet->fetchAll();
        return $lote;
    }

    /**
     * Obtiene un recibo individual de Cestaticket por su ID de detalle (cestaticket_detalles.id)
     */
    public function obtenerReciboCestaticketIndividual(int $empresaId, int $detalleId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT
                cd.*,
                c.codigo_lote,
                c.periodo,
                c.fecha_emision,
                c.observaciones,
                t.cedula,
                t.nombres,
                t.apellidos,
                CONCAT(t.nombres, " ", t.apellidos) AS nombre_completo,
                t.cargo,
                t.departamento,
                t.fecha_ingreso
             FROM cestaticket_detalles cd
             INNER JOIN cestaticket_lotes c ON c.id = cd.lote_id AND c.empresa_id = cd.empresa_id
             INNER JOIN trabajadores t ON t.id = cd.trabajador_id AND t.empresa_id = cd.empresa_id
             WHERE cd.id = :id AND cd.empresa_id = :empresa_id
             LIMIT 1'
        );
        $stmt->execute([
            ':id'         => $detalleId,
            ':empresa_id' => $empresaId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Guarda un lote mensual de Cestaticket y opcionalmente genera su asiento contable
     */
    public function guardarLoteCestaticket(int $empresaId, int $usuarioId, array $payload): array
    {
        $periodo          = trim((string)($payload['periodo'] ?? date('Y-m')));
        $fechaEmision     = trim((string)($payload['fecha_emision'] ?? date('Y-m-d')));
        $montoMensualBase = max(1.0, round((float)($payload['monto_mensual_base'] ?? 1800.00), 2));
        $observaciones    = trim((string)($payload['observaciones'] ?? 'Bono de Alimentación ' . $periodo));
        $generarAsiento   = !empty($payload['generar_asiento']);
        $filas            = is_array($payload['filas'] ?? null) ? $payload['filas'] : [];

        if (empty($filas)) {
            throw new RuntimeException('Debe incluir al menos un trabajador en el lote de Cestaticket.');
        }

        $valorDiario = round($montoMensualBase / 30.0, 2);

        $inTransaction = $this->db->inTransaction();
        if (!$inTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $prefijo = 'CEST-' . str_replace('-', '', $periodo) . '-';
            $stmtUlt = $this->db->prepare(
                'SELECT codigo_lote FROM cestaticket_lotes WHERE empresa_id = :empresa_id AND codigo_lote LIKE :pref ORDER BY id DESC LIMIT 1'
            );
            $stmtUlt->execute([
                ':empresa_id' => $empresaId,
                ':pref'       => $prefijo . '%',
            ]);
            $ultimo = $stmtUlt->fetchColumn();
            $sec = 1;
            if (is_string($ultimo) && preg_match('/-(\d+)$/', $ultimo, $m)) {
                $sec = (int)$m[1] + 1;
            }
            $codigoLote = $prefijo . str_pad((string)$sec, 2, '0', STR_PAD_LEFT);

            $totalPagarLote = 0.0;
            $detallesListos = [];

            foreach ($filas as $f) {
                $trabajadorId     = (int)($f['trabajador_id'] ?? 0);
                $diasHabiles      = max(0.0, round((float)($f['dias_habiles'] ?? 22.0), 2));
                $diasDescanso     = max(0.0, round((float)($f['dias_descanso'] ?? 8.0), 2));
                $diasFeriados     = max(0.0, round((float)($f['dias_feriados'] ?? 0.0), 2));
                $diasInasistencia = max(0.0, min(30.0, round((float)($f['dias_inasistencia'] ?? 0.0), 2)));

                $diasBrutos       = min(30.0, round($diasHabiles + $diasDescanso + $diasFeriados, 2));
                $diasEfectivos    = max(0.0, round($diasBrutos - $diasInasistencia, 2));
                $montoDescuento   = round($diasInasistencia * $valorDiario, 2);
                $totalTrabajador  = max(0.0, round($diasEfectivos * $valorDiario, 2));

                $totalPagarLote   = round($totalPagarLote + $totalTrabajador, 2);

                $detallesListos[] = [
                    'trabajador_id'      => $trabajadorId,
                    'dias_habiles'       => $diasHabiles,
                    'dias_descanso'      => $diasDescanso,
                    'dias_feriados'      => $diasFeriados,
                    'dias_inasistencia'  => $diasInasistencia,
                    'dias_efectivos'     => $diasEfectivos,
                    'monto_mensual_base' => $montoMensualBase,
                    'valor_diario'       => $valorDiario,
                    'monto_descuento'    => $montoDescuento,
                    'total_cestaticket'  => $totalTrabajador,
                ];
            }

            $stmtIns = $this->db->prepare(
                'INSERT INTO cestaticket_lotes (
                    empresa_id, codigo_lote, periodo, fecha_emision,
                    monto_mensual_base, total_trabajadores, total_pagar, observaciones, estado
                ) VALUES (
                    :empresa_id, :codigo_lote, :periodo, :fecha_emision,
                    :monto_mensual_base, :total_trabajadores, :total_pagar, :observaciones, "PROCESADO"
                )'
            );
            $stmtIns->execute([
                ':empresa_id'         => $empresaId,
                ':codigo_lote'        => $codigoLote,
                ':periodo'            => $periodo,
                ':fecha_emision'      => $fechaEmision,
                ':monto_mensual_base' => $montoMensualBase,
                ':total_trabajadores' => count($detallesListos),
                ':total_pagar'        => $totalPagarLote,
                ':observaciones'      => $observaciones,
            ]);

            $loteId = (int)$this->db->lastInsertId();

            $stmtDet = $this->db->prepare(
                'INSERT INTO cestaticket_detalles (
                    lote_id, empresa_id, trabajador_id,
                    dias_habiles, dias_descanso, dias_feriados, dias_inasistencia, dias_efectivos,
                    monto_mensual_base, valor_diario, monto_descuento, total_cestaticket
                ) VALUES (
                    :lote_id, :empresa_id, :trabajador_id,
                    :dias_habiles, :dias_descanso, :dias_feriados, :dias_inasistencia, :dias_efectivos,
                    :monto_mensual_base, :valor_diario, :monto_descuento, :total_cestaticket
                )'
            );

            foreach ($detallesListos as $d) {
                $stmtDet->execute([
                    ':lote_id'            => $loteId,
                    ':empresa_id'         => $empresaId,
                    ':trabajador_id'      => $d['trabajador_id'],
                    ':dias_habiles'       => $d['dias_habiles'],
                    ':dias_descanso'      => $d['dias_descanso'],
                    ':dias_feriados'      => $d['dias_feriados'],
                    ':dias_inasistencia'  => $d['dias_inasistencia'],
                    ':dias_efectivos'     => $d['dias_efectivos'],
                    ':monto_mensual_base' => $d['monto_mensual_base'],
                    ':valor_diario'       => $d['valor_diario'],
                    ':monto_descuento'    => $d['monto_descuento'],
                    ':total_cestaticket'  => $d['total_cestaticket'],
                ]);
            }

            $asientoId = null;
            if ($generarAsiento && $totalPagarLote > 0) {
                $cuentaGastoCest = $this->obtenerCuentaIdPorCodigo($empresaId, '6.1.01.009', '6.1.01.001');
                $cuentaPasivo    = $this->obtenerCuentaIdPorCodigo($empresaId, '2.4.01.004', '2.2.01.001');

                if ($cuentaGastoCest !== null && $cuentaPasivo !== null) {
                    $asientoModel = new AsientoModel();
                    $asientoId = $asientoModel->crearAsiento(
                        $empresaId,
                        $usuarioId,
                        [
                            'fecha'      => $fechaEmision,
                            'concepto'   => 'Causación de Bono de Alimentación (Cestaticket): ' . $observaciones,
                            'referencia' => $codigoLote,
                            'origen'     => 'NOMINA',
                        ],
                        [
                            [
                                'cuenta_id'   => $cuentaGastoCest,
                                'descripcion' => 'Gasto Beneficio de Alimentación - ' . $codigoLote,
                                'debe'        => $totalPagarLote,
                                'haber'       => 0.00,
                            ],
                            [
                                'cuenta_id'   => $cuentaPasivo,
                                'descripcion' => 'Beneficio de Alimentación por Pagar - ' . $codigoLote,
                                'debe'        => 0.00,
                                'haber'       => $totalPagarLote,
                            ],
                        ]
                    );

                    $stmtUpd = $this->db->prepare(
                        'UPDATE cestaticket_lotes SET asiento_id = :asiento_id WHERE id = :id AND empresa_id = :empresa_id'
                    );
                    $stmtUpd->execute([
                        ':asiento_id' => $asientoId,
                        ':id'         => $loteId,
                        ':empresa_id' => $empresaId,
                    ]);
                }
            }

            if (!$inTransaction) {
                $this->db->commit();
            }

            return [
                'lote_id'     => $loteId,
                'codigo_lote' => $codigoLote,
                'asiento_id'  => $asientoId,
                'total_pagar' => $totalPagarLote,
            ];
        } catch (Throwable $e) {
            if (!$inTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new RuntimeException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }
}
