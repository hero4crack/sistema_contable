<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;
use RuntimeException;

/**
 * Modelo de Control y Alertas de Vencimientos de Deberes Formales (Multi-Tenant)
 */
final class DeberesFormalesModel
{
    public const OBLIGACIONES_BASE = [
        'SENIAT_ESTIMADA'        => ['nombre' => 'SENIAT Estimada', 'periodicidad' => 'Trimestral-Anual', 'accion' => 'Declarar y pagar anticipo/estimada en Portal Fiscal SENIAT.'],
        'INCES'                  => ['nombre' => 'INCES', 'periodicidad' => 'Trimestral', 'accion' => 'Declarar aporte patronal (2%) y trabajador (0.5%) en portal INCES.'],
        'RNET'                   => ['nombre' => 'RNET', 'periodicidad' => 'Trimestral', 'accion' => 'Actualizar declaración trimestral laboral ante el MPPPST (RNET).'],
        'USO_CONFORME'           => ['nombre' => 'Uso Conforme', 'periodicidad' => 'Posee / No Posee', 'accion' => 'Solicitar o verificar conformidad de uso en Ingeniería Municipal.'],
        'MANT_LICORES'           => ['nombre' => 'Mantenimiento Licencia Licores', 'periodicidad' => 'Anual', 'accion' => 'Pagar tasa anual de mantenimiento de expendio en Hacienda Municipal.'],
        'MANT_ACTIVIDADES'       => ['nombre' => 'Mantenimiento Lic. Actividades Económicas', 'periodicidad' => 'Anual', 'accion' => 'Pagar mantenimiento anual de licencia en Alcaldía / Hacienda.'],
        'LIC_ACTIVIDADES'        => ['nombre' => 'Licencia de Actividades Económicas', 'periodicidad' => 'Trienio', 'accion' => 'Renovar licencia trienal de actividades económicas en Alcaldía.'],
        'LIC_LICORES'            => ['nombre' => 'Licencia de Licores', 'periodicidad' => 'Trienio', 'accion' => 'Renovar licencia trienal de licores en Hacienda Municipal.'],
        'INMUEBLE'               => ['nombre' => 'Declaración de Inmueble', 'periodicidad' => 'Anual', 'accion' => 'Declarar y pagar impuesto sobre inmuebles urbanos (Solvencia anual).'],
        'PUBLICIDAD'             => ['nombre' => 'Declaración Publicidad y Propaganda', 'periodicidad' => 'Anual', 'accion' => 'Declarar avisos/rótulos comerciales en Alcaldía.'],
        'BOMBEROS'               => ['nombre' => 'Bomberos', 'periodicidad' => 'Anual', 'accion' => 'Solicitar inspección y renovar certificado de Bomberos Municipales.'],
        'MANT_AMBIENTAL'         => ['nombre' => 'Mantenimiento Funcionamiento Ambiental', 'periodicidad' => 'Anual', 'accion' => 'Pagar mantenimiento anual ante Ministerio de Ecosocialismo / Instituto Municipal.'],
        'FUNC_AMBIENTAL'         => ['nombre' => 'Funcionamiento Ambiental', 'periodicidad' => 'Trienio', 'accion' => 'Renovar conformidad/permiso de funcionamiento ambiental.'],
        'PERMISO_SANITARIO'      => ['nombre' => 'Permiso Sanitario', 'periodicidad' => 'Anual-Bienio', 'accion' => 'Renovar permiso sanitario ante Contraloría Sanitaria / MPPS.'],
        'CONTRATO_ARRENDAMIENTO' => ['nombre' => 'Contrato de Arrendamiento', 'periodicidad' => 'Fecha Específica', 'accion' => 'Renovar contrato de arrendamiento notariado para respaldar domicilio.'],
        'SUNAGRO'                => ['nombre' => 'SUNAGRO', 'periodicidad' => 'Única', 'accion' => 'Verificar código/inscripción activa en sistema SICA-SUNAGRO.'],
        'RIF'                    => ['nombre' => 'RIF (Vencimiento)', 'periodicidad' => 'Trienio', 'accion' => 'Actualizar y reimprimir certificado RIF en Portal Fiscal SENIAT.'],
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `deberes_formales` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `empresa_id` INT UNSIGNED NOT NULL,
              `codigo_obligacion` VARCHAR(40) NOT NULL,
              `nombre_obligacion` VARCHAR(120) NOT NULL,
              `periodicidad` VARCHAR(40) NOT NULL,
              `estatus` ENUM('VIGENTE', 'POR_VERIFICAR', 'PRONTO_VENCER', 'VENCIDO', 'NO_APLICA', 'SIN_SOPORTE') NOT NULL DEFAULT 'POR_VERIFICAR',
              `fecha_vencimiento` DATE NULL DEFAULT NULL,
              `observacion` VARCHAR(255) NULL DEFAULT NULL,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_df_emp_cod` (`empresa_id`, `codigo_obligacion`),
              KEY `idx_df_vencimiento` (`empresa_id`, `estatus`, `fecha_vencimiento`),
              CONSTRAINT `fk_df_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $colRen = $this->db->query("SHOW COLUMNS FROM `deberes_formales` LIKE 'fecha_renovacion'")->fetch();
        if (!$colRen) {
            $this->db->exec("ALTER TABLE `deberes_formales` ADD COLUMN `fecha_renovacion` DATE NULL DEFAULT NULL AFTER `fecha_vencimiento`");
        }
        $colSop = $this->db->query("SHOW COLUMNS FROM `deberes_formales` LIKE 'soporte_path'")->fetch();
        if (!$colSop) {
            $this->db->exec("ALTER TABLE `deberes_formales` ADD COLUMN `soporte_path` VARCHAR(255) NULL DEFAULT NULL AFTER `observacion`");
        }
    }

    private function inicializarEmpresa(int $empresaId): void
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO deberes_formales (empresa_id, codigo_obligacion, nombre_obligacion, periodicidad, estatus)
             VALUES (:empresa_id, :codigo, :nombre, :periodicidad, 'POR_VERIFICAR')"
        );
        foreach (self::OBLIGACIONES_BASE as $codigo => $info) {
            $stmt->execute([
                ':empresa_id'   => $empresaId,
                ':codigo'       => $codigo,
                ':nombre'       => $info['nombre'],
                ':periodicidad' => $info['periodicidad'],
            ]);
        }
    }

    public function listarPorEmpresa(int $empresaId): array
    {
        $this->inicializarEmpresa($empresaId);

        $stmt = $this->db->prepare(
            'SELECT * FROM deberes_formales WHERE empresa_id = :empresa_id ORDER BY id ASC'
        );
        $stmt->execute([':empresa_id' => $empresaId]);
        $rows = $stmt->fetchAll();

        $hoy = date('Y-m-d');
        $limitePronto = date('Y-m-d', strtotime('+30 days'));

        foreach ($rows as &$row) {
            $cod = (string)$row['codigo_obligacion'];
            $row['accion_requerida'] = self::OBLIGACIONES_BASE[$cod]['accion'] ?? '';
            $row['estatus_efectivo'] = $this->calcularEstatusEfectivo(
                (string)$row['estatus'],
                $row['fecha_vencimiento'] ?? null,
                $hoy,
                $limitePronto
            );
        }
        unset($row);

        return $rows;
    }

    private function calcularEstatusEfectivo(
        string $estatusGuardado,
        ?string $fechaVenc,
        string $hoy,
        string $limitePronto
    ): string {
        if (in_array($estatusGuardado, ['NO_APLICA', 'SIN_SOPORTE'], true)) {
            return $estatusGuardado;
        }
        if ($fechaVenc !== null && $fechaVenc !== '') {
            if ($fechaVenc < $hoy) {
                return 'VENCIDO';
            }
            if ($fechaVenc <= $limitePronto) {
                return 'PRONTO_VENCER';
            }
        }
        return $estatusGuardado;
    }

    public function guardarObligacion(int $empresaId, array $datos): void
    {
        $codigo      = trim((string)($datos['codigo_obligacion'] ?? ''));
        $estatus     = strtoupper(trim((string)($datos['estatus'] ?? 'POR_VERIFICAR')));
        $fechaVenc   = trim((string)($datos['fecha_vencimiento'] ?? ''));
        $fechaRenov  = trim((string)($datos['fecha_renovacion'] ?? ''));
        $observacion = trim((string)($datos['observacion'] ?? ''));
        $soportePath = trim((string)($datos['soporte_path'] ?? ''));

        if (!isset(self::OBLIGACIONES_BASE[$codigo])) {
            throw new RuntimeException('Obligación formal no reconocida.');
        }
        if (!in_array($estatus, ['VIGENTE', 'POR_VERIFICAR', 'PRONTO_VENCER', 'VENCIDO', 'NO_APLICA', 'SIN_SOPORTE'], true)) {
            $estatus = 'POR_VERIFICAR';
        }
        if ($fechaVenc !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVenc)) {
            throw new RuntimeException('La fecha de vencimiento debe tener formato AAAA-MM-DD.');
        }
        if ($fechaRenov !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRenov)) {
            throw new RuntimeException('La fecha de renovación debe tener formato AAAA-MM-DD.');
        }

        $this->inicializarEmpresa($empresaId);

        if ($soportePath !== '') {
            $stmt = $this->db->prepare(
                'UPDATE deberes_formales
                 SET estatus = :estatus,
                     fecha_vencimiento = :fecha_vencimiento,
                     fecha_renovacion = :fecha_renovacion,
                     observacion = :observacion,
                     soporte_path = :soporte_path
                 WHERE empresa_id = :empresa_id AND codigo_obligacion = :codigo'
            );
            $stmt->execute([
                ':estatus'           => $estatus,
                ':fecha_vencimiento' => $fechaVenc !== '' ? $fechaVenc : null,
                ':fecha_renovacion'  => $fechaRenov !== '' ? $fechaRenov : null,
                ':observacion'       => $observacion !== '' ? mb_substr($observacion, 0, 255) : null,
                ':soporte_path'      => $soportePath,
                ':empresa_id'        => $empresaId,
                ':codigo'            => $codigo,
            ]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE deberes_formales
                 SET estatus = :estatus,
                     fecha_vencimiento = :fecha_vencimiento,
                     fecha_renovacion = :fecha_renovacion,
                     observacion = :observacion
                 WHERE empresa_id = :empresa_id AND codigo_obligacion = :codigo'
            );
            $stmt->execute([
                ':estatus'           => $estatus,
                ':fecha_vencimiento' => $fechaVenc !== '' ? $fechaVenc : null,
                ':fecha_renovacion'  => $fechaRenov !== '' ? $fechaRenov : null,
                ':observacion'       => $observacion !== '' ? mb_substr($observacion, 0, 255) : null,
                ':empresa_id'        => $empresaId,
                ':codigo'            => $codigo,
            ]);
        }
    }

    /**
     * Marca una obligación como Vigente / Renovado con fecha de renovación y próximo vencimiento
     */
    public function marcarRenovado(
        int $empresaId,
        string $codigo,
        string $fechaRenovacion,
        string $proximoVencimiento,
        string $observacion = ''
    ): void {
        $this->guardarObligacion($empresaId, [
            'codigo_obligacion' => $codigo,
            'estatus'           => 'VIGENTE',
            'fecha_renovacion'  => $fechaRenovacion,
            'fecha_vencimiento' => $proximoVencimiento,
            'observacion'       => $observacion !== '' ? $observacion : ('Renovado el ' . date('d/m/Y', strtotime($fechaRenovacion))),
        ]);
    }

    /**
     * Matriz global de deberes formales de todas las empresas clientes activas
     */
    public function listarMatrizGlobal(): array
    {
        $empresas = $this->db->query(
            'SELECT id, rif, razon_social FROM empresas WHERE activo = 1 ORDER BY razon_social ASC'
        )->fetchAll();

        $matriz = [];
        foreach ($empresas as $emp) {
            $empId = (int)$emp['id'];
            $lista = $this->listarPorEmpresa($empId);
            $porCodigo = [];
            foreach ($lista as $item) {
                $porCodigo[$item['codigo_obligacion']] = $item;
            }
            $matriz[] = [
                'empresa'      => $emp,
                'obligaciones' => $porCodigo,
            ];
        }

        return $matriz;
    }

    /**
     * Retorna alertas de vencimiento (Vencidas o Próximas a Vencer <= 30 días) para el Dashboard
     */
    public function obtenerAlertasVencimiento(int $empresaId): array
    {
        $lista = $this->listarPorEmpresa($empresaId);
        $alertas = [];

        foreach ($lista as $item) {
            if (in_array($item['estatus_efectivo'], ['VENCIDO', 'PRONTO_VENCER'], true)) {
                $alertas[] = $item;
            }
        }

        usort($alertas, static function (array $a, array $b): int {
            return strcmp((string)($a['fecha_vencimiento'] ?? '9999-12-31'), (string)($b['fecha_vencimiento'] ?? '9999-12-31'));
        });

        return $alertas;
    }
}
