<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;
use RuntimeException;

/**
 * Modelo de Relación en Divisas (Banco / Caja Efectivo USD) y Diferencial Cambiario BCV (VEN-NIF / NIC 21)
 */
final class DivisaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `movimientos_divisas` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `empresa_id` INT UNSIGNED NOT NULL,
              `tipo_caja` ENUM('BANCO', 'CAJA') NOT NULL DEFAULT 'BANCO',
              `cuenta_bancaria_id` INT UNSIGNED NULL DEFAULT NULL,
              `fecha_operacion` DATE NOT NULL,
              `referencia` VARCHAR(60) NULL DEFAULT NULL,
              `descripcion` VARCHAR(255) NOT NULL,
              `debe_usd` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
              `haber_usd` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
              `tasa_bcv` DECIMAL(18,4) NOT NULL DEFAULT 1.0000,
              `es_ajuste_cambiario` TINYINT(1) NOT NULL DEFAULT 0,
              `diferencial_realizado` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `diferencial_no_realizado` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
              `periodo` CHAR(7) NOT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_md_emp_tipo_per` (`empresa_id`, `tipo_caja`, `periodo`, `fecha_operacion`),
              CONSTRAINT `fk_md_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function obtenerSaldoAnterior(int $empresaId, string $tipoCaja, string $periodo): array
    {
        $fechaInicio = $periodo . '-01';
        $stmt = $this->db->prepare(
            'SELECT
                COALESCE(SUM(debe_usd - haber_usd), 0) AS saldo_usd,
                COALESCE(SUM((debe_usd * tasa_bcv) - (haber_usd * tasa_bcv) + diferencial_realizado + diferencial_no_realizado), 0) AS saldo_bs
             FROM movimientos_divisas
             WHERE empresa_id = :empresa_id
               AND tipo_caja = :tipo_caja
               AND fecha_operacion < :fecha_inicio'
        );
        $stmt->execute([
            ':empresa_id'   => $empresaId,
            ':tipo_caja'    => $tipoCaja,
            ':fecha_inicio' => $fechaInicio,
        ]);
        $row = $stmt->fetch();

        return [
            'saldo_usd' => round((float)($row['saldo_usd'] ?? 0), 4),
            'saldo_bs'  => round((float)($row['saldo_bs'] ?? 0), 2),
        ];
    }

    public function listarRelacionPeriodo(int $empresaId, string $tipoCaja, string $periodo): array
    {
        if (!in_array($tipoCaja, ['BANCO', 'CAJA'], true)) {
            $tipoCaja = 'BANCO';
        }

        $anterior = $this->obtenerSaldoAnterior($empresaId, $tipoCaja, $periodo);
        $saldoUsd = $anterior['saldo_usd'];
        $saldoBs  = $anterior['saldo_bs'];

        $stmt = $this->db->prepare(
            'SELECT * FROM movimientos_divisas
             WHERE empresa_id = :empresa_id
               AND tipo_caja = :tipo_caja
               AND periodo = :periodo
             ORDER BY fecha_operacion ASC, es_ajuste_cambiario ASC, id ASC'
        );
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':tipo_caja'  => $tipoCaja,
            ':periodo'    => $periodo,
        ]);
        $rows = $stmt->fetchAll();

        $totalDebeUsd   = 0.0;
        $totalHaberUsd  = 0.0;
        $totalDebeBs    = 0.0;
        $totalHaberBs   = 0.0;
        $totalDifReal   = 0.0;
        $totalDifNoReal = 0.0;
        $detalle        = [];

        foreach ($rows as $r) {
            $debeUsd  = (float)$r['debe_usd'];
            $haberUsd = (float)$r['haber_usd'];
            $tasa     = (float)$r['tasa_bcv'];
            $difReal  = (float)$r['diferencial_realizado'];
            $difNoReal= (float)$r['diferencial_no_realizado'];

            if ((int)$r['es_ajuste_cambiario'] === 1) {
                $ajusteBs = round($difReal + $difNoReal, 2);
                $debeBs   = $ajusteBs >= 0 ? $ajusteBs : 0.0;
                $haberBs  = $ajusteBs < 0 ? abs($ajusteBs) : 0.0;
            } else {
                $debeBs  = round($debeUsd * $tasa, 2);
                $haberBs = round($haberUsd * $tasa, 2);
            }

            $saldoUsd = round($saldoUsd + $debeUsd - $haberUsd, 4);
            $saldoBs  = round($saldoBs + $debeBs - $haberBs, 2);

            $totalDebeUsd   += $debeUsd;
            $totalHaberUsd  += $haberUsd;
            $totalDebeBs    += $debeBs;
            $totalHaberBs   += $haberBs;
            $totalDifReal   += $difReal;
            $totalDifNoReal += $difNoReal;

            $detalle[] = [
                'id'                       => (int)$r['id'],
                'fecha_operacion'          => (string)$r['fecha_operacion'],
                'referencia'               => (string)($r['referencia'] ?? ''),
                'descripcion'              => (string)$r['descripcion'],
                'debe_usd'                 => $debeUsd,
                'haber_usd'                => $haberUsd,
                'saldo_usd'                => $saldoUsd,
                'tasa_bcv'                 => $tasa,
                'debe_bs'                  => $debeBs,
                'haber_bs'                 => $haberBs,
                'saldo_bs'                 => $saldoBs,
                'es_ajuste_cambiario'      => (int)$r['es_ajuste_cambiario'],
                'diferencial_realizado'    => $difReal,
                'diferencial_no_realizado' => $difNoReal,
            ];
        }

        return [
            'saldo_anterior' => $anterior,
            'movimientos'    => $detalle,
            'totales'        => [
                'debe_usd'                 => round($totalDebeUsd, 4),
                'haber_usd'                => round($totalHaberUsd, 4),
                'saldo_final_usd'          => $saldoUsd,
                'debe_bs'                  => round($totalDebeBs, 2),
                'haber_bs'                 => round($totalHaberBs, 2),
                'saldo_final_bs'           => $saldoBs,
                'diferencial_realizado'    => round($totalDifReal, 2),
                'diferencial_no_realizado' => round($totalDifNoReal, 2),
            ],
        ];
    }

    public function registrarMovimiento(int $empresaId, array $datos): int
    {
        $tipoCaja  = (string)($datos['tipo_caja'] ?? 'BANCO');
        $fecha     = trim((string)($datos['fecha_operacion'] ?? ''));
        $ref       = trim((string)($datos['referencia'] ?? ''));
        $desc      = trim((string)($datos['descripcion'] ?? ''));
        $debeUsd   = round((float)($datos['debe_usd'] ?? 0), 4);
        $haberUsd  = round((float)($datos['haber_usd'] ?? 0), 4);
        $tasaBcv   = round((float)($datos['tasa_bcv'] ?? 0), 4);
        $cuentaId  = (int)($datos['cuenta_bancaria_id'] ?? 0);

        if (!in_array($tipoCaja, ['BANCO', 'CAJA'], true)) {
            throw new RuntimeException('Tipo de relación en divisas no válido.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new RuntimeException('Fecha de operación inválida (AAAA-MM-DD).');
        }
        if ($desc === '') {
            throw new RuntimeException('La descripción de la operación es obligatoria.');
        }
        if ($tasaBcv <= 0) {
            throw new RuntimeException('La Tasa de Cambio BCV debe ser mayor a cero.');
        }
        if (($debeUsd <= 0 && $haberUsd <= 0) || ($debeUsd > 0 && $haberUsd > 0)) {
            throw new RuntimeException('Indique un monto en Debe (USD) o en Haber (USD), pero no en ambos.');
        }

        $periodo = substr($fecha, 0, 7);

        $stmt = $this->db->prepare(
            'INSERT INTO movimientos_divisas
                (empresa_id, tipo_caja, cuenta_bancaria_id, fecha_operacion, referencia, descripcion,
                 debe_usd, haber_usd, tasa_bcv, es_ajuste_cambiario, diferencial_realizado, diferencial_no_realizado, periodo)
             VALUES
                (:empresa_id, :tipo_caja, :cuenta_id, :fecha, :referencia, :descripcion,
                 :debe_usd, :haber_usd, :tasa_bcv, 0, 0.00, 0.00, :periodo)'
        );
        $stmt->execute([
            ':empresa_id'  => $empresaId,
            ':tipo_caja'   => $tipoCaja,
            ':cuenta_id'   => $cuentaId > 0 ? $cuentaId : null,
            ':fecha'       => $fecha,
            ':referencia'  => $ref !== '' ? $ref : null,
            ':descripcion' => $desc,
            ':debe_usd'    => $debeUsd,
            ':haber_usd'   => $haberUsd,
            ':tasa_bcv'    => $tasaBcv,
            ':periodo'     => $periodo,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Calcula y registra el ajuste por Diferencial Cambiario al cierre del período según tasa BCV
     */
    public function generarAjusteDiferencialCierre(
        int $empresaId,
        string $tipoCaja,
        string $periodo,
        float $tasaCierreBcv
    ): array {
        if ($tasaCierreBcv <= 0) {
            throw new RuntimeException('La Tasa BCV de cierre debe ser mayor a cero.');
        }

        // Eliminar ajuste previo del mismo período para recalcular limpio
        $stmtDel = $this->db->prepare(
            'DELETE FROM movimientos_divisas
             WHERE empresa_id = :empresa_id
               AND tipo_caja = :tipo_caja
               AND periodo = :periodo
               AND es_ajuste_cambiario = 1'
        );
        $stmtDel->execute([
            ':empresa_id' => $empresaId,
            ':tipo_caja'  => $tipoCaja,
            ':periodo'    => $periodo,
        ]);

        $relacion = $this->listarRelacionPeriodo($empresaId, $tipoCaja, $periodo);
        $saldoUsd = (float)$relacion['totales']['saldo_final_usd'];
        $saldoBs  = (float)$relacion['totales']['saldo_final_bs'];

        $valorBsCierre = round($saldoUsd * $tasaCierreBcv, 2);
        $diferencial   = round($valorBsCierre - $saldoBs, 2);

        if (abs($diferencial) < 0.01) {
            return ['diferencial' => 0.00, 'registrado' => false];
        }

        $fechaCierre = date('Y-m-t', strtotime($periodo . '-01'));
        $stmtIns = $this->db->prepare(
            'INSERT INTO movimientos_divisas
                (empresa_id, tipo_caja, cuenta_bancaria_id, fecha_operacion, referencia, descripcion,
                 debe_usd, haber_usd, tasa_bcv, es_ajuste_cambiario, diferencial_realizado, diferencial_no_realizado, periodo)
             VALUES
                (:empresa_id, :tipo_caja, NULL, :fecha, :referencia, :descripcion,
                 0.0000, 0.0000, :tasa_bcv, 1, :dif_real, 0.00, :periodo)'
        );
        $stmtIns->execute([
            ':empresa_id'  => $empresaId,
            ':tipo_caja'   => $tipoCaja,
            ':fecha'       => $fechaCierre,
            ':referencia'  => 'DIF-BCV',
            ':descripcion' => 'Ajuste por Diferencial Cambiario (Tasa BCV Cierre Bs. ' . number_format($tasaCierreBcv, 4, ',', '.') . ')',
            ':tasa_bcv'    => $tasaCierreBcv,
            ':dif_real'    => $diferencial,
            ':periodo'     => $periodo,
        ]);

        return ['diferencial' => $diferencial, 'registrado' => true];
    }

    public function eliminarMovimiento(int $id, int $empresaId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM movimientos_divisas WHERE id = :id AND empresa_id = :empresa_id'
        );
        $stmt->execute([
            ':id'         => $id,
            ':empresa_id' => $empresaId,
        ]);
    }
}
