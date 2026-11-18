-- =============================================================================
-- MIGRACIÓN FASE 3: MÓDULO TRIBUTARIO SENIAT (COMPRAS, VENTAS, IVA E ISLR)
-- Juris Contable - Normativa Venezolana (SNAT/2015/0049, SNAT/2025/000054, Decreto 1.808)
-- =============================================================================

USE `juris_contable`;

-- 1. Tabla de Catálogo Oficial de Conceptos de Retención de ISLR (Decreto 1.808)
CREATE TABLE IF NOT EXISTS `conceptos_islr` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(10) NOT NULL COMMENT 'Código oficial SENIAT XML/Decreto 1808',
  `nombre` VARCHAR(180) NOT NULL,
  `tipo_persona` ENUM('PNR', 'PNNR', 'PJD', 'PJND') NOT NULL DEFAULT 'PJD',
  `porcentaje_base` DECIMAL(5,2) NOT NULL DEFAULT 100.00 COMMENT '% de la base sobre el que aplica',
  `tarifa_porcentaje` DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT '% de retención ISLR (1%, 2%, 3%, 5%, 34%)',
  `aplica_sustraendo` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 para PNR (Sustraendo = UT * Factor * Tarifa%)',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_conceptos_islr_codigo_persona` (`codigo`, `tipo_persona`)
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
