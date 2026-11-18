<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;
use Throwable;

/**
 * Modelo Multi-Empresa (Clientes del Escritorio Contable)
 */
final class EmpresaModel
{
    private PDO $db;
    private static bool $columnaBcvVerificada = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->asegurarColumnaTasaBcv();
    }

    /**
     * Garantiza que la columna tasa_bcv_usd exista en configuracion_fiscal
     */
    private function asegurarColumnaTasaBcv(): void
    {
        if (self::$columnaBcvVerificada) {
            return;
        }
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :tabla
                   AND COLUMN_NAME = :columna'
            );
            $stmt->execute([
                ':tabla'   => 'configuracion_fiscal',
                ':columna' => 'tasa_bcv_usd',
            ]);
            if ((int)$stmt->fetchColumn() === 0) {
                $this->db->exec('ALTER TABLE `configuracion_fiscal` ADD COLUMN `tasa_bcv_usd` DECIMAL(18,4) NOT NULL DEFAULT 45.0000 COMMENT "Tasa oficial BCV Bs./USD"');
            }

            $stmtLogo = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :tabla
                   AND COLUMN_NAME = :columna'
            );
            $stmtLogo->execute([
                ':tabla'   => 'empresas',
                ':columna' => 'logo_path',
            ]);
            if ((int)$stmtLogo->fetchColumn() === 0) {
                $this->db->exec('ALTER TABLE `empresas` ADD COLUMN `logo_path` VARCHAR(255) NULL DEFAULT NULL AFTER `moneda_principal`');
            }
            self::$columnaBcvVerificada = true;
        } catch (Throwable) {
            self::$columnaBcvVerificada = true;
        }
    }

    /**
     * Lista todas las empresas registradas con estadísticas de cuentas, asientos y nómina
     */
    public function listarTodas(?string $busqueda = null, ?string $estado = null): array
    {
        $sql = 'SELECT
                    e.*,
                    cf.valor_unidad_tributaria,
                    cf.alicuota_iva_general,
                    (SELECT COUNT(*) FROM plan_cuentas pc WHERE pc.empresa_id = e.id) AS total_cuentas,
                    (SELECT COUNT(*) FROM asientos a WHERE a.empresa_id = e.id AND a.estado = "PUBLICADO") AS total_asientos,
                    (SELECT COUNT(*) FROM trabajadores t WHERE t.empresa_id = e.id AND t.activo = 1) AS total_trabajadores
                FROM empresas e
                LEFT JOIN configuracion_fiscal cf ON cf.empresa_id = e.id
                WHERE 1=1';

        $params = [];

        if ($busqueda !== null && trim($busqueda) !== '') {
            $sql .= ' AND (e.rif LIKE :q1 OR e.razon_social LIKE :q2 OR e.nombre_comercial LIKE :q3)';
            $termino = '%' . trim($busqueda) . '%';
            $params[':q1'] = $termino;
            $params[':q2'] = $termino;
            $params[':q3'] = $termino;
        }

        if ($estado !== null && ($estado === '1' || $estado === '0')) {
            $sql .= ' AND e.activo = :activo';
            $params[':activo'] = (int)$estado;
        }

        $sql .= ' ORDER BY e.activo DESC, e.razon_social ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Lista únicamente las empresas activas para el selector global del Header
     */
    public function listarActivas(): array
    {
        $sql = 'SELECT id, rif, razon_social, nombre_comercial, tipo_contribuyente,
                       porcentaje_retencion_iva, periodo_fiscal_activo, moneda_principal, logo_path, activo
                FROM empresas
                WHERE activo = 1
                ORDER BY razon_social ASC';

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Obtiene una empresa por su ID incluyendo su configuración fiscal SENIAT/LOTTT
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = 'SELECT
                    e.*,
                    COALESCE(cf.alicuota_iva_general, 16.00) AS alicuota_iva_general,
                    COALESCE(cf.alicuota_iva_reducida, 8.00) AS alicuota_iva_reducida,
                    COALESCE(cf.alicuota_igtf, 3.00) AS alicuota_igtf,
                    COALESCE(cf.valor_unidad_tributaria, 9.00) AS valor_unidad_tributaria,
                    COALESCE(cf.factor_sustraendo_islr, 83.3334) AS factor_sustraendo_islr,
                    COALESCE(cf.monto_cestaticket_mensual, 1800.00) AS monto_cestaticket_mensual,
                    COALESCE(cf.porcentaje_sso_patronal, 9.00) AS porcentaje_sso_patronal,
                    COALESCE(cf.tasa_bcv_usd, 45.0000) AS tasa_bcv_usd
                FROM empresas e
                LEFT JOIN configuracion_fiscal cf ON cf.empresa_id = e.id
                WHERE e.id = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Verifica si un RIF ya se encuentra registrado en otra empresa
     */
    public function existeRif(string $rif, ?int $excluirId = null): bool
    {
        $sql = 'SELECT id FROM empresas WHERE rif = :rif';
        $params = [':rif' => strtoupper(trim($rif))];

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
     * Crea una nueva empresa cliente, su configuración fiscal y clona automáticamente el Plan de Cuentas Base
     * dentro de una transacción atómica PDO.
     */
    public function crearConPlanCuentas(array $datos): array
    {
        Database::beginTransaction();

        try {
            $sqlEmpresa = 'INSERT INTO empresas (
                                rif, razon_social, nombre_comercial, direccion_fiscal,
                                telefono, email_contacto, representante_legal,
                                tipo_contribuyente, porcentaje_retencion_iva,
                                periodo_fiscal_activo, moneda_principal, logo_path, activo
                           ) VALUES (
                                :rif, :razon_social, :nombre_comercial, :direccion_fiscal,
                                :telefono, :email_contacto, :representante_legal,
                                :tipo_contribuyente, :porcentaje_retencion_iva,
                                :periodo_fiscal_activo, :moneda_principal, :logo_path, 1
                           )';

            $stmt = $this->db->prepare($sqlEmpresa);
            $stmt->execute([
                ':rif'                      => strtoupper(trim($datos['rif'])),
                ':razon_social'             => trim($datos['razon_social']),
                ':nombre_comercial'         => trim($datos['nombre_comercial'] ?? '') ?: null,
                ':direccion_fiscal'         => trim($datos['direccion_fiscal']),
                ':telefono'                 => trim($datos['telefono'] ?? '') ?: null,
                ':email_contacto'           => trim($datos['email_contacto'] ?? '') ?: null,
                ':representante_legal'      => trim($datos['representante_legal'] ?? '') ?: null,
                ':tipo_contribuyente'       => $datos['tipo_contribuyente'],
                ':porcentaje_retencion_iva' => (string)$datos['porcentaje_retencion_iva'],
                ':periodo_fiscal_activo'    => $datos['periodo_fiscal_activo'],
                ':moneda_principal'         => $datos['moneda_principal'] ?? 'VES',
                ':logo_path'                => !empty($datos['logo_path']) ? (string)$datos['logo_path'] : null,
            ]);

            $empresaId = (int)$this->db->lastInsertId();

            // Crear registro en configuracion_fiscal
            $sqlConfig = 'INSERT INTO configuracion_fiscal (
                              empresa_id, alicuota_iva_general, valor_unidad_tributaria, monto_cestaticket_mensual
                          ) VALUES (
                              :empresa_id, :iva_general, :ut, :cestaticket
                          )';
            $stmtConfig = $this->db->prepare($sqlConfig);
            $stmtConfig->execute([
                ':empresa_id'  => $empresaId,
                ':iva_general' => (float)($datos['alicuota_iva_general'] ?? 16.00),
                ':ut'          => (float)($datos['valor_unidad_tributaria'] ?? 9.00),
                ':cestaticket' => (float)($datos['monto_cestaticket_mensual'] ?? 1800.00),
            ]);

            // Clonar automáticamente el Plan de Cuentas Base para la nueva empresa
            $planModel = new PlanCuentasModel();
            $cuentasClonadas = $planModel->clonarPlanBaseParaEmpresa($empresaId);

            Database::commit();

            return [
                'empresa_id'       => $empresaId,
                'cuentas_clonadas' => $cuentasClonadas,
            ];
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza los datos fiscales y de configuración de una empresa existente
     */
    public function actualizar(int $id, array $datos): bool
    {
        Database::beginTransaction();

        try {
            $setLogo = '';
            $paramsUpd = [
                ':rif'                      => strtoupper(trim($datos['rif'])),
                ':razon_social'             => trim($datos['razon_social']),
                ':nombre_comercial'         => trim($datos['nombre_comercial'] ?? '') ?: null,
                ':direccion_fiscal'         => trim($datos['direccion_fiscal']),
                ':telefono'                 => trim($datos['telefono'] ?? '') ?: null,
                ':email_contacto'           => trim($datos['email_contacto'] ?? '') ?: null,
                ':representante_legal'      => trim($datos['representante_legal'] ?? '') ?: null,
                ':tipo_contribuyente'       => $datos['tipo_contribuyente'],
                ':porcentaje_retencion_iva' => (string)$datos['porcentaje_retencion_iva'],
                ':periodo_fiscal_activo'    => $datos['periodo_fiscal_activo'],
                ':id'                       => $id,
            ];

            if (!empty($datos['logo_path'])) {
                $setLogo = ', logo_path = :logo_path';
                $paramsUpd[':logo_path'] = (string)$datos['logo_path'];
            } elseif (!empty($datos['eliminar_logo'])) {
                $setLogo = ', logo_path = NULL';
            }

            $sql = 'UPDATE empresas SET
                        rif = :rif,
                        razon_social = :razon_social,
                        nombre_comercial = :nombre_comercial,
                        direccion_fiscal = :direccion_fiscal,
                        telefono = :telefono,
                        email_contacto = :email_contacto,
                        representante_legal = :representante_legal,
                        tipo_contribuyente = :tipo_contribuyente,
                        porcentaje_retencion_iva = :porcentaje_retencion_iva,
                        periodo_fiscal_activo = :periodo_fiscal_activo' . $setLogo . '
                    WHERE id = :id';

            $stmt = $this->db->prepare($sql);
            $stmt->execute($paramsUpd);

            // Actualizar o insertar configuración fiscal asociada
            $sqlUpsertConfig = 'INSERT INTO configuracion_fiscal (
                                    empresa_id, valor_unidad_tributaria, monto_cestaticket_mensual
                                ) VALUES (
                                    :empresa_id, :ut, :cestaticket
                                ) ON DUPLICATE KEY UPDATE
                                    valor_unidad_tributaria = VALUES(valor_unidad_tributaria),
                                    monto_cestaticket_mensual = VALUES(monto_cestaticket_mensual)';

            $stmtCfg = $this->db->prepare($sqlUpsertConfig);
            $stmtCfg->execute([
                ':empresa_id'  => $id,
                ':ut'          => (float)($datos['valor_unidad_tributaria'] ?? 9.00),
                ':cestaticket' => (float)($datos['monto_cestaticket_mensual'] ?? 1800.00),
            ]);

            Database::commit();
            return true;
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Activa o inactiva una empresa cliente
     */
    public function cambiarEstado(int $id, int $nuevoEstado): bool
    {
        $sql = 'UPDATE empresas SET activo = :activo WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':activo' => $nuevoEstado === 1 ? 1 : 0,
            ':id'     => $id,
        ]);
    }

    /**
     * Actualiza la Tasa Oficial del Dólar BCV (Bs./USD) y opcionalmente recalcula
     * el Bono de Alimentación (Cestaticket) indexado a $40 USD BCV.
     */
    public function actualizarTasaBcv(int $empresaId, float $tasaBcv, bool $recalcularCestaticket = true, float $montoUsdCestaticket = 40.0): array
    {
        $tasaBcv = max(0.01, round($tasaBcv, 4));
        $montoUsdCestaticket = max(1.0, round($montoUsdCestaticket, 2));
        $nuevoCestaticketBs = round($tasaBcv * $montoUsdCestaticket, 2);

        if ($recalcularCestaticket) {
            $stmt = $this->db->prepare(
                'UPDATE configuracion_fiscal
                 SET tasa_bcv_usd = :tasa,
                     monto_cestaticket_mensual = :cestaticket'
            );
            $stmt->execute([
                ':tasa'        => $tasaBcv,
                ':cestaticket' => $nuevoCestaticketBs,
            ]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE configuracion_fiscal
                 SET tasa_bcv_usd = :tasa'
            );
            $stmt->execute([
                ':tasa' => $tasaBcv,
            ]);
        }

        return [
            'tasa_bcv_usd'              => $tasaBcv,
            'monto_cestaticket_mensual' => $nuevoCestaticketBs,
            'cestaticket_actualizado'   => $recalcularCestaticket,
        ];
    }

    /**
     * Obtiene todas las métricas ejecutivas de la empresa activa y del escritorio contable para el Dashboard
     */
    public function obtenerMetricasDashboard(int $empresaId, string $periodo): array
    {
        // 1. Estadísticas del Plan de Cuentas de la empresa activa
        $planModel = new PlanCuentasModel();
        $cuentasStats = $planModel->contarEstadisticasPorEmpresa($empresaId);
        $rubrosPlan   = $planModel->resumenPorRubro($empresaId);

        // 2. Resumen de Asientos Contables de la empresa activa
        $stmtAsientos = $this->db->prepare(
            'SELECT
                COUNT(*) AS total_asientos,
                COALESCE(SUM(total_debe), 0.00) AS suma_debe,
                COALESCE(SUM(total_haber), 0.00) AS suma_haber
             FROM asientos
             WHERE empresa_id = :empresa_id AND estado = "PUBLICADO"'
        );
        $stmtAsientos->execute([':empresa_id' => $empresaId]);
        $asientosRow = $stmtAsientos->fetch() ?: [];

        // 3. Resumen Tributario SENIAT (Libro de Ventas y Libro de Compras del período activo)
        $stmtVentas = $this->db->prepare(
            'SELECT
                COUNT(*) AS cantidad_facturas,
                COALESCE(SUM(base_imponible_general), 0.00) AS base_imponible,
                COALESCE(SUM(monto_iva_general), 0.00) AS debito_fiscal,
                COALESCE(SUM(total_venta_con_iva), 0.00) AS total_ventas
             FROM libro_ventas
             WHERE empresa_id = :empresa_id AND periodo_fiscal = :periodo AND estado = "ACTIVO"'
        );
        $stmtVentas->execute([':empresa_id' => $empresaId, ':periodo' => $periodo]);
        $ventasRow = $stmtVentas->fetch() ?: [];

        $stmtCompras = $this->db->prepare(
            'SELECT
                COUNT(*) AS cantidad_facturas,
                COALESCE(SUM(base_imponible_general + base_imponible_reducida), 0.00) AS base_imponible,
                COALESCE(SUM(monto_iva_general + monto_iva_reducida), 0.00) AS credito_fiscal,
                COALESCE(SUM(total_compra_con_iva), 0.00) AS total_compras
             FROM libro_compras
             WHERE empresa_id = :empresa_id AND periodo_fiscal = :periodo AND estado = "ACTIVO"'
        );
        $stmtCompras->execute([':empresa_id' => $empresaId, ':periodo' => $periodo]);
        $comprasRow = $stmtCompras->fetch() ?: [];

        $stmtRetIva = $this->db->prepare(
            'SELECT
                COUNT(*) AS comprobantes_emitidos,
                COALESCE(SUM(monto_iva_retenido), 0.00) AS total_retenido
             FROM retenciones_iva
             WHERE empresa_id = :empresa_id AND periodo_fiscal = :periodo AND estado = "EMITIDO"'
        );
        $stmtRetIva->execute([':empresa_id' => $empresaId, ':periodo' => $periodo]);
        $retIvaRow = $stmtRetIva->fetch() ?: [];

        $stmtRetIslr = $this->db->prepare(
            'SELECT
                COUNT(*) AS comprobantes_emitidos,
                COALESCE(SUM(monto_retenido), 0.00) AS total_retenido
             FROM retenciones_islr
             WHERE empresa_id = :empresa_id AND periodo_fiscal = :periodo AND estado = "EMITIDO"'
        );
        $stmtRetIslr->execute([':empresa_id' => $empresaId, ':periodo' => $periodo]);
        $retIslrRow = $stmtRetIslr->fetch() ?: [];

        // 4. Resumen de Nómina LOTTT de la empresa activa
        try {
            new NominaModel();
        } catch (Throwable) {
            // Ignorar si no aplica
        }

        $stmtNomina = $this->db->prepare(
            'SELECT
                COUNT(*) AS trabajadores_activos,
                COALESCE(SUM(salario_mensual), 0.00) AS masa_salarial_mensual
             FROM trabajadores
             WHERE empresa_id = :empresa_id AND activo = 1'
        );
        $stmtNomina->execute([':empresa_id' => $empresaId]);
        $nominaRow = $stmtNomina->fetch() ?: [];

        $stmtUltNomina = $this->db->prepare(
            'SELECT codigo_nomina, total_neto_pagar, periodo
             FROM nominas
             WHERE empresa_id = :empresa_id AND estado <> "ANULADA"
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmtUltNomina->execute([':empresa_id' => $empresaId]);
        $ultNominaRow = $stmtUltNomina->fetch() ?: [];

        // 5. Últimos asientos registrados en la empresa activa
        $stmtUltimosAsientos = $this->db->prepare(
            'SELECT id, numero_asiento, fecha, periodo, concepto, referencia, origen, total_debe, total_haber, estado
             FROM asientos
             WHERE empresa_id = :empresa_id
             ORDER BY fecha DESC, id DESC
             LIMIT 5'
        );
        $stmtUltimosAsientos->execute([':empresa_id' => $empresaId]);
        $ultimosAsientos = $stmtUltimosAsientos->fetchAll();

        // 6. Resumen global del Escritorio Contable (Multi-Tenant)
        $globalRow = $this->db->query(
            'SELECT
                COUNT(*) AS total_empresas,
                SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS empresas_activas,
                SUM(CASE WHEN tipo_contribuyente = "Especial" AND activo = 1 THEN 1 ELSE 0 END) AS contribuyentes_especiales,
                SUM(CASE WHEN tipo_contribuyente = "Ordinario" AND activo = 1 THEN 1 ELSE 0 END) AS contribuyentes_ordinarios
             FROM empresas'
        )->fetch() ?: [];

        $debitoFiscal  = (float)($ventasRow['debito_fiscal'] ?? 0.0);
        $creditoFiscal = (float)($comprasRow['credito_fiscal'] ?? 0.0);
        $cuotaTributariaIva = $debitoFiscal - $creditoFiscal;

        return [
            'cuentas'            => $cuentasStats,
            'rubros_plan'        => $rubrosPlan,
            'asientos'           => [
                'total'      => (int)($asientosRow['total_asientos'] ?? 0),
                'suma_debe'  => (float)($asientosRow['suma_debe'] ?? 0.0),
                'suma_haber' => (float)($asientosRow['suma_haber'] ?? 0.0),
            ],
            'tributario'         => [
                'facturas_venta'     => (int)($ventasRow['cantidad_facturas'] ?? 0),
                'base_ventas'        => (float)($ventasRow['base_imponible'] ?? 0.0),
                'debito_fiscal'      => $debitoFiscal,
                'total_ventas'       => (float)($ventasRow['total_ventas'] ?? 0.0),
                'facturas_compra'    => (int)($comprasRow['cantidad_facturas'] ?? 0),
                'base_compras'       => (float)($comprasRow['base_imponible'] ?? 0.0),
                'credito_fiscal'     => $creditoFiscal,
                'total_compras'      => (float)($comprasRow['total_compras'] ?? 0.0),
                'cuota_iva'           => $cuotaTributariaIva,
                'comprobantes_iva'    => (int)($retIvaRow['comprobantes_emitidos'] ?? 0),
                'total_iva_retenido'  => (float)($retIvaRow['total_retenido'] ?? 0.0),
                'comprobantes_islr'   => (int)($retIslrRow['comprobantes_emitidos'] ?? 0),
                'total_islr_retenido' => (float)($retIslrRow['total_retenido'] ?? 0.0),
            ],
            'nomina'             => [
                'trabajadores_activos'  => (int)($nominaRow['trabajadores_activos'] ?? 0),
                'masa_salarial_mensual' => (float)($nominaRow['masa_salarial_mensual'] ?? 0.0),
                'ultima_nomina_codigo'  => (string)($ultNominaRow['codigo_nomina'] ?? ''),
                'ultima_nomina_neto'    => (float)($ultNominaRow['total_neto_pagar'] ?? 0.0),
            ],
            'ultimos_asientos'   => $ultimosAsientos,
            'escritorio'         => [
                'total_empresas'            => (int)($globalRow['total_empresas'] ?? 0),
                'empresas_activas'          => (int)($globalRow['empresas_activas'] ?? 0),
                'contribuyentes_especiales' => (int)($globalRow['contribuyentes_especiales'] ?? 0),
                'contribuyentes_ordinarios' => (int)($globalRow['contribuyentes_ordinarios'] ?? 0),
            ],
        ];
    }
}
