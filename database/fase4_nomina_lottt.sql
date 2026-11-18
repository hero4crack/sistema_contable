-- =============================================================================
-- SISTEMA INTEGRAL CONTABLE, TRIBUTARIO Y LABORAL (MULTI-EMPRESA)
-- FASE 4: MÓDULO LABORAL LOTTT (TRABAJADORES, NÓMINA, CESTATICKET Y RECIBOS)
-- Motor: MySQL 8.0+ / MariaDB 10.4+ (XAMPP)
-- =============================================================================

USE `juris_contable`;

-- 1. Ampliación de parámetros laborales en `configuracion_fiscal`
ALTER TABLE `configuracion_fiscal`
  ADD COLUMN IF NOT EXISTS `salario_minimo_referencia` DECIMAL(18,2) NOT NULL DEFAULT 130.00 COMMENT 'Salario mínimo legal para tope IVSS/RPE',
  ADD COLUMN IF NOT EXISTS `tope_salarios_minimos_sso` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Tope de 5 salarios mínimos para IVSS',
  ADD COLUMN IF NOT EXISTS `porcentaje_inces_patronal` DECIMAL(5,2) NOT NULL DEFAULT 2.00 COMMENT 'Aporte Patronal INCES (2%)',
  ADD COLUMN IF NOT EXISTS `porcentaje_inces_empleado` DECIMAL(5,2) NOT NULL DEFAULT 0.50 COMMENT 'Retención INCES sobre utilidades (0.5%)',
  ADD COLUMN IF NOT EXISTS `dias_base_calculo` TINYINT UNSIGNED NOT NULL DEFAULT 30 COMMENT '30 días comerciales mes',
  ADD COLUMN IF NOT EXISTS `factor_hora_extra_diurna` DECIMAL(5,2) NOT NULL DEFAULT 1.50 COMMENT 'Recargo 50% hora extra diurna Art. 118 LOTTT',
  ADD COLUMN IF NOT EXISTS `factor_bono_nocturno` DECIMAL(5,2) NOT NULL DEFAULT 0.30 COMMENT 'Recargo 30% bono nocturno Art. 117 LOTTT';

-- 2. Ampliación de ficha de trabajadores en `trabajadores`
ALTER TABLE `trabajadores`
  ADD COLUMN IF NOT EXISTS `telefono` VARCHAR(30) NULL DEFAULT NULL AFTER `apellidos`,
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(120) NULL DEFAULT NULL AFTER `telefono`,
  ADD COLUMN IF NOT EXISTS `fecha_nacimiento` DATE NULL DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `tipo_nomina` ENUM('QUINCENAL', 'MENSUAL', 'SEMANAL') NOT NULL DEFAULT 'QUINCENAL' AFTER `tipo_trabajador`,
  ADD COLUMN IF NOT EXISTS `porcentaje_islr` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje AR-I de Retención ISLR sobre sueldos' AFTER `aplica_cestaticket`,
  ADD COLUMN IF NOT EXISTS `estatus_laboral` ENUM('ACTIVO', 'VACACIONES', 'REPOSO', 'EGRESADO') NOT NULL DEFAULT 'ACTIVO' AFTER `numero_cuenta`;

-- 3. Ampliación de cabecera de lotes de nómina en `nominas`
ALTER TABLE `nominas`
  ADD COLUMN IF NOT EXISTS `descripcion` VARCHAR(200) NULL DEFAULT NULL AFTER `lunes_del_mes`;

-- 4. Ampliación de desglose de recibos en `nomina_detalles` (Formato LOTTT)
ALTER TABLE `nomina_detalles`
  ADD COLUMN IF NOT EXISTS `dias_habiles` DECIMAL(5,2) NOT NULL DEFAULT 11.00 AFTER `dias_trabajados`,
  ADD COLUMN IF NOT EXISTS `dias_descanso` DECIMAL(5,2) NOT NULL DEFAULT 4.00 AFTER `dias_habiles`,
  ADD COLUMN IF NOT EXISTS `dias_feriados` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `dias_descanso`,
  ADD COLUMN IF NOT EXISTS `sueldo_diario` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `dias_feriados`,
  ADD COLUMN IF NOT EXISTS `monto_dias_habiles` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `sueldo_basico_periodo`,
  ADD COLUMN IF NOT EXISTS `monto_dias_descanso` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `monto_dias_habiles`,
  ADD COLUMN IF NOT EXISTS `monto_dias_feriados` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `monto_dias_descanso`,
  ADD COLUMN IF NOT EXISTS `retencion_islr` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `retencion_faov`,
  ADD COLUMN IF NOT EXISTS `aporte_patronal_inces` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `aporte_patronal_faov`;

-- 5. Tabla: `cestaticket_lotes` (Control Mensual de Bono de Alimentación por Empresa)
CREATE TABLE IF NOT EXISTS `cestaticket_lotes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `codigo_lote` VARCHAR(30) NOT NULL,
  `periodo` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
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

-- 6. Tabla: `cestaticket_detalles` (Detalle por Trabajador del Bono de Alimentación)
CREATE TABLE IF NOT EXISTS `cestaticket_detalles` (
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

-- 7. Actualización de datos de contacto y bancarios de trabajadores existentes
UPDATE `trabajadores`
SET `telefono` = '0414-2893104', `email` = 'cmendez@elrosalca.com.ve', `fecha_nacimiento` = '1988-06-14',
    `banco` = 'BANCO PROVINCIAL', `numero_cuenta` = '01080025140100984512', `tipo_nomina` = 'QUINCENAL', `estatus_laboral` = 'ACTIVO'
WHERE `empresa_id` = 1 AND `cedula` = 'V-18452310';

UPDATE `trabajadores`
SET `telefono` = '0412-9014421', `email` = 'gparedes@elrosalca.com.ve', `fecha_nacimiento` = '1995-11-22',
    `banco` = 'BANCAMIGA', `numero_cuenta` = '01720110550004918201', `tipo_nomina` = 'QUINCENAL', `estatus_laboral` = 'ACTIVO'
WHERE `empresa_id` = 1 AND `cedula` = 'V-24891023';

UPDATE `trabajadores`
SET `telefono` = '0424-1558902', `email` = 'jcastillo@elrosalca.com.ve', `fecha_nacimiento` = '1981-03-09',
    `banco` = 'BANCO DE VENEZUELA', `numero_cuenta` = '01020145880000192834', `tipo_nomina` = 'QUINCENAL', `estatus_laboral` = 'ACTIVO'
WHERE `empresa_id` = 1 AND `cedula` = 'V-15223981';

UPDATE `trabajadores`
SET `telefono` = '0414-8861209', `email` = 'lsalazar@tecnoorinoco.com.ve', `fecha_nacimiento` = '1985-08-19',
    `banco` = 'BANCO PROVINCIAL', `numero_cuenta` = '01080098120100441290', `tipo_nomina` = 'QUINCENAL', `estatus_laboral` = 'ACTIVO'
WHERE `empresa_id` = 2 AND `cedula` = 'V-19882104';

UPDATE `trabajadores`
SET `telefono` = '0424-9127730', `email` = 'mbrito@tecnoorinoco.com.ve', `fecha_nacimiento` = '1997-02-28',
    `banco` = 'BANCAMIGA', `numero_cuenta` = '01720110990008172635', `tipo_nomina` = 'QUINCENAL', `estatus_laboral` = 'ACTIVO'
WHERE `empresa_id` = 2 AND `cedula` = 'V-26114789';
