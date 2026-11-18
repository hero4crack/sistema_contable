<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Modelo del Módulo Tributario SENIAT:
 * - Configuración Tributaria (UT, Factor Sustraendo 83.3334, Alícuotas IVA / IGTF)
 * - Libro de Compras y Libro de Ventas con Resumen Artículo 72 (Forma 30 SENIAT)
 * - Integración Contable Automática con Libro Diario (Partida Doble)
 * - Comprobantes de Retención de IVA (SNAT/2015/0049 - SNAT/2025/000054) correlativo 14 dígitos AAAAMM00000001
 * - Comprobantes de Retención de ISLR (Decreto 1.808) con Catálogo de Conceptos y Sustraendo PNR
 */
final class TributarioModel
{
    private PDO $db;
    private static bool $esquemaVerificado = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->asegurarEsquemaFase3();
    }

    /**
     * Verifica y aplica de manera idempotente las columnas adicionales y el catálogo conceptos_islr de Fase 3
     */
    private function asegurarEsquemaFase3(): void
    {
        if (self::$esquemaVerificado) {
            return;
        }
        self::$esquemaVerificado = true;

        // 1. Crear tabla conceptos_islr si no existe
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `conceptos_islr` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `codigo` VARCHAR(10) NOT NULL,
              `nombre` VARCHAR(180) NOT NULL,
              `tipo_persona` ENUM('PNR', 'PNNR', 'PJD', 'PJND') NOT NULL DEFAULT 'PJD',
              `porcentaje_base` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
              `tarifa_porcentaje` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
              `aplica_sustraendo` TINYINT(1) NOT NULL DEFAULT 0,
              `activo` TINYINT(1) NOT NULL DEFAULT 1,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_conceptos_islr_codigo_persona` (`codigo`, `tipo_persona`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $countConceptos = (int)$this->db->query('SELECT COUNT(*) FROM `conceptos_islr`')->fetchColumn();
        if ($countConceptos === 0) {
            $this->db->exec("
                INSERT IGNORE INTO `conceptos_islr` (`codigo`, `nombre`, `tipo_persona`, `porcentaje_base`, `tarifa_porcentaje`, `aplica_sustraendo`, `activo`) VALUES
                ('004-PJ', 'Honorarios Profesionales No Mercantiles (Persona Jurídica Domiciliada)', 'PJD', 100.00, 5.00, 0, 1),
                ('004-PN', 'Honorarios Profesionales No Mercantiles (Persona Natural Residente)', 'PNR', 100.00, 3.00, 1, 1),
                ('053-PJ', 'Servicios Contratados y Ejecución de Obras (Persona Jurídica Domiciliada)', 'PJD', 100.00, 2.00, 0, 1),
                ('053-PN', 'Servicios Contratados y Ejecución de Obras (Persona Natural Residente)', 'PNR', 100.00, 1.00, 1, 1),
                ('012-PJ', 'Canon de Arrendamiento de Bienes Inmuebles (Persona Jurídica Domiciliada)', 'PJD', 100.00, 5.00, 0, 1),
                ('012-PN', 'Canon de Arrendamiento de Bienes Inmuebles (Persona Natural Residente)', 'PNR', 100.00, 3.00, 1, 1),
                ('016-PJ', 'Fletes y Servicios de Transporte Nacional (Persona Jurídica Domiciliada)', 'PJD', 100.00, 3.00, 0, 1),
                ('016-PN', 'Fletes y Servicios de Transporte Nacional (Persona Natural Residente)', 'PNR', 100.00, 1.00, 1, 1),
                ('020-PJ', 'Servicios de Publicidad y Propaganda (Persona Jurídica Domiciliada)', 'PJD', 100.00, 5.00, 0, 1),
                ('020-PN', 'Servicios de Publicidad y Propaganda (Persona Natural Residente)', 'PNR', 100.00, 3.00, 1, 1),
                ('009-PJ', 'Comisiones Mercantiles y de Intermediación (Persona Jurídica Domiciliada)', 'PJD', 100.00, 5.00, 0, 1),
                ('009-PN', 'Comisiones Mercantiles y de Intermediación (Persona Natural Residente)', 'PNR', 100.00, 3.00, 1, 1)
            ");
        }

        // 2. Asegurar columnas extendidas en configuracion_fiscal, libro_compras, libro_ventas, retenciones_iva y retenciones_islr
        $this->agregarColumnaSiNoExiste('configuracion_fiscal', 'excedente_credito_fiscal_anterior', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00');

        $this->agregarColumnaSiNoExiste('libro_compras', 'direccion_proveedor', 'VARCHAR(255) NULL DEFAULT NULL AFTER `nombre_proveedor`');
        $this->agregarColumnaSiNoExiste('libro_compras', 'tipo_transaccion', "VARCHAR(15) NOT NULL DEFAULT '01-Reg' AFTER `tipo_documento`");
        $this->agregarColumnaSiNoExiste('libro_compras', 'numero_nota_credito', 'VARCHAR(40) NULL DEFAULT NULL AFTER `numero_control`');
        $this->agregarColumnaSiNoExiste('libro_compras', 'numero_nota_debito', 'VARCHAR(40) NULL DEFAULT NULL AFTER `numero_nota_credito`');
        $this->agregarColumnaSiNoExiste('libro_compras', 'base_imponible_adicional', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `monto_iva_reducida`');
        $this->agregarColumnaSiNoExiste('libro_compras', 'alicuota_adicional', 'DECIMAL(5,2) NOT NULL DEFAULT 31.00 AFTER `base_imponible_adicional`');
        $this->agregarColumnaSiNoExiste('libro_compras', 'monto_iva_adicional', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `alicuota_adicional`');
        $this->agregarColumnaSiNoExiste('libro_compras', 'base_igtf', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `monto_iva_adicional`');
        $this->agregarColumnaSiNoExiste('libro_compras', 'alicuota_igtf', 'DECIMAL(5,2) NOT NULL DEFAULT 3.00 AFTER `base_igtf`');
        $this->agregarColumnaSiNoExiste('libro_compras', 'monto_igtf', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `alicuota_igtf`');
        $this->agregarColumnaSiNoExiste('libro_compras', 'porcentaje_retencion_iva', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `total_compra_con_iva`');
        $this->agregarColumnaSiNoExiste('libro_compras', 'monto_iva_retenido', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `porcentaje_retencion_iva`');

        $this->agregarColumnaSiNoExiste('libro_ventas', 'tipo_transaccion', "VARCHAR(15) NOT NULL DEFAULT '01-Reg' AFTER `tipo_documento`");
        $this->agregarColumnaSiNoExiste('libro_ventas', 'numero_nota_credito', 'VARCHAR(40) NULL DEFAULT NULL AFTER `numero_control`');
        $this->agregarColumnaSiNoExiste('libro_ventas', 'numero_nota_debito', 'VARCHAR(40) NULL DEFAULT NULL AFTER `numero_nota_credito`');
        $this->agregarColumnaSiNoExiste('libro_ventas', 'base_imponible_reducida', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `monto_iva_general`');
        $this->agregarColumnaSiNoExiste('libro_ventas', 'alicuota_reducida', 'DECIMAL(5,2) NOT NULL DEFAULT 8.00 AFTER `base_imponible_reducida`');
        $this->agregarColumnaSiNoExiste('libro_ventas', 'monto_iva_reducida', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `alicuota_reducida`');
        $this->agregarColumnaSiNoExiste('libro_ventas', 'base_imponible_adicional', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `monto_iva_reducida`');
        $this->agregarColumnaSiNoExiste('libro_ventas', 'alicuota_adicional', 'DECIMAL(5,2) NOT NULL DEFAULT 31.00 AFTER `base_imponible_adicional`');
        $this->agregarColumnaSiNoExiste('libro_ventas', 'monto_iva_adicional', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `alicuota_adicional`');
        $this->agregarColumnaSiNoExiste('libro_ventas', 'base_igtf', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `monto_iva_adicional`');
        $this->agregarColumnaSiNoExiste('libro_ventas', 'alicuota_igtf', 'DECIMAL(5,2) NOT NULL DEFAULT 3.00 AFTER `base_igtf`');

        $this->agregarColumnaSiNoExiste('retenciones_iva', 'direccion_proveedor', 'VARCHAR(255) NULL DEFAULT NULL AFTER `nombre_proveedor`');
        $this->agregarColumnaSiNoExiste('retenciones_iva', 'total_facturas', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `porcentaje_retencion`');
        $this->agregarColumnaSiNoExiste('retenciones_iva', 'total_exento', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `total_facturas`');
        $this->agregarColumnaSiNoExiste('retenciones_iva', 'total_base_imponible', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `total_exento`');

        $this->agregarColumnaSiNoExiste('retenciones_islr', 'direccion_beneficiario', 'VARCHAR(255) NULL DEFAULT NULL AFTER `nombre_beneficiario`');
        $this->agregarColumnaSiNoExiste('retenciones_islr', 'numero_factura', 'VARCHAR(40) NULL DEFAULT NULL AFTER `descripcion_concepto`');
        $this->agregarColumnaSiNoExiste('retenciones_islr', 'numero_control', 'VARCHAR(40) NULL DEFAULT NULL AFTER `numero_factura`');
        $this->agregarColumnaSiNoExiste('retenciones_islr', 'monto_total_factura', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `numero_control`');
        $this->agregarColumnaSiNoExiste('retenciones_islr', 'monto_exento', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `monto_total_factura`');

        // Sincronizar datos de muestra de la Fase 1 si existen
        $this->db->exec("
            UPDATE `libro_compras`
            SET `porcentaje_retencion_iva` = 75.00, `monto_iva_retenido` = 5040.00
            WHERE `id` = 1 AND `monto_iva_retenido` = 0
        ");
        $this->db->exec("
            UPDATE `retenciones_iva`
            SET `total_facturas` = 48720.00, `total_base_imponible` = 42000.00,
                `direccion_proveedor` = 'Av. Urdaneta, Edif. Centro Financiero, Local PB, Caracas'
            WHERE `id` = 1 AND `total_base_imponible` = 0
        ");

        $countIslr = (int)$this->db->query('SELECT COUNT(*) FROM `retenciones_islr`')->fetchColumn();
        if ($countIslr === 0) {
            $this->db->exec("
                INSERT INTO `retenciones_islr` (
                    `empresa_id`, `numero_comprobante`, `fecha_emision`, `periodo_fiscal`,
                    `rif_beneficiario`, `nombre_beneficiario`, `direccion_beneficiario`,
                    `tipo_persona`, `codigo_concepto`, `descripcion_concepto`,
                    `numero_factura`, `numero_control`, `monto_total_factura`, `monto_exento`,
                    `base_imponible`, `porcentaje_islr`, `sustraendo`, `monto_retenido`, `estado`
                ) VALUES (
                    1, 'ISLR-202611-00001', '2026-11-05', '2026-11',
                    'J-40206401-2', 'ESCRITORIO JURÍDICO JURIS CONTABLE, C.A.',
                    'Av. La Paz, CC Inversiones Pareca, Piso 1, Ofic. 1-01, Puerto Cabello, Edo. Carabobo',
                    'PJD', '004-PJ', 'Honorarios Profesionales No Mercantiles (Persona Jurídica Domiciliada)',
                    '005548', '00-005548', 58000.00, 0.00,
                    50000.00, 5.00, 0.00, 2500.00, 'EMITIDO'
                )
            ");
        }
    }

    /**
     * Agrega una columna a una tabla si aún no existe (100% compatible con MariaDB y ATTR_EMULATE_PREPARES = false)
     */
    private function agregarColumnaSiNoExiste(string $tabla, string $columna, string $definicion): void
    {
        $sql = 'SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :tabla
                  AND COLUMN_NAME = :columna';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':tabla'   => $tabla,
            ':columna' => $columna,
        ]);

        if ((int)$stmt->fetchColumn() === 0) {
            $this->db->exec("ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicion");
        }
    }

    /**
     * Obtiene los parámetros fiscales SENIAT configurados para la empresa activa
     */
    public function obtenerConfiguracionFiscal(int $empresaId): array
    {
        $sql = 'SELECT
                    cf.*,
                    e.rif,
                    e.razon_social,
                    e.direccion_fiscal,
                    e.tipo_contribuyente,
                    e.porcentaje_retencion_iva,
                    e.periodo_fiscal_activo
                FROM empresas e
                LEFT JOIN configuracion_fiscal cf ON cf.empresa_id = e.id
                WHERE e.id = :empresa_id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('No se encontró la configuración fiscal de la empresa activa.');
        }

        return [
            'empresa_id'                        => (int)$row['id'],
            'rif'                               => (string)$row['rif'],
            'razon_social'                      => (string)$row['razon_social'],
            'direccion_fiscal'                  => (string)$row['direccion_fiscal'],
            'tipo_contribuyente'                => (string)$row['tipo_contribuyente'],
            'porcentaje_retencion_iva'          => (float)($row['porcentaje_retencion_iva'] ?? 75),
            'periodo_fiscal_activo'             => (string)$row['periodo_fiscal_activo'],
            'alicuota_iva_general'              => (float)($row['alicuota_iva_general'] ?? 16.00),
            'alicuota_iva_reducida'             => (float)($row['alicuota_iva_reducida'] ?? 8.00),
            'alicuota_iva_adicional'            => (float)($row['alicuota_iva_adicional'] ?? 31.00),
            'alicuota_igtf'                     => (float)($row['alicuota_igtf'] ?? 3.00),
            'valor_unidad_tributaria'           => (float)($row['valor_unidad_tributaria'] ?? 9.00),
            'factor_sustraendo_islr'            => (float)($row['factor_sustraendo_islr'] ?? 83.3334),
            'excedente_credito_fiscal_anterior' => (float)($row['excedente_credito_fiscal_anterior'] ?? 0.00),
        ];
    }

    /**
     * Actualiza la configuración tributaria (UT, Factor Sustraendo, Alícuotas, Excedente IVA y % Retención)
     */
    public function actualizarConfiguracionFiscal(int $empresaId, array $datos): void
    {
        Database::beginTransaction();

        try {
            $porcRetIva = in_array((string)($datos['porcentaje_retencion_iva'] ?? '75'), ['0', '75', '100'], true)
                ? (string)$datos['porcentaje_retencion_iva']
                : '75';

            $stmtEmp = $this->db->prepare(
                'UPDATE empresas
                 SET porcentaje_retencion_iva = :porc_ret
                 WHERE id = :empresa_id'
            );
            $stmtEmp->execute([
                ':porc_ret'   => $porcRetIva,
                ':empresa_id' => $empresaId,
            ]);

            $sqlCfg = 'INSERT INTO configuracion_fiscal (
                            empresa_id, alicuota_iva_general, alicuota_iva_reducida, alicuota_iva_adicional,
                            alicuota_igtf, valor_unidad_tributaria, factor_sustraendo_islr, excedente_credito_fiscal_anterior
                       ) VALUES (
                            :empresa_id, :iva_gen, :iva_red, :iva_adi, :igtf, :ut, :factor, :excedente
                       ) ON DUPLICATE KEY UPDATE
                            alicuota_iva_general = VALUES(alicuota_iva_general),
                            alicuota_iva_reducida = VALUES(alicuota_iva_reducida),
                            alicuota_iva_adicional = VALUES(alicuota_iva_adicional),
                            alicuota_igtf = VALUES(alicuota_igtf),
                            valor_unidad_tributaria = VALUES(valor_unidad_tributaria),
                            factor_sustraendo_islr = VALUES(factor_sustraendo_islr),
                            excedente_credito_fiscal_anterior = VALUES(excedente_credito_fiscal_anterior)';

            $stmtCfg = $this->db->prepare($sqlCfg);
            $stmtCfg->execute([
                ':empresa_id' => $empresaId,
                ':iva_gen'    => round((float)($datos['alicuota_iva_general'] ?? 16.00), 2),
                ':iva_red'    => round((float)($datos['alicuota_iva_reducida'] ?? 8.00), 2),
                ':iva_adi'    => round((float)($datos['alicuota_iva_adicional'] ?? 31.00), 2),
                ':igtf'       => round((float)($datos['alicuota_igtf'] ?? 3.00), 2),
                ':ut'         => round((float)($datos['valor_unidad_tributaria'] ?? 9.00), 2),
                ':factor'     => round((float)($datos['factor_sustraendo_islr'] ?? 83.3334), 4),
                ':excedente'  => round((float)($datos['excedente_credito_fiscal_anterior'] ?? 0.00), 2),
            ]);

            Database::commit();
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    // =========================================================================
    // SECCIÓN 1: LIBRO DE COMPRAS Y LIBRO DE VENTAS
    // =========================================================================

    /**
     * Lista todos los documentos del Libro de Compras para la empresa y período fiscal
     */
    public function listarLibroCompras(int $empresaId, string $periodo): array
    {
        $sql = 'SELECT
                    lc.*,
                    ri.numero_comprobante AS comprobante_iva_numero,
                    ri.fecha_emision AS comprobante_iva_fecha,
                    a.numero_asiento AS asiento_numero
                FROM libro_compras lc
                LEFT JOIN retenciones_iva ri ON ri.id = lc.retencion_iva_id
                LEFT JOIN asientos a ON a.id = lc.asiento_id
                WHERE lc.empresa_id = :empresa_id AND lc.periodo_fiscal = :periodo
                ORDER BY lc.fecha_factura ASC, lc.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':periodo'    => $periodo,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Lista las facturas de compra activas que aún NO tienen comprobante de retención de IVA asociado
     */
    public function listarComprasSinRetencionIva(int $empresaId, ?string $periodo = null): array
    {
        $sql = 'SELECT
                    lc.id,
                    lc.periodo_fiscal,
                    lc.fecha_factura,
                    lc.rif_proveedor,
                    lc.nombre_proveedor,
                    lc.direccion_proveedor,
                    lc.tipo_documento,
                    lc.tipo_transaccion,
                    lc.numero_factura,
                    lc.numero_control,
                    lc.compras_exentas,
                    (lc.base_imponible_general + lc.base_imponible_reducida + lc.base_imponible_adicional) AS base_imponible_total,
                    (lc.monto_iva_general + lc.monto_iva_reducida + lc.monto_iva_adicional) AS iva_causado_total,
                    lc.alicuota_general,
                    lc.total_compra_con_iva
                FROM libro_compras lc
                WHERE lc.empresa_id = :empresa_id
                  AND lc.estado = "ACTIVO"
                  AND lc.retencion_iva_id IS NULL
                  AND (lc.monto_iva_general + lc.monto_iva_reducida + lc.monto_iva_adicional) > 0';

        $params = [':empresa_id' => $empresaId];
        if ($periodo !== null && $periodo !== '') {
            $sql .= ' AND lc.periodo_fiscal = :periodo';
            $params[':periodo'] = $periodo;
        }

        $sql .= ' ORDER BY lc.fecha_factura DESC, lc.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Lista todos los documentos del Libro de Ventas para la empresa y período fiscal
     */
    public function listarLibroVentas(int $empresaId, string $periodo): array
    {
        $sql = 'SELECT
                    lv.*,
                    a.numero_asiento AS asiento_numero
                FROM libro_ventas lv
                LEFT JOIN asientos a ON a.id = lv.asiento_id
                WHERE lv.empresa_id = :empresa_id AND lv.periodo_fiscal = :periodo
                ORDER BY lv.fecha_factura ASC, lv.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':periodo'    => $periodo,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Registra una factura/nota en el Libro de Compras con opción de emitir Comprobante de Retención IVA
     * y opción de generar Asiento Contable automático en el Libro Diario.
     */
    public function registrarFacturaCompra(int $empresaId, int $usuarioId, array $datos): array
    {
        $fechaFactura   = trim((string)($datos['fecha_factura'] ?? date('Y-m-d')));
        $periodoFiscal  = trim((string)($datos['periodo_fiscal'] ?? substr($fechaFactura, 0, 7)));
        $rifProveedor   = strtoupper(trim((string)($datos['rif_proveedor'] ?? '')));
        $nombreProv     = trim((string)($datos['nombre_proveedor'] ?? ''));
        $direccionProv  = trim((string)($datos['direccion_proveedor'] ?? ''));
        $tipoDoc        = (string)($datos['tipo_documento'] ?? 'FACTURA');
        $tipoTrans      = (string)($datos['tipo_transaccion'] ?? '01-Reg');
        $numFactura     = trim((string)($datos['numero_factura'] ?? ''));
        $numControl     = trim((string)($datos['numero_control'] ?? ''));
        $numNotaCredito = trim((string)($datos['numero_nota_credito'] ?? ''));
        $numNotaDebito  = trim((string)($datos['numero_nota_debito'] ?? ''));
        $numAfectada    = trim((string)($datos['numero_factura_afectada'] ?? ''));

        if (!preg_match('/^[VEJGP]-\d{8}-\d$/', $rifProveedor)) {
            throw new RuntimeException('El RIF del proveedor debe tener el formato SENIAT (Ej: J-12345678-9).');
        }

        if ($nombreProv === '' || $numFactura === '' || $numControl === '') {
            throw new RuntimeException('Razón Social del Proveedor, N° de Factura y N° de Control son obligatorios.');
        }

        // Factor multiplicador (-1 si es Nota de Crédito para restar en totales del libro)
        $signo = ($tipoDoc === 'NOTA_CREDITO') ? -1.0 : 1.0;

        $exentas       = round(abs((float)($datos['compras_exentas'] ?? 0)) * $signo, 2);
        $baseGen       = round(abs((float)($datos['base_imponible_general'] ?? 0)) * $signo, 2);
        $alicGen       = round((float)($datos['alicuota_general'] ?? 16.00), 2);
        $ivaGen        = round($baseGen * ($alicGen / 100), 2);

        $baseRed       = round(abs((float)($datos['base_imponible_reducida'] ?? 0)) * $signo, 2);
        $alicRed       = round((float)($datos['alicuota_reducida'] ?? 8.00), 2);
        $ivaRed        = round($baseRed * ($alicRed / 100), 2);

        $baseAdi       = round(abs((float)($datos['base_imponible_adicional'] ?? 0)) * $signo, 2);
        $alicAdi       = round((float)($datos['alicuota_adicional'] ?? 31.00), 2);
        $ivaAdi        = round($baseAdi * ($alicAdi / 100), 2);

        $baseIgtf      = round(abs((float)($datos['base_igtf'] ?? 0)) * $signo, 2);
        $alicIgtf      = round((float)($datos['alicuota_igtf'] ?? 3.00), 2);
        $montoIgtf     = round($baseIgtf * ($alicIgtf / 100), 2);

        $ivaTotalCausado = round($ivaGen + $ivaRed + $ivaAdi, 2);
        $baseTotal       = round($baseGen + $baseRed + $baseAdi, 2);
        $totalCompra     = round($exentas + $baseTotal + $ivaTotalCausado + $montoIgtf, 2);

        if (abs($totalCompra) <= 0.001) {
            throw new RuntimeException('El monto total de la factura de compra debe ser mayor a Bs. 0,00.');
        }

        $porcRetIva = round((float)($datos['porcentaje_retencion_iva'] ?? 0), 2);
        $montoRetIva = $porcRetIva > 0 ? round(abs($ivaTotalCausado) * ($porcRetIva / 100) * $signo, 2) : 0.00;
        $emitirRetencion = !empty($datos['emitir_comprobante_ret_iva']) && abs($montoRetIva) > 0;
        $generarAsiento  = !empty($datos['generar_asiento_automatico']);

        Database::beginTransaction();

        try {
            $retencionIvaId = null;
            $numeroComprobanteIva = null;

            // 1. Si solicitó emitir el Comprobante de Retención de IVA simultáneamente
            if ($emitirRetencion && $ivaTotalCausado > 0) {
                $numeroComprobanteIva = $this->generarCorrelativoIva($empresaId, $periodoFiscal);

                $stmtRet = $this->db->prepare(
                    'INSERT INTO retenciones_iva (
                        empresa_id, numero_comprobante, fecha_emision, periodo_fiscal,
                        rif_proveedor, nombre_proveedor, direccion_proveedor,
                        porcentaje_retencion, total_facturas, total_exento, total_base_imponible,
                        monto_iva_causado, monto_iva_retenido, estado
                    ) VALUES (
                        :empresa_id, :numero_comprobante, :fecha_emision, :periodo_fiscal,
                        :rif_proveedor, :nombre_proveedor, :direccion_proveedor,
                        :porcentaje_retencion, :total_facturas, :total_exento, :total_base_imponible,
                        :monto_iva_causado, :monto_iva_retenido, "EMITIDO"
                    )'
                );
                $stmtRet->execute([
                    ':empresa_id'           => $empresaId,
                    ':numero_comprobante'   => $numeroComprobanteIva,
                    ':fecha_emision'        => $fechaFactura,
                    ':periodo_fiscal'       => $periodoFiscal,
                    ':rif_proveedor'        => $rifProveedor,
                    ':nombre_proveedor'     => $nombreProv,
                    ':direccion_proveedor'  => $direccionProv ?: null,
                    ':porcentaje_retencion' => $porcRetIva,
                    ':total_facturas'       => abs($totalCompra),
                    ':total_exento'         => abs($exentas),
                    ':total_base_imponible' => abs($baseTotal),
                    ':monto_iva_causado'    => abs($ivaTotalCausado),
                    ':monto_iva_retenido'   => abs($montoRetIva),
                ]);

                $retencionIvaId = (int)$this->db->lastInsertId();
            }

            // 2. Si solicitó generar Asiento Contable Automático en el Libro Diario
            $asientoId = null;
            $numeroAsiento = null;

            if ($generarAsiento && $totalCompra > 0) {
                $asientoInfo = $this->crearAsientoAutomaticoCompra(
                    $empresaId,
                    $usuarioId,
                    $fechaFactura,
                    $numFactura,
                    $nombreProv,
                    $rifProveedor,
                    abs($exentas) + abs($baseTotal),
                    abs($ivaTotalCausado),
                    abs($montoIgtf),
                    $emitirRetencion ? abs($montoRetIva) : 0.00
                );
                $asientoId     = $asientoInfo['id'];
                $numeroAsiento = $asientoInfo['numero_asiento'];
            }

            // 3. Insertar registro en libro_compras
            $sql = 'INSERT INTO libro_compras (
                        empresa_id, periodo_fiscal, fecha_factura, rif_proveedor, nombre_proveedor, direccion_proveedor,
                        tipo_documento, tipo_transaccion, numero_factura, numero_control,
                        numero_nota_credito, numero_nota_debito, numero_factura_afectada,
                        compras_exentas, base_imponible_general, alicuota_general, monto_iva_general,
                        base_imponible_reducida, alicuota_reducida, monto_iva_reducida,
                        base_imponible_adicional, alicuota_adicional, monto_iva_adicional,
                        base_igtf, alicuota_igtf, monto_igtf,
                        total_compra_con_iva, porcentaje_retencion_iva, monto_iva_retenido,
                        retencion_iva_id, asiento_id, estado
                    ) VALUES (
                        :empresa_id, :periodo_fiscal, :fecha_factura, :rif_proveedor, :nombre_proveedor, :direccion_proveedor,
                        :tipo_documento, :tipo_transaccion, :numero_factura, :numero_control,
                        :numero_nota_credito, :numero_nota_debito, :numero_factura_afectada,
                        :compras_exentas, :base_gen, :alic_gen, :iva_gen,
                        :base_red, :alic_red, :iva_red,
                        :base_adi, :alic_adi, :iva_adi,
                        :base_igtf, :alic_igtf, :monto_igtf,
                        :total_compra, :porc_ret_iva, :monto_ret_iva,
                        :retencion_iva_id, :asiento_id, "ACTIVO"
                    )';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':empresa_id'              => $empresaId,
                ':periodo_fiscal'          => $periodoFiscal,
                ':fecha_factura'           => $fechaFactura,
                ':rif_proveedor'           => $rifProveedor,
                ':nombre_proveedor'        => $nombreProv,
                ':direccion_proveedor'     => $direccionProv ?: null,
                ':tipo_documento'          => $tipoDoc,
                ':tipo_transaccion'        => $tipoTrans,
                ':numero_factura'          => $numFactura,
                ':numero_control'          => $numControl,
                ':numero_nota_credito'     => $numNotaCredito ?: null,
                ':numero_nota_debito'      => $numNotaDebito ?: null,
                ':numero_factura_afectada' => $numAfectada ?: null,
                ':compras_exentas'         => $exentas,
                ':base_gen'                => $baseGen,
                ':alic_gen'                => $alicGen,
                ':iva_gen'                 => $ivaGen,
                ':base_red'                => $baseRed,
                ':alic_red'                => $alicRed,
                ':iva_red'                 => $ivaRed,
                ':base_adi'                => $baseAdi,
                ':alic_adi'                => $alicAdi,
                ':iva_adi'                 => $ivaAdi,
                ':base_igtf'               => $baseIgtf,
                ':alic_igtf'               => $alicIgtf,
                ':monto_igtf'              => $montoIgtf,
                ':total_compra'            => $totalCompra,
                ':porc_ret_iva'            => $porcRetIva,
                ':monto_ret_iva'           => $emitirRetencion ? $montoRetIva : 0.00,
                ':retencion_iva_id'        => $retencionIvaId,
                ':asiento_id'              => $asientoId,
            ]);

            $compraId = (int)$this->db->lastInsertId();

            Database::commit();

            return [
                'compra_id'           => $compraId,
                'total_compra'        => $totalCompra,
                'comprobante_iva_num' => $numeroComprobanteIva,
                'asiento_numero'      => $numeroAsiento,
            ];
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Registra una factura/nota en el Libro de Ventas con opción de generar Asiento Contable automático
     */
    public function registrarFacturaVenta(int $empresaId, int $usuarioId, array $datos): array
    {
        $fechaFactura   = trim((string)($datos['fecha_factura'] ?? date('Y-m-d')));
        $periodoFiscal  = trim((string)($datos['periodo_fiscal'] ?? substr($fechaFactura, 0, 7)));
        $rifCliente     = strtoupper(trim((string)($datos['rif_cliente'] ?? '')));
        $nombreCliente  = trim((string)($datos['nombre_cliente'] ?? ''));
        $tipoContribCli = (string)($datos['tipo_contribuyente_cliente'] ?? 'Ordinario');
        $tipoDoc        = (string)($datos['tipo_documento'] ?? 'FACTURA');
        $tipoTrans      = (string)($datos['tipo_transaccion'] ?? '01-Reg');
        $numFactura     = trim((string)($datos['numero_factura'] ?? ''));
        $numControl     = trim((string)($datos['numero_control'] ?? ''));
        $numNotaCredito = trim((string)($datos['numero_nota_credito'] ?? ''));
        $numNotaDebito  = trim((string)($datos['numero_nota_debito'] ?? ''));
        $numAfectada    = trim((string)($datos['numero_factura_afectada'] ?? ''));

        if (!preg_match('/^[VEJGP]-\d{8}-\d$/', $rifCliente)) {
            throw new RuntimeException('El RIF del cliente debe tener el formato SENIAT (Ej: J-12345678-9).');
        }

        if ($nombreCliente === '' || $numFactura === '' || $numControl === '') {
            throw new RuntimeException('Razón Social del Cliente, N° de Factura y N° de Control son obligatorios.');
        }

        $signo = ($tipoDoc === 'NOTA_CREDITO') ? -1.0 : 1.0;

        $exentas       = round(abs((float)($datos['ventas_exentas'] ?? 0)) * $signo, 2);
        $baseGen       = round(abs((float)($datos['base_imponible_general'] ?? 0)) * $signo, 2);
        $alicGen       = round((float)($datos['alicuota_general'] ?? 16.00), 2);
        $ivaGen        = round($baseGen * ($alicGen / 100), 2);

        $baseRed       = round(abs((float)($datos['base_imponible_reducida'] ?? 0)) * $signo, 2);
        $alicRed       = round((float)($datos['alicuota_reducida'] ?? 8.00), 2);
        $ivaRed        = round($baseRed * ($alicRed / 100), 2);

        $baseAdi       = round(abs((float)($datos['base_imponible_adicional'] ?? 0)) * $signo, 2);
        $alicAdi       = round((float)($datos['alicuota_adicional'] ?? 31.00), 2);
        $ivaAdi        = round($baseAdi * ($alicAdi / 100), 2);

        $baseIgtf      = round(abs((float)($datos['base_igtf'] ?? 0)) * $signo, 2);
        $alicIgtf      = round((float)($datos['alicuota_igtf'] ?? 3.00), 2);
        $montoIgtf     = round($baseIgtf * ($alicIgtf / 100), 2);

        $ivaTotalDebito = round($ivaGen + $ivaRed + $ivaAdi, 2);
        $baseTotal      = round($baseGen + $baseRed + $baseAdi, 2);
        $totalVenta     = round($exentas + $baseTotal + $ivaTotalDebito + $montoIgtf, 2);

        if (abs($totalVenta) <= 0.001) {
            throw new RuntimeException('El monto total de la factura de venta debe ser mayor a Bs. 0,00.');
        }

        $comprobanteRetCliente = trim((string)($datos['numero_comprobante_ret_cliente'] ?? ''));
        $ivaRetenidoPorCliente = round(abs((float)($datos['iva_retenido_por_cliente'] ?? 0)) * $signo, 2);
        $generarAsiento        = !empty($datos['generar_asiento_automatico']);

        Database::beginTransaction();

        try {
            $asientoId = null;
            $numeroAsiento = null;

            if ($generarAsiento && $totalVenta > 0) {
                $asientoInfo = $this->crearAsientoAutomaticoVenta(
                    $empresaId,
                    $usuarioId,
                    $fechaFactura,
                    $numFactura,
                    $nombreCliente,
                    $rifCliente,
                    abs($exentas) + abs($baseTotal),
                    abs($ivaTotalDebito),
                    abs($ivaRetenidoPorCliente)
                );
                $asientoId     = $asientoInfo['id'];
                $numeroAsiento = $asientoInfo['numero_asiento'];
            }

            $sql = 'INSERT INTO libro_ventas (
                        empresa_id, periodo_fiscal, fecha_factura, rif_cliente, nombre_cliente,
                        tipo_contribuyente_cliente, tipo_documento, tipo_transaccion,
                        numero_factura, numero_control, numero_nota_credito, numero_nota_debito, numero_factura_afectada,
                        ventas_exentas, base_imponible_general, alicuota_general, monto_iva_general,
                        base_imponible_reducida, alicuota_reducida, monto_iva_reducida,
                        base_imponible_adicional, alicuota_adicional, monto_iva_adicional,
                        base_igtf, alicuota_igtf, monto_igtf, total_venta_con_iva,
                        numero_comprobante_ret_cliente, iva_retenido_por_cliente, asiento_id, estado
                    ) VALUES (
                        :empresa_id, :periodo_fiscal, :fecha_factura, :rif_cliente, :nombre_cliente,
                        :tipo_contrib_cli, :tipo_documento, :tipo_transaccion,
                        :numero_factura, :numero_control, :numero_nota_credito, :numero_nota_debito, :numero_factura_afectada,
                        :ventas_exentas, :base_gen, :alic_gen, :iva_gen,
                        :base_red, :alic_red, :iva_red,
                        :base_adi, :alic_adi, :iva_adi,
                        :base_igtf, :alic_igtf, :monto_igtf, :total_venta,
                        :comp_ret_cli, :iva_ret_cli, :asiento_id, "ACTIVO"
                    )';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':empresa_id'              => $empresaId,
                ':periodo_fiscal'          => $periodoFiscal,
                ':fecha_factura'           => $fechaFactura,
                ':rif_cliente'             => $rifCliente,
                ':nombre_cliente'          => $nombreCliente,
                ':tipo_contrib_cli'        => in_array($tipoContribCli, ['Ordinario', 'Especial', 'No Sujeto'], true) ? $tipoContribCli : 'Ordinario',
                ':tipo_documento'          => $tipoDoc,
                ':tipo_transaccion'        => $tipoTrans,
                ':numero_factura'          => $numFactura,
                ':numero_control'          => $numControl,
                ':numero_nota_credito'     => $numNotaCredito ?: null,
                ':numero_nota_debito'      => $numNotaDebito ?: null,
                ':numero_factura_afectada' => $numAfectada ?: null,
                ':ventas_exentas'          => $exentas,
                ':base_gen'                => $baseGen,
                ':alic_gen'                => $alicGen,
                ':iva_gen'                 => $ivaGen,
                ':base_red'                => $baseRed,
                ':alic_red'                => $alicRed,
                ':iva_red'                 => $ivaRed,
                ':base_adi'                => $baseAdi,
                ':alic_adi'                => $alicAdi,
                ':iva_adi'                 => $ivaAdi,
                ':base_igtf'               => $baseIgtf,
                ':alic_igtf'               => $alicIgtf,
                ':monto_igtf'              => $montoIgtf,
                ':total_venta'             => $totalVenta,
                ':comp_ret_cli'            => $comprobanteRetCliente ?: null,
                ':iva_ret_cli'             => $ivaRetenidoPorCliente,
                ':asiento_id'              => $asientoId,
            ]);

            $ventaId = (int)$this->db->lastInsertId();

            Database::commit();

            return [
                'venta_id'       => $ventaId,
                'total_venta'    => $totalVenta,
                'asiento_numero' => $numeroAsiento,
            ];
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Calcula el Cuadro Resumen Artículo 72 (Casillas Forma 30 SENIAT) para el período consultado
     */
    public function obtenerResumenArticulo72(int $empresaId, string $periodo): array
    {
        $cfg = $this->obtenerConfiguracionFiscal($empresaId);

        // Sumatorias de Ventas del período
        $stmtV = $this->db->prepare(
            'SELECT
                COALESCE(SUM(ventas_exentas), 0.00) AS ventas_exentas,
                COALESCE(SUM(base_imponible_general), 0.00) AS ventas_base_gen,
                COALESCE(SUM(monto_iva_general), 0.00) AS ventas_iva_gen,
                COALESCE(SUM(base_imponible_reducida), 0.00) AS ventas_base_red,
                COALESCE(SUM(monto_iva_reducida), 0.00) AS ventas_iva_red,
                COALESCE(SUM(base_imponible_adicional), 0.00) AS ventas_base_adi,
                COALESCE(SUM(monto_iva_adicional), 0.00) AS ventas_iva_adi,
                COALESCE(SUM(iva_retenido_por_cliente), 0.00) AS retenciones_soportadas_clientes
             FROM libro_ventas
             WHERE empresa_id = :empresa_id AND periodo_fiscal = :periodo AND estado = "ACTIVO"'
        );
        $stmtV->execute([':empresa_id' => $empresaId, ':periodo' => $periodo]);
        $v = $stmtV->fetch() ?: [];

        // Sumatorias de Compras del período
        $stmtC = $this->db->prepare(
            'SELECT
                COALESCE(SUM(compras_exentas), 0.00) AS compras_exentas,
                COALESCE(SUM(base_imponible_general), 0.00) AS compras_base_gen,
                COALESCE(SUM(monto_iva_general), 0.00) AS compras_iva_gen,
                COALESCE(SUM(base_imponible_reducida), 0.00) AS compras_base_red,
                COALESCE(SUM(monto_iva_reducida), 0.00) AS compras_iva_red,
                COALESCE(SUM(base_imponible_adicional), 0.00) AS compras_base_adi,
                COALESCE(SUM(monto_iva_adicional), 0.00) AS compras_iva_adi,
                COALESCE(SUM(monto_iva_retenido), 0.00) AS iva_retenido_a_proveedores
             FROM libro_compras
             WHERE empresa_id = :empresa_id AND periodo_fiscal = :periodo AND estado = "ACTIVO"'
        );
        $stmtC->execute([':empresa_id' => $empresaId, ':periodo' => $periodo]);
        $c = $stmtC->fetch() ?: [];

        $totalVentasBase = round((float)$v['ventas_exentas'] + (float)$v['ventas_base_gen'] + (float)$v['ventas_base_red'] + (float)$v['ventas_base_adi'], 2);
        $totalDebitosFiscales = round((float)$v['ventas_iva_gen'] + (float)$v['ventas_iva_red'] + (float)$v['ventas_iva_adi'], 2);

        $totalComprasBase = round((float)$c['compras_exentas'] + (float)$c['compras_base_gen'] + (float)$c['compras_base_red'] + (float)$c['compras_base_adi'], 2);
        $creditosDelPeriodo = round((float)$c['compras_iva_gen'] + (float)$c['compras_iva_red'] + (float)$c['compras_iva_adi'], 2);
        $excedenteMesAnterior = round((float)$cfg['excedente_credito_fiscal_anterior'], 2);
        $totalCreditosFiscales = round($creditosDelPeriodo + $excedenteMesAnterior, 2);

        $diferencia = round($totalDebitosFiscales - $totalCreditosFiscales, 2);
        $cuotaTributariaPeriodo = $diferencia > 0 ? $diferencia : 0.00;
        $excedenteCreditoMesSiguiente = $diferencia < 0 ? abs($diferencia) : 0.00;

        $retencionesSoportadas = round((float)$v['retenciones_soportadas_clientes'], 2);
        $ivaNetoAPagar = max(0.00, round($cuotaTributariaPeriodo - $retencionesSoportadas, 2));

        return [
            'debitos' => [
                'casilla_40_no_gravadas'  => (float)$v['ventas_exentas'],
                'casilla_42_base_general' => (float)$v['ventas_base_gen'],
                'casilla_42_deb_general'  => (float)$v['ventas_iva_gen'],
                'casilla_442_base_adic'   => (float)$v['ventas_base_adi'],
                'casilla_442_deb_adic'    => (float)$v['ventas_iva_adi'],
                'casilla_443_base_red'    => (float)$v['ventas_base_red'],
                'casilla_443_deb_red'     => (float)$v['ventas_iva_red'],
                'casilla_46_total_base'   => $totalVentasBase,
                'casilla_49_total_debito' => $totalDebitosFiscales,
            ],
            'creditos' => [
                'casilla_30_no_gravadas'   => (float)$c['compras_exentas'],
                'casilla_33_base_general'  => (float)$c['compras_base_gen'],
                'casilla_33_cred_general'  => (float)$c['compras_iva_gen'],
                'casilla_332_base_adic'    => (float)$c['compras_base_adi'],
                'casilla_332_cred_adic'    => (float)$c['compras_iva_adi'],
                'casilla_333_base_red'     => (float)$c['compras_base_red'],
                'casilla_333_cred_red'     => (float)$c['compras_iva_red'],
                'casilla_35_total_base'    => $totalComprasBase,
                'casilla_35_cred_periodo'  => $creditosDelPeriodo,
                'casilla_20_excedente_ant' => $excedenteMesAnterior,
                'casilla_39_total_credito' => $totalCreditosFiscales,
            ],
            'autoliquidacion' => [
                'casilla_53_cuota_periodo'       => $cuotaTributariaPeriodo,
                'casilla_60_excedente_siguiente' => $excedenteCreditoMesSiguiente,
                'retenciones_soportadas_periodo' => $retencionesSoportadas,
                'iva_retenido_a_terceros'        => (float)$c['iva_retenido_a_proveedores'],
                'total_iva_pagar'                => $ivaNetoAPagar,
            ],
        ];
    }

    // =========================================================================
    // SECCIÓN 2: COMPROBANTES DE RETENCIÓN DE IVA (SNAT/2015/0049)
    // =========================================================================

    /**
     * Genera el correlativo oficial SENIAT de 14 dígitos: AAAAMM + 8 dígitos secuenciales (Ej: 20261100000002)
     */
    public function generarCorrelativoIva(int $empresaId, string $periodo): string
    {
        $prefijo = str_replace('-', '', $periodo); // AAAAMM (6 dígitos)

        $sql = 'SELECT numero_comprobante
                FROM retenciones_iva
                WHERE empresa_id = :empresa_id
                ORDER BY id DESC
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);
        $ultimo = $stmt->fetchColumn();

        $secuencia = 1;
        if (is_string($ultimo) && strlen($ultimo) === 14) {
            $ultimosOcho = substr($ultimo, 6, 8);
            if (ctype_digit($ultimosOcho)) {
                $secuencia = (int)$ultimosOcho + 1;
            }
        }

        return $prefijo . str_pad((string)$secuencia, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Lista los comprobantes de retención de IVA emitidos por la empresa activa en el período
     */
    public function listarRetencionesIva(int $empresaId, ?string $periodo = null): array
    {
        $sql = 'SELECT
                    ri.*,
                    (SELECT COUNT(*) FROM libro_compras lc WHERE lc.retencion_iva_id = ri.id) AS cantidad_facturas
                FROM retenciones_iva ri
                WHERE ri.empresa_id = :empresa_id';

        $params = [':empresa_id' => $empresaId];
        if ($periodo !== null && $periodo !== '') {
            $sql .= ' AND ri.periodo_fiscal = :periodo';
            $params[':periodo'] = $periodo;
        }

        $sql .= ' ORDER BY ri.numero_comprobante DESC, ri.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Emite un Comprobante de Retención de IVA agrupando una o varias facturas del mismo proveedor
     * ya registradas en el Libro de Compras.
     */
    public function emitirComprobanteIvaDesdeCompras(
        int $empresaId,
        string $fechaEmision,
        string $periodoFiscal,
        float $porcentajeRetencion,
        string $direccionProveedor,
        array $idsCompras
    ): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idsCompras), static fn(int $v): bool => $v > 0)));
        if (empty($ids)) {
            throw new RuntimeException('Debe seleccionar al menos una factura del Libro de Compras para retener.');
        }

        if (!in_array((int)$porcentajeRetencion, [75, 100], true)) {
            throw new RuntimeException('El porcentaje de retención de IVA debe ser 75% o 100%.');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sqlCompras = "SELECT * FROM libro_compras
                       WHERE empresa_id = ? AND id IN ($placeholders) AND estado = 'ACTIVO'
                       ORDER BY fecha_factura ASC, id ASC";

        $stmt = $this->db->prepare($sqlCompras);
        $stmt->execute(array_merge([$empresaId], $ids));
        $facturas = $stmt->fetchAll();

        if (count($facturas) !== count($ids)) {
            throw new RuntimeException('Una o más facturas seleccionadas no pertenecen a la empresa activa.');
        }

        $rifProv    = (string)$facturas[0]['rif_proveedor'];
        $nombreProv = (string)$facturas[0]['nombre_proveedor'];
        $dirProv    = trim($direccionProveedor) !== '' ? trim($direccionProveedor) : (string)($facturas[0]['direccion_proveedor'] ?? '');

        $sumaTotalFacturas = 0.0;
        $sumaExento        = 0.0;
        $sumaBase          = 0.0;
        $sumaIvaCausado    = 0.0;
        $sumaIvaRetenido   = 0.0;

        foreach ($facturas as $f) {
            if ($f['retencion_iva_id'] !== null) {
                throw new RuntimeException('La factura N° ' . $f['numero_factura'] . ' ya posee un comprobante de retención asociado.');
            }
            if ($f['rif_proveedor'] !== $rifProv) {
                throw new RuntimeException('Todas las facturas agrupadas en un mismo comprobante deben pertenecer al mismo RIF de proveedor (' . $rifProv . ').');
            }

            $base = (float)$f['base_imponible_general'] + (float)$f['base_imponible_reducida'] + (float)$f['base_imponible_adicional'];
            $iva  = (float)$f['monto_iva_general'] + (float)$f['monto_iva_reducida'] + (float)$f['monto_iva_adicional'];
            $ret  = round($iva * ($porcentajeRetencion / 100), 2);

            $sumaTotalFacturas = round($sumaTotalFacturas + (float)$f['total_compra_con_iva'], 2);
            $sumaExento        = round($sumaExento + (float)$f['compras_exentas'], 2);
            $sumaBase          = round($sumaBase + $base, 2);
            $sumaIvaCausado    = round($sumaIvaCausado + $iva, 2);
            $sumaIvaRetenido   = round($sumaIvaRetenido + $ret, 2);
        }

        if ($sumaIvaCausado <= 0) {
            throw new RuntimeException('Las facturas seleccionadas no poseen impuesto IVA causado sujeto a retención.');
        }

        Database::beginTransaction();

        try {
            $numeroComprobante = $this->generarCorrelativoIva($empresaId, $periodoFiscal);

            $stmtIns = $this->db->prepare(
                'INSERT INTO retenciones_iva (
                    empresa_id, numero_comprobante, fecha_emision, periodo_fiscal,
                    rif_proveedor, nombre_proveedor, direccion_proveedor,
                    porcentaje_retencion, total_facturas, total_exento, total_base_imponible,
                    monto_iva_causado, monto_iva_retenido, estado
                ) VALUES (
                    :empresa_id, :numero_comprobante, :fecha_emision, :periodo_fiscal,
                    :rif_proveedor, :nombre_proveedor, :direccion_proveedor,
                    :porcentaje_retencion, :total_facturas, :total_exento, :total_base_imponible,
                    :monto_iva_causado, :monto_iva_retenido, "EMITIDO"
                )'
            );

            $stmtIns->execute([
                ':empresa_id'           => $empresaId,
                ':numero_comprobante'   => $numeroComprobante,
                ':fecha_emision'        => $fechaEmision,
                ':periodo_fiscal'       => $periodoFiscal,
                ':rif_proveedor'        => $rifProv,
                ':nombre_proveedor'     => $nombreProv,
                ':direccion_proveedor'  => $dirProv ?: null,
                ':porcentaje_retencion' => $porcentajeRetencion,
                ':total_facturas'       => $sumaTotalFacturas,
                ':total_exento'         => $sumaExento,
                ':total_base_imponible' => $sumaBase,
                ':monto_iva_causado'    => $sumaIvaCausado,
                ':monto_iva_retenido'   => $sumaIvaRetenido,
            ]);

            $retencionId = (int)$this->db->lastInsertId();

            // Actualizar cada factura en libro_compras vinculando retencion_iva_id
            $stmtUpd = $this->db->prepare(
                'UPDATE libro_compras
                 SET retencion_iva_id = :ret_id,
                     porcentaje_retencion_iva = :porc,
                     monto_iva_retenido = ROUND((monto_iva_general + monto_iva_reducida + monto_iva_adicional) * (:porc2 / 100), 2),
                     direccion_proveedor = COALESCE(:dir, direccion_proveedor)
                 WHERE id = :id AND empresa_id = :empresa_id'
            );

            foreach ($ids as $idCompra) {
                $stmtUpd->execute([
                    ':ret_id'     => $retencionId,
                    ':porc'       => $porcentajeRetencion,
                    ':porc2'      => $porcentajeRetencion,
                    ':dir'        => $dirProv ?: null,
                    ':id'         => $idCompra,
                    ':empresa_id' => $empresaId,
                ]);
            }

            Database::commit();

            return [
                'id'                 => $retencionId,
                'numero_comprobante' => $numeroComprobante,
                'monto_retenido'     => $sumaIvaRetenido,
            ];
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene un Comprobante de Retención de IVA junto con todas sus facturas asociadas para Impresión Oficial
     */
    public function obtenerComprobanteIvaConFacturas(int $retencionId, int $empresaId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM retenciones_iva WHERE id = :id AND empresa_id = :empresa_id LIMIT 1'
        );
        $stmt->execute([':id' => $retencionId, ':empresa_id' => $empresaId]);
        $comprobante = $stmt->fetch();

        if (!$comprobante) {
            return null;
        }

        $stmtFact = $this->db->prepare(
            'SELECT * FROM libro_compras
             WHERE retencion_iva_id = :ret_id AND empresa_id = :empresa_id
             ORDER BY fecha_factura ASC, id ASC'
        );
        $stmtFact->execute([':ret_id' => $retencionId, ':empresa_id' => $empresaId]);
        $comprobante['facturas'] = $stmtFact->fetchAll();

        return $comprobante;
    }

    // =========================================================================
    // SECCIÓN 3: COMPROBANTES DE RETENCIÓN DE ISLR (DECRETO 1.808)
    // =========================================================================

    /**
     * Lista el catálogo precargado de Conceptos de Retención de ISLR (Decreto 1.808)
     */
    public function listarConceptosIslr(): array
    {
        return $this->db->query(
            'SELECT * FROM conceptos_islr WHERE activo = 1 ORDER BY codigo ASC'
        )->fetchAll();
    }

    /**
     * Genera el correlativo secuencial de Comprobante ISLR por empresa y período
     */
    public function generarCorrelativoIslr(int $empresaId, string $periodo): string
    {
        $prefijo = 'ISLR-' . str_replace('-', '', $periodo) . '-';

        $stmt = $this->db->prepare(
            'SELECT numero_comprobante FROM retenciones_islr
             WHERE empresa_id = :empresa_id
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([':empresa_id' => $empresaId]);
        $ultimo = $stmt->fetchColumn();

        $secuencia = 1;
        if (is_string($ultimo) && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $secuencia = (int)$m[1] + 1;
        }

        return $prefijo . str_pad((string)$secuencia, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Lista los comprobantes de retención de ISLR emitidos por la empresa activa
     */
    public function listarRetencionesIslr(int $empresaId, ?string $periodo = null): array
    {
        $sql = 'SELECT * FROM retenciones_islr WHERE empresa_id = :empresa_id';
        $params = [':empresa_id' => $empresaId];

        if ($periodo !== null && $periodo !== '') {
            $sql .= ' AND periodo_fiscal = :periodo';
            $params[':periodo'] = $periodo;
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Registra un Comprobante de Retención de ISLR aplicando el porcentaje y el sustraendo según UT
     */
    public function emitirComprobanteIslr(int $empresaId, array $datos): array
    {
        $cfg = $this->obtenerConfiguracionFiscal($empresaId);

        $fechaEmision   = trim((string)($datos['fecha_emision'] ?? date('Y-m-d')));
        $periodoFiscal  = trim((string)($datos['periodo_fiscal'] ?? substr($fechaEmision, 0, 7)));
        $rifBenef       = strtoupper(trim((string)($datos['rif_beneficiario'] ?? '')));
        $nombreBenef    = trim((string)($datos['nombre_beneficiario'] ?? ''));
        $dirBenef       = trim((string)($datos['direccion_beneficiario'] ?? ''));
        $tipoPersona    = strtoupper(trim((string)($datos['tipo_persona'] ?? 'PJD')));
        $codigoConcepto = trim((string)($datos['codigo_concepto'] ?? '004-PJ'));
        $descConcepto   = trim((string)($datos['descripcion_concepto'] ?? 'Honorarios Profesionales'));
        $numFactura     = trim((string)($datos['numero_factura'] ?? ''));
        $numControl     = trim((string)($datos['numero_control'] ?? ''));
        $montoTotal     = round((float)($datos['monto_total_factura'] ?? 0), 2);
        $montoExento    = round((float)($datos['monto_exento'] ?? 0), 2);
        $baseImponible  = round((float)($datos['base_imponible'] ?? 0), 2);
        $porcentajeIslr = round((float)($datos['porcentaje_islr'] ?? 5.00), 2);
        $aplicaSustraendo = !empty($datos['aplica_sustraendo']) || $tipoPersona === 'PNR';

        if (!preg_match('/^[VEJGP]-\d{8}-\d$/', $rifBenef)) {
            throw new RuntimeException('El RIF del beneficiario debe tener el formato SENIAT (Ej: J-40206401-2 o V-12345678-9).');
        }

        if ($nombreBenef === '' || $baseImponible <= 0) {
            throw new RuntimeException('Nombre del beneficiario y Base Imponible sujeta a retención (> 0) son obligatorios.');
        }

        // Cálculo de Sustraendo según Decreto 1.808: UT * Factor (83.3334) * (Porcentaje / 100)
        $sustraendo = 0.00;
        if ($aplicaSustraendo && $tipoPersona === 'PNR') {
            $ut     = (float)$cfg['valor_unidad_tributaria'];
            $factor = (float)$cfg['factor_sustraendo_islr'];
            $sustraendo = round($ut * $factor * ($porcentajeIslr / 100), 2);
        }

        $impuestoBruto = round($baseImponible * ($porcentajeIslr / 100), 2);
        $montoRetenido = max(0.00, round($impuestoBruto - $sustraendo, 2));

        if ($montoTotal <= 0) {
            $montoTotal = $baseImponible + $montoExento;
        }

        $numeroComprobante = $this->generarCorrelativoIslr($empresaId, $periodoFiscal);

        $sql = 'INSERT INTO retenciones_islr (
                    empresa_id, numero_comprobante, fecha_emision, periodo_fiscal,
                    rif_beneficiario, nombre_beneficiario, direccion_beneficiario,
                    tipo_persona, codigo_concepto, descripcion_concepto,
                    numero_factura, numero_control, monto_total_factura, monto_exento,
                    base_imponible, porcentaje_islr, sustraendo, monto_retenido, estado
                ) VALUES (
                    :empresa_id, :numero_comprobante, :fecha_emision, :periodo_fiscal,
                    :rif_beneficiario, :nombre_beneficiario, :direccion_beneficiario,
                    :tipo_persona, :codigo_concepto, :descripcion_concepto,
                    :numero_factura, :numero_control, :monto_total_factura, :monto_exento,
                    :base_imponible, :porcentaje_islr, :sustraendo, :monto_retenido, "EMITIDO"
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id'             => $empresaId,
            ':numero_comprobante'     => $numeroComprobante,
            ':fecha_emision'          => $fechaEmision,
            ':periodo_fiscal'         => $periodoFiscal,
            ':rif_beneficiario'       => $rifBenef,
            ':nombre_beneficiario'    => $nombreBenef,
            ':direccion_beneficiario' => $dirBenef ?: null,
            ':tipo_persona'           => in_array($tipoPersona, ['PNR', 'PNNR', 'PJD', 'PJND'], true) ? $tipoPersona : 'PJD',
            ':codigo_concepto'        => $codigoConcepto,
            ':descripcion_concepto'   => $descConcepto,
            ':numero_factura'         => $numFactura ?: null,
            ':numero_control'         => $numControl ?: null,
            ':monto_total_factura'    => $montoTotal,
            ':monto_exento'           => $montoExento,
            ':base_imponible'         => $baseImponible,
            ':porcentaje_islr'        => $porcentajeIslr,
            ':sustraendo'             => $sustraendo,
            ':monto_retenido'         => $montoRetenido,
        ]);

        return [
            'id'                 => (int)$this->db->lastInsertId(),
            'numero_comprobante' => $numeroComprobante,
            'sustraendo'         => $sustraendo,
            'monto_retenido'     => $montoRetenido,
        ];
    }

    /**
     * Obtiene un Comprobante de Retención de ISLR para su Vista de Impresión Oficial
     */
    public function obtenerComprobanteIslr(int $id, int $empresaId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM retenciones_islr WHERE id = :id AND empresa_id = :empresa_id LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':empresa_id' => $empresaId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // =========================================================================
    // SECCIÓN 4: GENERACIÓN AUTOMÁTICA DE ASIENTOS CONTABLES DESDE FACTURAS
    // =========================================================================

    private function obtenerIdCuentaPorCodigoOTipo(int $empresaId, array $codigosPreferidos, string $tipoFallback): int
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM plan_cuentas
             WHERE empresa_id = :empresa_id AND codigo = :codigo AND acepta_movimiento = 1 AND activo = 1
             LIMIT 1'
        );

        foreach ($codigosPreferidos as $cod) {
            $stmt->execute([':empresa_id' => $empresaId, ':codigo' => $cod]);
            $id = (int)$stmt->fetchColumn();
            if ($id > 0) {
                return $id;
            }
        }

        $stmtFallback = $this->db->prepare(
            'SELECT id FROM plan_cuentas
             WHERE empresa_id = :empresa_id AND tipo = :tipo AND acepta_movimiento = 1 AND activo = 1
             ORDER BY codigo ASC LIMIT 1'
        );
        $stmtFallback->execute([':empresa_id' => $empresaId, ':tipo' => $tipoFallback]);
        return (int)$stmtFallback->fetchColumn();
    }

    private function crearAsientoAutomaticoCompra(
        int $empresaId,
        int $usuarioId,
        string $fecha,
        string $numFactura,
        string $proveedor,
        string $rif,
        float $montoBaseMasExento,
        float $montoIvaCredito,
        float $montoIgtf,
        float $montoIvaRetenido
    ): array {
        $ctaGastoOCompra = $this->obtenerIdCuentaPorCodigoOTipo($empresaId, ['5.1.01.005', '6.1.02.001', '6.1.03.006'], 'COSTO');
        $ctaCreditoFiscal= $this->obtenerIdCuentaPorCodigoOTipo($empresaId, ['1.4.01.005', '1.4.01.001'], 'ACTIVO');
        $ctaGastoIgtf    = $this->obtenerIdCuentaPorCodigoOTipo($empresaId, ['6.1.07.007', '6.1.07.003'], 'GASTOS');
        $ctaPorPagar     = $this->obtenerIdCuentaPorCodigoOTipo($empresaId, ['2.2.01.001'], 'PASIVO');
        $ctaRetIvaPagar  = $this->obtenerIdCuentaPorCodigoOTipo($empresaId, ['2.3.03.002'], 'PASIVO');

        $renglones = [];

        if ($montoBaseMasExento > 0) {
            $renglones[] = [
                'cuenta_id'   => $ctaGastoOCompra,
                'descripcion' => "Compra según Factura N° {$numFactura} ({$proveedor})",
                'debe'        => $montoBaseMasExento,
                'haber'       => 0.00,
            ];
        }

        if ($montoIvaCredito > 0) {
            $renglones[] = [
                'cuenta_id'   => $ctaCreditoFiscal,
                'descripcion' => "IVA Crédito Fiscal s/Factura N° {$numFactura}",
                'debe'        => $montoIvaCredito,
                'haber'       => 0.00,
            ];
        }

        if ($montoIgtf > 0) {
            $renglones[] = [
                'cuenta_id'   => $ctaGastoIgtf,
                'descripcion' => "Gasto IGTF 3% s/Factura N° {$numFactura}",
                'debe'        => $montoIgtf,
                'haber'       => 0.00,
            ];
        }

        $totalDebe = round($montoBaseMasExento + $montoIvaCredito + $montoIgtf, 2);
        $netoPorPagar = round($totalDebe - $montoIvaRetenido, 2);

        if ($netoPorPagar > 0) {
            $renglones[] = [
                'cuenta_id'   => $ctaPorPagar,
                'descripcion' => "Cuentas por Pagar Proveedor {$proveedor} ({$rif})",
                'debe'        => 0.00,
                'haber'       => $netoPorPagar,
            ];
        }

        if ($montoIvaRetenido > 0) {
            $renglones[] = [
                'cuenta_id'   => $ctaRetIvaPagar,
                'descripcion' => "Retención de IVA por Pagar s/Factura N° {$numFactura}",
                'debe'        => 0.00,
                'haber'       => $montoIvaRetenido,
            ];
        }

        $asientoModel = new AsientoModel();
        return $asientoModel->crearAsientoPartidaDoble($empresaId, $usuarioId, [
            'fecha'      => $fecha,
            'concepto'   => "Registro automático de Compra Factura N° {$numFactura} — {$proveedor} ({$rif})",
            'referencia' => 'FAC-' . $numFactura,
            'origen'     => 'COMPRAS',
        ], $renglones);
    }

    private function crearAsientoAutomaticoVenta(
        int $empresaId,
        int $usuarioId,
        string $fecha,
        string $numFactura,
        string $cliente,
        string $rif,
        float $montoBaseMasExento,
        float $montoIvaDebito,
        float $ivaRetenidoPorCliente
    ): array {
        $ctaPorCobrar      = $this->obtenerIdCuentaPorCodigoOTipo($empresaId, ['1.2.01.002', '1.2.01.001'], 'ACTIVO');
        $ctaRetIvaSoportada= $this->obtenerIdCuentaPorCodigoOTipo($empresaId, ['1.4.01.002'], 'ACTIVO');
        $ctaIngresoVenta   = $this->obtenerIdCuentaPorCodigoOTipo($empresaId, ['4.1.01.001', '4.1.01.006'], 'INGRESOS');
        $ctaDebitoFiscal   = $this->obtenerIdCuentaPorCodigoOTipo($empresaId, ['2.3.03.001'], 'PASIVO');

        $totalFactura = round($montoBaseMasExento + $montoIvaDebito, 2);
        $netoCobrar   = max(0.00, round($totalFactura - $ivaRetenidoPorCliente, 2));

        $renglones = [];

        if ($netoCobrar > 0) {
            $renglones[] = [
                'cuenta_id'   => $ctaPorCobrar,
                'descripcion' => "Cuentas por Cobrar Cliente {$cliente} ({$rif}) Factura N° {$numFactura}",
                'debe'        => $netoCobrar,
                'haber'       => 0.00,
            ];
        }

        if ($ivaRetenidoPorCliente > 0) {
            $renglones[] = [
                'cuenta_id'   => $ctaRetIvaSoportada,
                'descripcion' => "Retención de IVA soportada de cliente especial s/Factura N° {$numFactura}",
                'debe'        => $ivaRetenidoPorCliente,
                'haber'       => 0.00,
            ];
        }

        if ($montoBaseMasExento > 0) {
            $renglones[] = [
                'cuenta_id'   => $ctaIngresoVenta,
                'descripcion' => "Ingreso por Venta / Servicio según Factura N° {$numFactura}",
                'debe'        => 0.00,
                'haber'       => $montoBaseMasExento,
            ];
        }

        if ($montoIvaDebito > 0) {
            $renglones[] = [
                'cuenta_id'   => $ctaDebitoFiscal,
                'descripcion' => "IVA Débito Fiscal s/Factura N° {$numFactura}",
                'debe'        => 0.00,
                'haber'       => $montoIvaDebito,
            ];
        }

        $asientoModel = new AsientoModel();
        return $asientoModel->crearAsientoPartidaDoble($empresaId, $usuarioId, [
            'fecha'      => $fecha,
            'concepto'   => "Registro automático de Venta Factura N° {$numFactura} — {$cliente} ({$rif})",
            'referencia' => 'FAC-' . $numFactura,
            'origen'     => 'VENTAS',
        ], $renglones);
    }
}
