-- =============================================================================
-- SISTEMA INTEGRAL MULTI-EMPRESA: JURIS CONTABLE (SENIAT / LOTTT - VENEZUELA)
-- Motor: MySQL / MariaDB (InnoDB, utf8mb4_unicode_ci)
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '-04:00';

CREATE DATABASE IF NOT EXISTS `juris_contable`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `juris_contable`;

-- -----------------------------------------------------------------------------
-- 1. TABLA: usuarios
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin', 'contador', 'asistente') NOT NULL DEFAULT 'contador',
  `permisos_json` TEXT NULL DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_acceso` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `idx_usuarios_rol_activo` (`rol`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. TABLA: empresas (Clientes Multi-Tenant)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `empresas`;
CREATE TABLE `empresas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rif` VARCHAR(15) NOT NULL COMMENT 'Formato V/E/J/G/P-12345678-9',
  `razon_social` VARCHAR(200) NOT NULL,
  `nombre_comercial` VARCHAR(150) NULL DEFAULT NULL,
  `direccion_fiscal` TEXT NOT NULL,
  `telefono` VARCHAR(40) NULL DEFAULT NULL,
  `email_contacto` VARCHAR(150) NULL DEFAULT NULL,
  `representante_legal` VARCHAR(150) NULL DEFAULT NULL,
  `tipo_contribuyente` ENUM('Ordinario', 'Especial', 'Formal') NOT NULL DEFAULT 'Ordinario',
  `porcentaje_retencion_iva` ENUM('0', '75', '100') NOT NULL DEFAULT '75',
  `periodo_fiscal_activo` CHAR(7) NOT NULL DEFAULT '2026-11' COMMENT 'Formato YYYY-MM',
  `moneda_principal` VARCHAR(10) NOT NULL DEFAULT 'VES',
  `logo_path` VARCHAR(255) NULL DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_empresas_rif` (`rif`),
  KEY `idx_empresas_activo` (`activo`),
  KEY `idx_empresas_tipo` (`tipo_contribuyente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. TABLA: configuracion_fiscal (Parámetros SENIAT / LOTTT por Empresa)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `configuracion_fiscal`;
CREATE TABLE `configuracion_fiscal` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `alicuota_iva_general` DECIMAL(5,2) NOT NULL DEFAULT 16.00,
  `alicuota_iva_reducida` DECIMAL(5,2) NOT NULL DEFAULT 8.00,
  `alicuota_iva_adicional` DECIMAL(5,2) NOT NULL DEFAULT 31.00,
  `alicuota_igtf` DECIMAL(5,2) NOT NULL DEFAULT 3.00,
  `valor_unidad_tributaria` DECIMAL(12,2) NOT NULL DEFAULT 9.00,
  `factor_sustraendo_islr` DECIMAL(10,4) NOT NULL DEFAULT 83.3334,
  `proximo_correlativo_iva` INT UNSIGNED NOT NULL DEFAULT 1,
  `proximo_correlativo_islr` INT UNSIGNED NOT NULL DEFAULT 1,
  `porcentaje_sso_empleado` DECIMAL(5,2) NOT NULL DEFAULT 4.00,
  `porcentaje_rpe_empleado` DECIMAL(5,2) NOT NULL DEFAULT 0.50,
  `porcentaje_faov_empleado` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `porcentaje_sso_patronal` DECIMAL(5,2) NOT NULL DEFAULT 9.00 COMMENT '9%, 10% u 11% según riesgo',
  `porcentaje_rpe_patronal` DECIMAL(5,2) NOT NULL DEFAULT 2.00,
  `porcentaje_faov_patronal` DECIMAL(5,2) NOT NULL DEFAULT 2.00,
  `porcentaje_inces_patronal` DECIMAL(5,2) NOT NULL DEFAULT 2.00,
  `porcentaje_inces_empleado` DECIMAL(5,2) NOT NULL DEFAULT 0.50,
  `monto_cestaticket_mensual` DECIMAL(14,2) NOT NULL DEFAULT 1800.00,
  `tasa_bcv_usd` DECIMAL(18,4) NOT NULL DEFAULT 45.0000,
  `salario_minimo_referencia` DECIMAL(18,2) NOT NULL DEFAULT 15000.00,
  `tope_salarios_minimos_sso` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `dias_base_calculo` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `factor_hora_extra_diurna` DECIMAL(5,2) NOT NULL DEFAULT 1.50,
  `factor_bono_nocturno` DECIMAL(5,2) NOT NULL DEFAULT 0.30,
  `excedente_credito_fiscal_anterior` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_fiscal_empresa` (`empresa_id`),
  CONSTRAINT `fk_config_fiscal_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. TABLA: plan_cuentas (Catálogo Contable Jerárquico por Empresa)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `plan_cuentas`;
CREATE TABLE `plan_cuentas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `codigo` VARCHAR(30) NOT NULL,
  `nombre` VARCHAR(180) NOT NULL,
  `tipo` ENUM('ACTIVO', 'PASIVO', 'CAPITAL', 'INGRESOS', 'COSTO', 'GASTOS') NOT NULL,
  `naturaleza` ENUM('DEUDORA', 'ACREEDORA') NOT NULL,
  `nivel` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `codigo_padre` VARCHAR(30) NULL DEFAULT NULL,
  `acepta_movimiento` TINYINT(1) NOT NULL DEFAULT 0,
  `saldo_inicial` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_cuentas_empresa_codigo` (`empresa_id`, `codigo`),
  KEY `idx_plan_cuentas_empresa_tipo` (`empresa_id`, `tipo`),
  KEY `idx_plan_cuentas_padre` (`empresa_id`, `codigo_padre`),
  KEY `idx_plan_cuentas_movimiento` (`empresa_id`, `acepta_movimiento`),
  CONSTRAINT `fk_plan_cuentas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. TABLA: asientos (Cabecera de Asientos Contables - Libro Diario)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `asientos`;
CREATE TABLE `asientos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `numero_asiento` VARCHAR(25) NOT NULL,
  `fecha` DATE NOT NULL,
  `periodo` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `concepto` VARCHAR(500) NOT NULL,
  `referencia` VARCHAR(80) NULL DEFAULT NULL,
  `origen` ENUM('MANUAL', 'COMPRAS', 'VENTAS', 'NOMINA', 'CIERRE') NOT NULL DEFAULT 'MANUAL',
  `total_debe` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_haber` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('BORRADOR', 'PUBLICADO', 'ANULADO') NOT NULL DEFAULT 'PUBLICADO',
  `usuario_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_asientos_empresa_numero` (`empresa_id`, `numero_asiento`),
  KEY `idx_asientos_empresa_fecha` (`empresa_id`, `fecha`),
  KEY `idx_asientos_empresa_periodo` (`empresa_id`, `periodo`, `estado`),
  KEY `fk_asientos_usuario` (`usuario_id`),
  CONSTRAINT `fk_asientos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asientos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. TABLA: asiento_detalles (Renglones de Partida Doble)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `asiento_detalles`;
CREATE TABLE `asiento_detalles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asiento_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NOT NULL,
  `cuenta_id` INT UNSIGNED NOT NULL,
  `descripcion` VARCHAR(255) NULL DEFAULT NULL,
  `debe` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `haber` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_asiento_det_asiento` (`asiento_id`),
  KEY `idx_asiento_det_empresa_cuenta` (`empresa_id`, `cuenta_id`),
  CONSTRAINT `fk_asiento_det_asiento` FOREIGN KEY (`asiento_id`) REFERENCES `asientos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asiento_det_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asiento_det_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `plan_cuentas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. TABLA: retenciones_iva (Comprobantes SNAT/2015/0049)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `retenciones_iva`;
CREATE TABLE `retenciones_iva` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `numero_comprobante` CHAR(14) NOT NULL COMMENT 'Formato AAAAMM00000001',
  `fecha_emision` DATE NOT NULL,
  `periodo_fiscal` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `rif_proveedor` VARCHAR(15) NOT NULL,
  `nombre_proveedor` VARCHAR(200) NOT NULL,
  `direccion_proveedor` VARCHAR(255) NULL DEFAULT NULL,
  `porcentaje_retencion` DECIMAL(5,2) NOT NULL DEFAULT 75.00,
  `total_facturas` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_exento` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_base_imponible` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `monto_iva_causado` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `monto_iva_retenido` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('EMITIDO', 'ANULADO') NOT NULL DEFAULT 'EMITIDO',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ret_iva_empresa_comprobante` (`empresa_id`, `numero_comprobante`),
  KEY `idx_ret_iva_periodo` (`empresa_id`, `periodo_fiscal`),
  CONSTRAINT `fk_ret_iva_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. TABLA: retenciones_islr (Comprobantes Decreto 1.808)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `retenciones_islr`;
CREATE TABLE `retenciones_islr` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `numero_comprobante` VARCHAR(20) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `periodo_fiscal` CHAR(7) NOT NULL,
  `rif_beneficiario` VARCHAR(15) NOT NULL,
  `nombre_beneficiario` VARCHAR(200) NOT NULL,
  `direccion_beneficiario` VARCHAR(255) NULL DEFAULT NULL,
  `tipo_persona` ENUM('PNR', 'PNNR', 'PJD', 'PJND') NOT NULL DEFAULT 'PJD',
  `codigo_concepto` VARCHAR(10) NOT NULL COMMENT 'Ej: 001 Honorarios, 053 Servicios',
  `descripcion_concepto` VARCHAR(200) NOT NULL,
  `numero_factura` VARCHAR(40) NULL DEFAULT NULL,
  `numero_control` VARCHAR(40) NULL DEFAULT NULL,
  `monto_total_factura` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `monto_exento` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `base_imponible` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `porcentaje_islr` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `sustraendo` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `monto_retenido` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('EMITIDO', 'ANULADO') NOT NULL DEFAULT 'EMITIDO',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ret_islr_empresa_comprobante` (`empresa_id`, `numero_comprobante`),
  KEY `idx_ret_islr_periodo` (`empresa_id`, `periodo_fiscal`),
  CONSTRAINT `fk_ret_islr_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. TABLA: libro_compras (Registro SENIAT de Compras)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `libro_compras`;
CREATE TABLE `libro_compras` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `periodo_fiscal` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `fecha_factura` DATE NOT NULL,
  `rif_proveedor` VARCHAR(15) NOT NULL,
  `nombre_proveedor` VARCHAR(200) NOT NULL,
  `direccion_proveedor` VARCHAR(255) NULL DEFAULT NULL,
  `tipo_documento` ENUM('FACTURA', 'NOTA_CREDITO', 'NOTA_DEBITO', 'CERT_IMPORTACION') NOT NULL DEFAULT 'FACTURA',
  `tipo_transaccion` VARCHAR(15) NOT NULL DEFAULT '01-Reg',
  `numero_factura` VARCHAR(40) NOT NULL,
  `numero_control` VARCHAR(40) NOT NULL,
  `numero_nota_credito` VARCHAR(40) NULL DEFAULT NULL,
  `numero_nota_debito` VARCHAR(40) NULL DEFAULT NULL,
  `numero_factura_afectada` VARCHAR(40) NULL DEFAULT NULL,
  `compras_exentas` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `base_imponible_general` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `alicuota_general` DECIMAL(5,2) NOT NULL DEFAULT 16.00,
  `monto_iva_general` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `base_imponible_reducida` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `alicuota_reducida` DECIMAL(5,2) NOT NULL DEFAULT 8.00,
  `monto_iva_reducida` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `base_imponible_adicional` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `alicuota_adicional` DECIMAL(5,2) NOT NULL DEFAULT 31.00,
  `monto_iva_adicional` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `base_igtf` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `alicuota_igtf` DECIMAL(5,2) NOT NULL DEFAULT 3.00,
  `monto_igtf` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_compra_con_iva` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `porcentaje_retencion_iva` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `monto_iva_retenido` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `retencion_iva_id` INT UNSIGNED NULL DEFAULT NULL,
  `retencion_islr_id` INT UNSIGNED NULL DEFAULT NULL,
  `asiento_id` INT UNSIGNED NULL DEFAULT NULL,
  `estado` ENUM('ACTIVO', 'ANULADO') NOT NULL DEFAULT 'ACTIVO',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_libro_compras_empresa_periodo` (`empresa_id`, `periodo_fiscal`, `fecha_factura`),
  KEY `fk_lc_ret_iva` (`retencion_iva_id`),
  KEY `fk_lc_ret_islr` (`retencion_islr_id`),
  KEY `fk_lc_asiento` (`asiento_id`),
  CONSTRAINT `fk_lc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lc_ret_iva` FOREIGN KEY (`retencion_iva_id`) REFERENCES `retenciones_iva` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_lc_ret_islr` FOREIGN KEY (`retencion_islr_id`) REFERENCES `retenciones_islr` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_lc_asiento` FOREIGN KEY (`asiento_id`) REFERENCES `asientos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. TABLA: libro_ventas (Registro SENIAT de Ventas)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `libro_ventas`;
CREATE TABLE `libro_ventas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `periodo_fiscal` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `fecha_factura` DATE NOT NULL,
  `rif_cliente` VARCHAR(15) NOT NULL,
  `nombre_cliente` VARCHAR(200) NOT NULL,
  `tipo_contribuyente_cliente` ENUM('Ordinario', 'Especial', 'No Sujeto') NOT NULL DEFAULT 'Ordinario',
  `tipo_documento` ENUM('FACTURA', 'NOTA_CREDITO', 'NOTA_DEBITO', 'REPORTE_Z') NOT NULL DEFAULT 'FACTURA',
  `tipo_transaccion` VARCHAR(15) NOT NULL DEFAULT '01-Reg',
  `numero_factura` VARCHAR(40) NOT NULL,
  `numero_control` VARCHAR(40) NOT NULL,
  `numero_nota_credito` VARCHAR(40) NULL DEFAULT NULL,
  `numero_nota_debito` VARCHAR(40) NULL DEFAULT NULL,
  `numero_factura_afectada` VARCHAR(40) NULL DEFAULT NULL,
  `ventas_exentas` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `base_imponible_general` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `alicuota_general` DECIMAL(5,2) NOT NULL DEFAULT 16.00,
  `monto_iva_general` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `base_imponible_reducida` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `alicuota_reducida` DECIMAL(5,2) NOT NULL DEFAULT 8.00,
  `monto_iva_reducida` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `base_imponible_adicional` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `alicuota_adicional` DECIMAL(5,2) NOT NULL DEFAULT 31.00,
  `monto_iva_adicional` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `base_igtf` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `alicuota_igtf` DECIMAL(5,2) NOT NULL DEFAULT 3.00,
  `monto_igtf` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_venta_con_iva` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `numero_comprobante_ret_cliente` VARCHAR(20) NULL DEFAULT NULL,
  `iva_retenido_por_cliente` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `asiento_id` INT UNSIGNED NULL DEFAULT NULL,
  `estado` ENUM('ACTIVO', 'ANULADO') NOT NULL DEFAULT 'ACTIVO',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_libro_ventas_empresa_periodo` (`empresa_id`, `periodo_fiscal`, `fecha_factura`),
  KEY `fk_lv_asiento` (`asiento_id`),
  CONSTRAINT `fk_lv_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lv_asiento` FOREIGN KEY (`asiento_id`) REFERENCES `asientos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. TABLA: trabajadores (Nómina LOTTT por Empresa)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `trabajadores`;
CREATE TABLE `trabajadores` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `cedula` VARCHAR(15) NOT NULL COMMENT 'V-12345678 o E-12345678',
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(30) NULL DEFAULT NULL,
  `email` VARCHAR(120) NULL DEFAULT NULL,
  `fecha_nacimiento` DATE NULL DEFAULT NULL,
  `cargo` VARCHAR(120) NOT NULL,
  `departamento` VARCHAR(100) NULL DEFAULT 'Administración',
  `tipo_trabajador` ENUM('EMPLEADO', 'OBRERO', 'DIRECTIVO') NOT NULL DEFAULT 'EMPLEADO',
  `tipo_nomina` ENUM('QUINCENAL', 'MENSUAL', 'SEMANAL') NOT NULL DEFAULT 'QUINCENAL',
  `fecha_ingreso` DATE NOT NULL,
  `fecha_egreso` DATE NULL DEFAULT NULL,
  `salario_mensual` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `aplica_sso` TINYINT(1) NOT NULL DEFAULT 1,
  `aplica_rpe` TINYINT(1) NOT NULL DEFAULT 1,
  `aplica_faov` TINYINT(1) NOT NULL DEFAULT 1,
  `aplica_cestaticket` TINYINT(1) NOT NULL DEFAULT 1,
  `porcentaje_islr` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `banco` VARCHAR(80) NULL DEFAULT NULL,
  `numero_cuenta` VARCHAR(30) NULL DEFAULT NULL,
  `estatus_laboral` ENUM('ACTIVO', 'VACACIONES', 'REPOSO', 'EGRESADO') NOT NULL DEFAULT 'ACTIVO',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trabajadores_empresa_cedula` (`empresa_id`, `cedula`),
  KEY `idx_trabajadores_empresa_activo` (`empresa_id`, `activo`),
  CONSTRAINT `fk_trabajadores_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 12. TABLA: nominas (Cabecera de Lotes de Nómina Quincenal/Mensual)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `nominas`;
CREATE TABLE `nominas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `codigo_nomina` VARCHAR(30) NOT NULL,
  `periodo` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `tipo_frecuencia` ENUM('PRIMERA_QUINCENA', 'SEGUNDA_QUINCENA', 'MENSUAL', 'CESTATICKET') NOT NULL DEFAULT 'PRIMERA_QUINCENA',
  `fecha_desde` DATE NOT NULL,
  `fecha_hasta` DATE NOT NULL,
  `lunes_del_mes` TINYINT UNSIGNED NOT NULL DEFAULT 4 COMMENT '4 o 5 lunes para cálculo IVSS/RPE',
  `descripcion` VARCHAR(200) NULL DEFAULT NULL,
  `total_asignaciones` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_deducciones` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_cestaticket` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_neto_pagar` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_aporte_patronal` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `asiento_id` INT UNSIGNED NULL DEFAULT NULL,
  `estado` ENUM('BORRADOR', 'PROCESADA', 'PAGADA', 'ANULADA') NOT NULL DEFAULT 'PROCESADA',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nominas_empresa_codigo` (`empresa_id`, `codigo_nomina`),
  KEY `idx_nominas_empresa_periodo` (`empresa_id`, `periodo`),
  KEY `fk_nominas_asiento` (`asiento_id`),
  CONSTRAINT `fk_nominas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_nominas_asiento` FOREIGN KEY (`asiento_id`) REFERENCES `asientos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 13. TABLA: nomina_detalles (Recibos de Pago por Trabajador)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `nomina_detalles`;
CREATE TABLE `nomina_detalles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nomina_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NOT NULL,
  `trabajador_id` INT UNSIGNED NOT NULL,
  `dias_trabajados` DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  `dias_habiles` DECIMAL(5,2) NOT NULL DEFAULT 11.00,
  `dias_descanso` DECIMAL(5,2) NOT NULL DEFAULT 4.00,
  `dias_feriados` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `sueldo_diario` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `sueldo_basico_periodo` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `monto_dias_habiles` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `monto_dias_descanso` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `monto_dias_feriados` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `horas_extras` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `bonificaciones` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `cestaticket` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `retencion_sso` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `retencion_rpe` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `retencion_faov` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `retencion_islr` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `otras_deducciones` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `aporte_patronal_sso` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `aporte_patronal_rpe` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `aporte_patronal_faov` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `aporte_patronal_inces` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_asignaciones` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `total_deducciones` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `neto_pagar` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_nomina_det_nomina` (`nomina_id`),
  KEY `idx_nomina_det_trabajador` (`empresa_id`, `trabajador_id`),
  CONSTRAINT `fk_nomina_det_nomina` FOREIGN KEY (`nomina_id`) REFERENCES `nominas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_nomina_det_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_nomina_det_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14. TABLA: conceptos_islr (Catálogo Oficial Decreto 1.808)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `conceptos_islr`;
CREATE TABLE `conceptos_islr` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 15. TABLA: cestaticket_lotes (Bono de Alimentación por Empresa)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cestaticket_lotes`;
CREATE TABLE `cestaticket_lotes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 16. TABLA: cestaticket_detalles (Detalle por Trabajador de Cestaticket)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cestaticket_detalles`;
CREATE TABLE `cestaticket_detalles` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 17. Cuentas Bancarias por Empresa (Enlace Bancario)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `conciliaciones_bancarias`;
DROP TABLE IF EXISTS `movimientos_bancarios`;
DROP TABLE IF EXISTS `cuentas_bancarias`;

CREATE TABLE `cuentas_bancarias` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 18. Movimientos Bancarios (Estado de Cuenta / Libro Auxiliar)
-- -----------------------------------------------------------------------------
CREATE TABLE `movimientos_bancarios` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 19. Conciliaciones Bancarias Mensuales
-- -----------------------------------------------------------------------------
CREATE TABLE `conciliaciones_bancarias` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
('009-PN', 'Comisiones Mercantiles y de Intermediación (Persona Natural Residente)', 'PNR', 100.00, 3.00, 1, 1);

-- =============================================================================
-- SEED INICIAL DE DATOS
-- =============================================================================

-- 1. Usuario Administrador (Clave: Admin123*)
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password_hash`, `rol`, `activo`) VALUES
(1, 'Administrador Juris Contable', 'admin@juriscontable.com', '$2y$12$.6qlAvZrUcO.St8lKR0ypej5XR9TCKP3iitx74aZaR3GgzgUU3KoW', 'admin', 1),
(2, 'Lic. María Virginia Rodríguez', 'contador@juriscontable.com', '$2y$12$.6qlAvZrUcO.St8lKR0ypej5XR9TCKP3iitx74aZaR3GgzgUU3KoW', 'contador', 1);

-- 2. Empresas Clientes de Ejemplo
INSERT INTO `empresas` (
  `id`, `rif`, `razon_social`, `nombre_comercial`, `direccion_fiscal`,
  `telefono`, `email_contacto`, `representante_legal`,
  `tipo_contribuyente`, `porcentaje_retencion_iva`, `periodo_fiscal_activo`, `moneda_principal`, `activo`
) VALUES
(
  1,
  'J-40123456-7',
  'INVERSIONES Y ADMINISTRADORA EL ROSAL, C.A.',
  'Administradora El Rosal',
  'Av. Francisco de Miranda, Torre Europa, Piso 8, Ofic. 8-B, Urb. El Rosal, Chacao, Miranda',
  '0212-9524410',
  'administracion@elrosalca.com.ve',
  'Dumelys González',
  'Especial',
  '75',
  '2026-11',
  'VES',
  1
),
(
  2,
  'J-50987654-3',
  'DISTRIBUIDORA TECNOLÓGICA ORINOCO, S.A.',
  'TecnoOrinoco',
  'Av. Las Américas, Centro Empresarial Guayana, Nivel PB, Local 14, Puerto Ordaz, Bolívar',
  '0286-9618820',
  'finanzas@tecnoorinoco.com.ve',
  'María Virginia Rodríguez',
  'Ordinario',
  '0',
  '2026-11',
  'VES',
  1
);

-- 3. Configuración Fiscal Inicial para Ambas Empresas
INSERT INTO `configuracion_fiscal` (
  `empresa_id`, `alicuota_iva_general`, `alicuota_iva_reducida`, `alicuota_iva_adicional`,
  `alicuota_igtf`, `valor_unidad_tributaria`, `factor_sustraendo_islr`,
  `proximo_correlativo_iva`, `proximo_correlativo_islr`,
  `porcentaje_sso_empleado`, `porcentaje_rpe_empleado`, `porcentaje_faov_empleado`,
  `porcentaje_sso_patronal`, `porcentaje_rpe_patronal`, `porcentaje_faov_patronal`,
  `monto_cestaticket_mensual`
) VALUES
(1, 16.00, 8.00, 31.00, 3.00, 9.00, 83.3334, 1, 1, 4.00, 0.50, 1.00, 9.00, 2.00, 2.00, 1800.00),
(2, 16.00, 8.00, 31.00, 3.00, 9.00, 83.3334, 1, 1, 4.00, 0.50, 1.00, 9.00, 2.00, 2.00, 1800.00);

-- 4. Catálogo del Plan de Cuentas Base (Cargado para Empresa 1: J-40123456-7)
INSERT INTO `plan_cuentas` (`empresa_id`, `codigo`, `nombre`, `tipo`, `naturaleza`, `nivel`, `codigo_padre`, `acepta_movimiento`) VALUES
-- GRUPO 1: ACTIVO
(1, '1', 'ACTIVO', 'ACTIVO', 'DEUDORA', 1, NULL, 0),
(1, '1.1', 'Efectivo y Equivalente de Efectivo', 'ACTIVO', 'DEUDORA', 2, '1', 0),
(1, '1.1.01', 'Caja', 'ACTIVO', 'DEUDORA', 3, '1.1', 0),
(1, '1.1.01.001', 'Caja', 'ACTIVO', 'DEUDORA', 4, '1.1.01', 1),
(1, '1.1.01.002', 'Caja Chica', 'ACTIVO', 'DEUDORA', 4, '1.1.01', 1),
(1, '1.1.02', 'Bancos', 'ACTIVO', 'DEUDORA', 3, '1.1', 0),
(1, '1.1.02.001', 'BANCO PROVINCIAL', 'ACTIVO', 'DEUDORA', 4, '1.1.02', 1),
(1, '1.1.02.002', 'BANCAMIGA', 'ACTIVO', 'DEUDORA', 4, '1.1.02', 1),
(1, '1.1.02.003', 'BANCO DE VENEZUELA', 'ACTIVO', 'DEUDORA', 4, '1.1.02', 1),
(1, '1.1.02.004', 'PROVINCIAL DIVISAS', 'ACTIVO', 'DEUDORA', 4, '1.1.02', 1),
(1, '1.1.02.005', 'BANCAMIGA DIVISAS', 'ACTIVO', 'DEUDORA', 4, '1.1.02', 1),
(1, '1.2', 'Cuentas por cobrar', 'ACTIVO', 'DEUDORA', 2, '1', 0),
(1, '1.2.01', 'Cuentas por cobrar', 'ACTIVO', 'DEUDORA', 3, '1.2', 0),
(1, '1.2.01.001', 'Cuentas por Cobrar', 'ACTIVO', 'DEUDORA', 4, '1.2.01', 1),
(1, '1.2.01.002', 'Cuentas por Cobrar Clientes', 'ACTIVO', 'DEUDORA', 4, '1.2.01', 1),
(1, '1.2.02', 'Cuentas por cobrar accionistas', 'ACTIVO', 'DEUDORA', 3, '1.2', 0),
(1, '1.2.02.001', 'Cuenta por cobrar Dumelys Gonzalez', 'ACTIVO', 'DEUDORA', 4, '1.2.02', 1),
(1, '1.2.02.002', 'Cuenta por cobrar Maria Virginia Rodriguez', 'ACTIVO', 'DEUDORA', 4, '1.2.02', 1),
(1, '1.2.03', 'Cuentas por cobrar empleados', 'ACTIVO', 'DEUDORA', 3, '1.2', 0),
(1, '1.2.03.001', 'CUENTAS POR COBRAR EMPLEADOS', 'ACTIVO', 'DEUDORA', 4, '1.2.03', 1),
(1, '1.3', 'Mercancía', 'ACTIVO', 'DEUDORA', 2, '1', 0),
(1, '1.3.01', 'Mercancía en existencia', 'ACTIVO', 'DEUDORA', 3, '1.3', 0),
(1, '1.3.01.001', 'Mercancía en existencia', 'ACTIVO', 'DEUDORA', 4, '1.3.01', 1),
(1, '1.4', 'Activo por Impuesto Corriente', 'ACTIVO', 'DEUDORA', 2, '1', 0),
(1, '1.4.01', 'Activo por Impuesto Corriente', 'ACTIVO', 'DEUDORA', 3, '1.4', 0),
(1, '1.4.01.001', 'Excedente Credito Fiscal Iva', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1),
(1, '1.4.01.002', 'Retenciones de IVA', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1),
(1, '1.4.01.003', 'Retenciones de ISLR', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1),
(1, '1.4.01.004', 'ISLR Pagado en Exceso', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1),
(1, '1.4.01.005', 'Credito Fiscal IVA', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1),
(1, '1.4.01.006', 'Impuesto Sobre la Renta Estimada', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1),
(1, '1.4.01.007', 'Ret Islr por Compensar', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1),
(1, '1.4.01.008', 'Ret Islr', 'ACTIVO', 'DEUDORA', 4, '1.4.01', 1),
(1, '1.5', 'Prepagados', 'ACTIVO', 'DEUDORA', 2, '1', 0),
(1, '1.5.01', 'Prepagados', 'ACTIVO', 'DEUDORA', 3, '1.5', 0),
(1, '1.5.01.001', 'Anticipo Proveedores', 'ACTIVO', 'DEUDORA', 4, '1.5.01', 1),
(1, '1.5.01.002', 'Anticipo Beneficio Alimentacion', 'ACTIVO', 'DEUDORA', 4, '1.5.01', 1),
(1, '1.5.01.003', 'PATENTE PAGADA POR ANTICIPADO', 'ACTIVO', 'DEUDORA', 4, '1.5.01', 1),
(1, '1.6', 'Propiedad planta y equipo', 'ACTIVO', 'DEUDORA', 2, '1', 0),
(1, '1.6.01', 'Propiedad planta y equipo', 'ACTIVO', 'DEUDORA', 3, '1.6', 0),
(1, '1.6.01.001', 'Terrenos', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1),
(1, '1.6.01.002', 'Edificios', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1),
(1, '1.6.01.003', 'Instalaciones', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1),
(1, '1.6.01.004', 'Maquinarias', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1),
(1, '1.6.01.005', 'Mobiliario y Equipos de Oficina', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1),
(1, '1.6.01.006', 'Equipos y Programas de Computacion', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1),
(1, '1.6.01.007', 'Vehiculos', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1),
(1, '1.6.01.008', 'Obras en Proceso', 'ACTIVO', 'DEUDORA', 4, '1.6.01', 1),
(1, '1.7', 'Intangible', 'ACTIVO', 'ACREEDORA', 2, '1', 0),
(1, '1.7.01', 'Depreciación y amortización acumulada', 'ACTIVO', 'ACREEDORA', 3, '1.7', 0),
(1, '1.7.01.001', 'Depreciacion Acumulada Edificios', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1),
(1, '1.7.01.002', 'Depreciacion Acumulada Instalaciones', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1),
(1, '1.7.01.003', 'Depreciacion Acumulada Maquinarias', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1),
(1, '1.7.01.004', 'Depreciacion Acumulada Mobiliario y Equi', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1),
(1, '1.7.01.005', 'Depreciacion acumulada Equipos y Program', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1),
(1, '1.7.01.006', 'Depreciacion Acumulada Vehiculos', 'ACTIVO', 'ACREEDORA', 4, '1.7.01', 1),

-- GRUPO 2: PASIVO
(1, '2', 'PASIVO', 'PASIVO', 'ACREEDORA', 1, NULL, 0),
(1, '2.1', 'Pasivo', 'PASIVO', 'ACREEDORA', 2, '2', 0),
(1, '2.1.01', 'Pasivo financieros', 'PASIVO', 'ACREEDORA', 3, '2.1', 0),
(1, '2.1.01.001', 'Credito Bancarios', 'PASIVO', 'ACREEDORA', 4, '2.1.01', 1),
(1, '2.2', 'Cuentas por Pagar', 'PASIVO', 'ACREEDORA', 2, '2', 0),
(1, '2.2.01', 'Cuentas por Pagar', 'PASIVO', 'ACREEDORA', 3, '2.2', 0),
(1, '2.2.01.001', 'Cuentas por Pagar', 'PASIVO', 'ACREEDORA', 4, '2.2.01', 1),
(1, '2.3', 'Cuentas por Pagar otros', 'PASIVO', 'ACREEDORA', 2, '2', 0),
(1, '2.3.01', 'Cuentas por Pagar otros', 'PASIVO', 'ACREEDORA', 3, '2.3', 0),
(1, '2.3.01.001', 'Cuenta por pagar Accionistas', 'PASIVO', 'ACREEDORA', 4, '2.3.01', 1),
(1, '2.3.01.002', 'Anticipo Cliente', 'PASIVO', 'ACREEDORA', 4, '2.3.01', 1),
(1, '2.3.02', 'Retenciones y aportes por pagar', 'PASIVO', 'ACREEDORA', 3, '2.3', 0),
(1, '2.3.02.001', 'Retencion SSO', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1),
(1, '2.3.02.002', 'Retencion Paro Forzoso', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1),
(1, '2.3.02.003', 'Retenciones Ahorro Habitacional', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1),
(1, '2.3.02.004', 'Retenciones Ince', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1),
(1, '2.3.02.005', 'APORTE PATRONAL FAOV POR PAGAR', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1),
(1, '2.3.02.006', 'APORTE PATRONAL SSO POR PAGAR', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1),
(1, '2.3.02.007', 'Impuesto sobre Actividad Economica por pagar', 'PASIVO', 'ACREEDORA', 4, '2.3.02', 1),
(1, '2.3.03', 'Pasivo por Impuesto Corriente', 'PASIVO', 'ACREEDORA', 3, '2.3', 0),
(1, '2.3.03.001', 'IVA Debito fiscal', 'PASIVO', 'ACREEDORA', 4, '2.3.03', 1),
(1, '2.3.03.002', 'Retencion de iva', 'PASIVO', 'ACREEDORA', 4, '2.3.03', 1),
(1, '2.3.03.003', 'Retenciones de I.S.L.R', 'PASIVO', 'ACREEDORA', 4, '2.3.03', 1),
(1, '2.3.03.004', 'Impuesto Sobre la Renta', 'PASIVO', 'ACREEDORA', 4, '2.3.03', 1),
(1, '2.4', 'Gastos acumulados', 'PASIVO', 'ACREEDORA', 2, '2', 0),
(1, '2.4.01', 'Gastos acumulados por pagar', 'PASIVO', 'ACREEDORA', 3, '2.4', 0),
(1, '2.4.01.001', 'Ley Politica Habitacional', 'PASIVO', 'ACREEDORA', 4, '2.4.01', 1),
(1, '2.4.01.002', 'Seguro Social Obligatorio', 'PASIVO', 'ACREEDORA', 4, '2.4.01', 1),
(1, '2.4.01.003', 'Sueldos Directivos por Pagar', 'PASIVO', 'ACREEDORA', 4, '2.4.01', 1),
(1, '2.4.01.004', 'Sueldos por Pagar', 'PASIVO', 'ACREEDORA', 4, '2.4.01', 1),
(1, '2.5', 'Pasivos a largo plazo', 'PASIVO', 'ACREEDORA', 2, '2', 0),
(1, '2.5.01', 'Prestaciones Sociales', 'PASIVO', 'ACREEDORA', 3, '2.5', 0),
(1, '2.5.01.001', 'Anticipo de prestaciones Sociales', 'PASIVO', 'ACREEDORA', 4, '2.5.01', 1),
(1, '2.5.02', 'Intereses sobre prestaciones sociales', 'PASIVO', 'ACREEDORA', 3, '2.5', 0),
(1, '2.5.02.001', 'Intereses sobre prestaciones sociales', 'PASIVO', 'ACREEDORA', 4, '2.5.02', 1),
(1, '2.5.03', 'Proviciones intereses bancarios', 'PASIVO', 'ACREEDORA', 3, '2.5', 0),
(1, '2.5.03.001', 'Provicion Intereses', 'PASIVO', 'ACREEDORA', 4, '2.5.03', 1),

-- GRUPO 3: CAPITAL
(1, '3', 'CAPITAL', 'CAPITAL', 'ACREEDORA', 1, NULL, 0),
(1, '3.1', 'CAPITAL SOCIAL', 'CAPITAL', 'ACREEDORA', 2, '3', 0),
(1, '3.1.01', 'CAPITAL SOCIAL', 'CAPITAL', 'ACREEDORA', 3, '3.1', 0),
(1, '3.1.01.001', 'CAPITAL SOCIAL', 'CAPITAL', 'ACREEDORA', 4, '3.1.01', 1),
(1, '3.2', 'RECONVERSION MONETARIA', 'CAPITAL', 'ACREEDORA', 2, '3', 0),
(1, '3.2.01', 'Reservado', 'CAPITAL', 'ACREEDORA', 3, '3.2', 0),
(1, '3.2.01.001', 'Reserva Legal', 'CAPITAL', 'ACREEDORA', 4, '3.2.01', 1),
(1, '3.2.01.002', 'Reserva Estatutaria', 'CAPITAL', 'ACREEDORA', 4, '3.2.01', 1),
(1, '3.2.02', 'RESULTADO EJERCICIOS ANTERIORES', 'CAPITAL', 'ACREEDORA', 3, '3.2', 0),
(1, '3.2.02.001', 'RESULTADO EJERCICIOS ANTERIORES', 'CAPITAL', 'ACREEDORA', 4, '3.2.02', 1),
(1, '3.2.02.002', 'RESULTADO DEL EJERCICIO', 'CAPITAL', 'ACREEDORA', 4, '3.2.02', 1),
(1, '3.2.02.003', 'RECONVERSION MONETARIA', 'CAPITAL', 'ACREEDORA', 4, '3.2.02', 1),

-- GRUPO 4: INGRESOS
(1, '4', 'INGRESOS', 'INGRESOS', 'ACREEDORA', 1, NULL, 0),
(1, '4.1', 'Ingresos', 'INGRESOS', 'ACREEDORA', 2, '4', 0),
(1, '4.1.01', 'Ingresos', 'INGRESOS', 'ACREEDORA', 3, '4.1', 0),
(1, '4.1.01.001', 'Ventas', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1),
(1, '4.1.01.002', 'Ingresos por Administración de Alquiler', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1),
(1, '4.1.01.003', 'Ingresos por Administración de Condominio', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1),
(1, '4.1.01.004', 'Ingresos por Controladores de Inmuebles', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1),
(1, '4.1.01.005', 'Ingresos por Mantenimiento de Inmuebles', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1),
(1, '4.1.01.006', 'Ingresos por Honorarios Profesionales', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1),
(1, '4.1.01.007', 'Ingresos por Cuenta de Terceros', 'INGRESOS', 'ACREEDORA', 4, '4.1.01', 1),

-- GRUPO 5: COSTO DE VENTAS
(1, '5', 'COSTO DE VENTAS', 'COSTO', 'DEUDORA', 1, NULL, 0),
(1, '5.1', 'Costos', 'COSTO', 'DEUDORA', 2, '5', 0),
(1, '5.1.01', 'Costo de Servicios', 'COSTO', 'DEUDORA', 3, '5.1', 0),
(1, '5.1.01.001', 'Vigilancia', 'COSTO', 'DEUDORA', 4, '5.1.01', 1),
(1, '5.1.01.002', 'Limpieza de Inmuebles', 'COSTO', 'DEUDORA', 4, '5.1.01', 1),
(1, '5.1.01.003', 'Mantenimiento de Inmuebles', 'COSTO', 'DEUDORA', 4, '5.1.01', 1),
(1, '5.1.01.004', 'Articulos y materiales de Oficina', 'COSTO', 'DEUDORA', 4, '5.1.01', 1),
(1, '5.1.01.005', 'Otros costos de ventas', 'COSTO', 'DEUDORA', 4, '5.1.01', 1),

-- GRUPO 6: GASTOS
(1, '6', 'GASTOS', 'GASTOS', 'DEUDORA', 1, NULL, 0),
(1, '6.1', 'Egresos', 'GASTOS', 'DEUDORA', 2, '6', 0),
(1, '6.1.01', 'Gastos personal', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.01.001', 'Sueldos Empleados', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.002', 'Salarios Obreros', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.003', 'Sueldos Directores', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.004', 'Comisiones', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.005', 'Bonificaciones', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.006', 'Domingos y Feriados', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.007', 'Horas Extras', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.008', 'Comision Socio', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.009', 'Beneficio de Alimentacion', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.010', 'Bonificacion de Empleados', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.011', 'Vacaciones', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.012', 'Bono Vacacional', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.013', 'Utilidades', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.014', 'Prestaciones Sociales', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.015', 'Intereses Sobre Prestaciones Sociales', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.016', 'Seguro Social obligatorio', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.017', 'Aporte Ince', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.018', 'Ley Politica Habitacional', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.019', 'RETENCION PARO FORZOSO', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.01.020', 'APORTE NO ENTERADOS FAOV', 'GASTOS', 'DEUDORA', 4, '6.1.01', 1),
(1, '6.1.02', 'Suministros', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.02.001', 'Articulos y materiales de Oficina', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1),
(1, '6.1.02.002', 'Artículos de Limpieza', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1),
(1, '6.1.02.003', 'Suministros de Seguridad', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1),
(1, '6.1.02.004', 'Suministros y Articulos de Computacion', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1),
(1, '6.1.02.005', 'Uniforme', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1),
(1, '6.1.02.006', 'Agua Potable', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1),
(1, '6.1.02.007', 'Regalos y Obsequios', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1),
(1, '6.1.02.008', 'Otros Suministros', 'GASTOS', 'DEUDORA', 4, '6.1.02', 1),
(1, '6.1.03', 'Servicios', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.03.001', 'Asesoria Financiera', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.002', 'Gastos Corporativos', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.003', 'Correos y Encomiendas', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.004', 'Reparacion y Mtto Vehiculos', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.005', 'Fletes y Acarreos', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.006', 'Honorarios Profesionales', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.007', 'Gastos legales', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.008', 'Transporte', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.009', 'Servicios de Mensajeria', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.010', 'Servicio de Limpieza Local', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.011', 'Publicidad y Propaganda', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.012', 'Gastos de Instalacion', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.013', 'Mantenimiento y Equipo de Computacion', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.03.014', 'Gastos Camara de Comercio', 'GASTOS', 'DEUDORA', 4, '6.1.03', 1),
(1, '6.1.04', 'Gastos Oficina', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.04.001', 'Gastos Oficina', 'GASTOS', 'DEUDORA', 4, '6.1.04', 1),
(1, '6.1.04.002', 'Mejoras Propiedad', 'GASTOS', 'DEUDORA', 4, '6.1.04', 1),
(1, '6.1.04.003', 'Alquiler Oficina', 'GASTOS', 'DEUDORA', 4, '6.1.04', 1),
(1, '6.1.04.004', 'Gasto Condominio', 'GASTOS', 'DEUDORA', 4, '6.1.04', 1),
(1, '6.1.04.005', 'Servicio Técnico', 'GASTOS', 'DEUDORA', 4, '6.1.04', 1),
(1, '6.1.05', 'Gastos Varios', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.05.001', 'Alimentos y Bebidas', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1),
(1, '6.1.05.002', 'Gastos Medicos', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1),
(1, '6.1.05.003', 'Trabajos Eventuales', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1),
(1, '6.1.05.004', 'Alquileres Equipos', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1),
(1, '6.1.05.005', 'Equipos Oficina', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1),
(1, '6.1.05.006', 'Gastos de Sistemas Legales', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1),
(1, '6.1.05.007', 'Gastos Varios', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1),
(1, '6.1.05.008', 'Afiliaciones', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1),
(1, '6.1.05.009', 'Personal Contratado', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1),
(1, '6.1.05.010', 'Patrocinio', 'GASTOS', 'DEUDORA', 4, '6.1.05', 1),
(1, '6.1.06', 'Servicios Publicos', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.06.001', 'Energia Electrica', 'GASTOS', 'DEUDORA', 4, '6.1.06', 1),
(1, '6.1.06.002', 'Aseo urbano', 'GASTOS', 'DEUDORA', 4, '6.1.06', 1),
(1, '6.1.06.003', 'Saneamiento Ambiental', 'GASTOS', 'DEUDORA', 4, '6.1.06', 1),
(1, '6.1.06.004', 'Servicio de Internet', 'GASTOS', 'DEUDORA', 4, '6.1.06', 1),
(1, '6.1.06.005', 'Telefonía', 'GASTOS', 'DEUDORA', 4, '6.1.06', 1),
(1, '6.1.07', 'Impuestos', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.07.001', 'Patente de Industria y Comercio (IAE)', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1),
(1, '6.1.07.002', 'Gasto de Impuesto Sobre la Renta', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1),
(1, '6.1.07.003', 'Otros Impuestos', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1),
(1, '6.1.07.004', 'Publicidad', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1),
(1, '6.1.07.005', 'Tasas y contribuciones', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1),
(1, '6.1.07.006', 'Prorrateo IVA', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1),
(1, '6.1.07.007', 'Gasto de IGTF', 'GASTOS', 'DEUDORA', 4, '6.1.07', 1),
(1, '6.1.08', 'Otros gastos', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.08.001', 'Iva no Deducible', 'GASTOS', 'DEUDORA', 4, '6.1.08', 1),
(1, '6.1.08.002', 'Donaciones', 'GASTOS', 'DEUDORA', 4, '6.1.08', 1),
(1, '6.1.08.003', 'Gastos de Viaje', 'GASTOS', 'DEUDORA', 4, '6.1.08', 1),
(1, '6.1.09', 'Comisiones e intereses', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.09.001', 'Comisiones Bancarias', 'GASTOS', 'DEUDORA', 4, '6.1.09', 1),
(1, '6.1.09.002', 'Intereses Pagare', 'GASTOS', 'DEUDORA', 4, '6.1.09', 1),
(1, '6.1.09.003', 'Intereses a Terceros', 'GASTOS', 'DEUDORA', 4, '6.1.09', 1),
(1, '6.1.10', 'Gastos no deducibles', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.10.001', 'Gastos no Deducibles', 'GASTOS', 'DEUDORA', 4, '6.1.10', 1),
(1, '6.1.10.002', 'Intereses por Mora', 'GASTOS', 'DEUDORA', 4, '6.1.10', 1),
(1, '6.1.11', 'Gastos de depreciación / amortización', 'GASTOS', 'DEUDORA', 3, '6.1', 0),
(1, '6.1.11.001', 'Gastos de Depreciacion Maquinaria', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1),
(1, '6.1.11.002', 'Depreciacion Acumulada Instalaciones', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1),
(1, '6.1.11.003', 'Gastos de Depreciacion Mobiliarios', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1),
(1, '6.1.11.004', 'Gastos de Depreciacion Equipo', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1),
(1, '6.1.11.005', 'Gastos de Depreciacion Vehiculos', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1),
(1, '6.1.11.006', 'Amortizacion Sistemas de Computacion', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1),
(1, '6.1.11.007', 'Perdida de Venta Activo', 'GASTOS', 'DEUDORA', 4, '6.1.11', 1);

-- 5. Clonación del mismo Catálogo Base para Empresa 2 (J-50987654-3)
INSERT INTO `plan_cuentas` (`empresa_id`, `codigo`, `nombre`, `tipo`, `naturaleza`, `nivel`, `codigo_padre`, `acepta_movimiento`)
SELECT 2, `codigo`, `nombre`, `tipo`, `naturaleza`, `nivel`, `codigo_padre`, `acepta_movimiento`
FROM `plan_cuentas`
WHERE `empresa_id` = 1;

-- 6. Datos de muestra iniciales para enriquecer el Dashboard en Fase 1
INSERT INTO `trabajadores` (`empresa_id`, `cedula`, `nombres`, `apellidos`, `cargo`, `departamento`, `tipo_trabajador`, `fecha_ingreso`, `salario_mensual`, `activo`) VALUES
(1, 'V-18452310', 'Carlos Eduardo', 'Méndez Rojas', 'Analista Contable Senior', 'Contabilidad', 'EMPLEADO', '2023-02-15', 14500.00, 1),
(1, 'V-24891023', 'Génesis Valentina', 'Paredes Soto', 'Coordinadora de Condominios', 'Operaciones', 'EMPLEADO', '2024-05-01', 12800.00, 1),
(1, 'V-15223981', 'José Gregorio', 'Castillo Pérez', 'Supervisor de Mantenimiento', 'Servicios', 'OBRERO', '2022-09-10', 9600.00, 1),
(2, 'V-19882104', 'Luis Alberto', 'Salazar Figuera', 'Gerente de Ventas Corporativas', 'Comercial', 'DIRECTIVO', '2023-08-01', 22000.00, 1),
(2, 'V-26114789', 'Mariana Alejandra', 'Brito Bolívar', 'Especialista de Facturación', 'Administración', 'EMPLEADO', '2024-11-01', 11500.00, 1);

INSERT INTO `asientos` (`id`, `empresa_id`, `numero_asiento`, `fecha`, `periodo`, `concepto`, `referencia`, `origen`, `total_debe`, `total_haber`, `estado`, `usuario_id`) VALUES
(1, 1, 'ASI-202611-0001', '2026-11-02', '2026-11', 'Apertura y cobro de honorarios de administración de condominios Noviembre 2026', 'REC-00841', 'MANUAL', 145000.00, 145000.00, 'PUBLICADO', 1),
(2, 2, 'ASI-202611-0001', '2026-11-03', '2026-11', 'Venta de equipos de computación según Factura N° 000412', 'FAC-000412', 'VENTAS', 89320.00, 89320.00, 'PUBLICADO', 1);

INSERT INTO `asiento_detalles` (`asiento_id`, `empresa_id`, `cuenta_id`, `descripcion`, `debe`, `haber`) VALUES
(
  1, 1,
  (SELECT `id` FROM `plan_cuentas` WHERE `empresa_id` = 1 AND `codigo` = '1.1.02.001' LIMIT 1),
  'Depósito en Banco Provincial por cobro de administración',
  145000.00, 0.00
),
(
  1, 1,
  (SELECT `id` FROM `plan_cuentas` WHERE `empresa_id` = 1 AND `codigo` = '4.1.01.003' LIMIT 1),
  'Ingresos por Administración de Condominio',
  0.00, 125000.00
),
(
  1, 1,
  (SELECT `id` FROM `plan_cuentas` WHERE `empresa_id` = 1 AND `codigo` = '2.3.03.001' LIMIT 1),
  'IVA Débito Fiscal 16% sobre Factura 00001850',
  0.00, 20000.00
),
(
  2, 2,
  (SELECT `id` FROM `plan_cuentas` WHERE `empresa_id` = 2 AND `codigo` = '1.2.01.002' LIMIT 1),
  'Cuentas por Cobrar Clientes - Factura 00000412',
  89320.00, 0.00
),
(
  2, 2,
  (SELECT `id` FROM `plan_cuentas` WHERE `empresa_id` = 2 AND `codigo` = '4.1.01.001' LIMIT 1),
  'Ventas de equipos tecnológicos',
  0.00, 77000.00
),
(
  2, 2,
  (SELECT `id` FROM `plan_cuentas` WHERE `empresa_id` = 2 AND `codigo` = '2.3.03.001' LIMIT 1),
  'IVA Débito Fiscal 16% sobre Factura 00000412',
  0.00, 12320.00
);

INSERT INTO `libro_ventas` (`empresa_id`, `periodo_fiscal`, `fecha_factura`, `rif_cliente`, `nombre_cliente`, `tipo_contribuyente_cliente`, `tipo_documento`, `numero_factura`, `numero_control`, `ventas_exentas`, `base_imponible_general`, `alicuota_general`, `monto_iva_general`, `total_venta_con_iva`, `asiento_id`, `estado`) VALUES
(1, '2026-11', '2026-11-02', 'J-30881920-1', 'CONDOMINIO RESIDENCIAS TORRE DEL PARQUE', 'Ordinario', 'FACTURA', '00001850', '00-00001850', 0.00, 125000.00, 16.00, 20000.00, 145000.00, 1, 'ACTIVO'),
(2, '2026-11', '2026-11-03', 'J-41200981-4', 'SERVICIOS INDUSTRIALES CARONÍ, C.A.', 'Especial', 'FACTURA', '00000412', '00-00000412', 0.00, 77000.00, 16.00, 12320.00, 89320.00, 2, 'ACTIVO');

INSERT INTO `retenciones_iva` (`id`, `empresa_id`, `numero_comprobante`, `fecha_emision`, `periodo_fiscal`, `rif_proveedor`, `nombre_proveedor`, `porcentaje_retencion`, `monto_iva_causado`, `monto_iva_retenido`, `estado`) VALUES
(1, 1, '20261100000001', '2026-11-05', '2026-11', 'J-31445902-8', 'SUMINISTROS DE OFICINA CARACAS, C.A.', 75.00, 6720.00, 5040.00, 'EMITIDO');

INSERT INTO `libro_compras` (`empresa_id`, `periodo_fiscal`, `fecha_factura`, `rif_proveedor`, `nombre_proveedor`, `tipo_documento`, `numero_factura`, `numero_control`, `compras_exentas`, `base_imponible_general`, `alicuota_general`, `monto_iva_general`, `total_compra_con_iva`, `retencion_iva_id`, `estado`) VALUES
(1, '2026-11', '2026-11-05', 'J-31445902-8', 'SUMINISTROS DE OFICINA CARACAS, C.A.', 'FACTURA', 'A-009214', '00-0084112', 0.00, 42000.00, 16.00, 6720.00, 48720.00, 1, 'ACTIVO'),
(2, '2026-11', '2026-11-06', 'J-00012948-0', 'MAYORISTA DE HARDWARE VENEZUELA, C.A.', 'FACTURA', 'F-112980', '00-0591204', 0.00, 45000.00, 16.00, 7200.00, 52200.00, NULL, 'ACTIVO');

SET FOREIGN_KEY_CHECKS = 1;
