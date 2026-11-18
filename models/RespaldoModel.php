<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Modelo de Respaldo y Restauración de Base de Datos (Solo Admin)
 */
final class RespaldoModel
{
    private PDO $db;
    private string $backupDir;

    public function __construct()
    {
        $this->db        = Database::getInstance();
        $this->backupDir = ROOT_PATH . '/storage/backups';
        $this->asegurarDirectorioBackups();
    }

    private function asegurarDirectorioBackups(): void
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $htaccess = $this->backupDir . '/.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, "Order allow,deny\nDeny from all\n");
        }
    }

    public function obtenerResumenBaseDatos(): array
    {
        $config = require ROOT_PATH . '/config/database.php';
        $dbName = (string)($config['database'] ?? 'juris_contable');

        $stmt = $this->db->query('SHOW TABLE STATUS');
        $rows = $stmt->fetchAll();

        $tablas       = [];
        $totalFilas   = 0;
        $totalBytes   = 0;

        foreach ($rows as $row) {
            $filas = (int)($row['Rows'] ?? 0);
            $bytes = (int)($row['Data_length'] ?? 0) + (int)($row['Index_length'] ?? 0);
            $totalFilas += $filas;
            $totalBytes += $bytes;

            $tablas[] = [
                'nombre' => (string)($row['Name'] ?? ''),
                'motor'  => (string)($row['Engine'] ?? 'InnoDB'),
                'filas'  => $filas,
                'bytes'  => $bytes,
            ];
        }

        return [
            'database'    => $dbName,
            'tablas'      => $tablas,
            'total_tablas'=> count($tablas),
            'total_filas' => $totalFilas,
            'total_bytes' => $totalBytes,
        ];
    }

    public function generarRespaldoSql(): array
    {
        @set_time_limit(300);

        $config   = require ROOT_PATH . '/config/database.php';
        $dbName   = (string)($config['database'] ?? 'juris_contable');
        $filename = sprintf('respaldo_%s_%s.sql', preg_replace('/[^a-zA-Z0-9_]/', '_', $dbName), date('Y-m-d_H-i-s'));
        $filepath = $this->backupDir . '/' . $filename;

        $fh = fopen($filepath, 'wb');
        if ($fh === false) {
            throw new RuntimeException('No se pudo crear el archivo de respaldo en el servidor.');
        }

        try {
            $header  = "-- =============================================================================\n";
            $header .= "-- JURISDICCIÓN / SISTEMA: Juris Contable - Respaldo Completo MySQL\n";
            $header .= "-- Base de Datos: {$dbName}\n";
            $header .= "-- Fecha de Generación: " . date('d/m/Y H:i:s') . "\n";
            $header .= "-- =============================================================================\n\n";
            $header .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $header .= "SET FOREIGN_KEY_CHECKS = 0;\n";
            $header .= "SET NAMES utf8mb4;\n\n";
            fwrite($fh, $header);

            $tablasStmt = $this->db->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
            $tablas     = $tablasStmt->fetchAll(PDO::FETCH_NUM);

            foreach ($tablas as $tRow) {
                $tabla = (string)$tRow[0];
                $tablaEsc = '`' . str_replace('`', '``', $tabla) . '`';

                fwrite($fh, "-- -----------------------------------------------------------------------------\n");
                fwrite($fh, "-- Estructura de tabla {$tablaEsc}\n");
                fwrite($fh, "-- -----------------------------------------------------------------------------\n");
                fwrite($fh, "DROP TABLE IF EXISTS {$tablaEsc};\n");

                $createStmt = $this->db->query("SHOW CREATE TABLE {$tablaEsc}");
                $createRow  = $createStmt->fetch(PDO::FETCH_NUM);
                if (!empty($createRow[1])) {
                    fwrite($fh, $createRow[1] . ";\n\n");
                }

                $dataStmt = $this->db->query("SELECT * FROM {$tablaEsc}");
                $colCount = $dataStmt->columnCount();
                if ($colCount === 0) {
                    continue;
                }

                $columnas = [];
                for ($i = 0; $i < $colCount; $i++) {
                    $meta       = $dataStmt->getColumnMeta($i);
                    $colName    = (string)($meta['name'] ?? ('col_' . $i));
                    $columnas[] = '`' . str_replace('`', '``', $colName) . '`';
                }
                $colsSql = '(' . implode(', ', $columnas) . ')';

                $batch     = [];
                $batchSize = 100;

                while ($row = $dataStmt->fetch(PDO::FETCH_NUM)) {
                    $vals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = $this->db->quote((string)$val);
                        }
                    }
                    $batch[] = '(' . implode(', ', $vals) . ')';

                    if (count($batch) >= $batchSize) {
                        fwrite($fh, "INSERT INTO {$tablaEsc} {$colsSql} VALUES\n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    fwrite($fh, "INSERT INTO {$tablaEsc} {$colsSql} VALUES\n" . implode(",\n", $batch) . ";\n");
                }

                fwrite($fh, "\n");
            }

            fwrite($fh, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fclose($fh);

            return [
                'archivo' => $filename,
                'ruta'    => $filepath,
                'bytes'   => filesize($filepath) ?: 0,
                'tablas'  => count($tablas),
            ];
        } catch (Throwable $e) {
            if (is_resource($fh)) {
                fclose($fh);
            }
            if (is_file($filepath)) {
                @unlink($filepath);
            }
            throw $e;
        }
    }

    public function listarRespaldos(): array
    {
        $archivos = glob($this->backupDir . '/*.sql') ?: [];
        $lista    = [];

        foreach ($archivos as $path) {
            if (!is_file($path)) {
                continue;
            }
            $lista[] = [
                'archivo'    => basename($path),
                'bytes'      => filesize($path) ?: 0,
                'fecha_unix' => filemtime($path) ?: 0,
                'fecha'      => date('d/m/Y H:i:s', filemtime($path) ?: time()),
            ];
        }

        usort($lista, static fn(array $a, array $b): int => $b['fecha_unix'] <=> $a['fecha_unix']);
        return $lista;
    }

    public function obtenerRutaRespaldoSeguro(string $archivo): string
    {
        $limpio = basename(trim($archivo));
        if (!preg_match('/^[A-Za-z0-9_.\-]+\.sql$/', $limpio)) {
            throw new RuntimeException('Nombre de archivo de respaldo no válido.');
        }

        $ruta = $this->backupDir . '/' . $limpio;
        if (!is_file($ruta)) {
            throw new RuntimeException('El archivo de respaldo solicitado no existe.');
        }

        return $ruta;
    }

    public function eliminarRespaldo(string $archivo): void
    {
        $ruta = $this->obtenerRutaRespaldoSeguro($archivo);
        if (!@unlink($ruta)) {
            throw new RuntimeException('No se pudo eliminar el archivo de respaldo.');
        }
    }

    public function restaurarDesdeArchivo(string $rutaSql): int
    {
        @set_time_limit(300);

        if (!is_file($rutaSql)) {
            throw new RuntimeException('El archivo SQL para restauración no existe.');
        }

        $sql = file_get_contents($rutaSql);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('El archivo SQL está vacío o no pudo leerse.');
        }

        $sentencias = $this->dividirSentenciasSql($sql);
        if (empty($sentencias)) {
            throw new RuntimeException('No se detectaron sentencias SQL válidas en el archivo.');
        }

        $ejecutadas = 0;
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($sentencias as $stmtSql) {
                $this->db->exec($stmtSql);
                $ejecutadas++;
            }
        } finally {
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        return $ejecutadas;
    }

    private function dividirSentenciasSql(string $sql): array
    {
        $sql        = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
        $len        = strlen($sql);
        $sentencias = [];
        $buffer     = '';
        $enComilla  = false;
        $tipoComilla = '';

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];

            // Saltar comentarios -- ... hasta fin de línea
            if (!$enComilla && $ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                $nl = strpos($sql, "\n", $i);
                if ($nl === false) {
                    break;
                }
                $i = $nl;
                continue;
            }

            // Saltar comentarios /* ... */
            if (!$enComilla && $ch === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
                $end = strpos($sql, '*/', $i + 2);
                if ($end === false) {
                    break;
                }
                $i = $end + 1;
                continue;
            }

            if ($enComilla) {
                $buffer .= $ch;
                if ($ch === '\\' && $i + 1 < $len) {
                    $buffer .= $sql[++$i];
                    continue;
                }
                if ($ch === $tipoComilla) {
                    if ($i + 1 < $len && $sql[$i + 1] === $tipoComilla) {
                        $buffer .= $sql[++$i];
                    } else {
                        $enComilla = false;
                    }
                }
                continue;
            }

            if ($ch === '\'' || $ch === '"') {
                $enComilla   = true;
                $tipoComilla = $ch;
                $buffer     .= $ch;
                continue;
            }

            if ($ch === ';') {
                $limpia = trim($buffer);
                if ($limpia !== '') {
                    $sentencias[] = $limpia;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $ch;
        }

        $ultimo = trim($buffer);
        if ($ultimo !== '') {
            $sentencias[] = $ultimo;
        }

        return $sentencias;
    }
}
