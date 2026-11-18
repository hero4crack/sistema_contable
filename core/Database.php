<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Clase Singleton de Conexión a Base de Datos con Soporte Transaccional
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Obtiene la instancia única de conexión PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require ROOT_PATH . '/config/database.php';

            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                // Si la base de datos aún no existe (Código 1049), intentamos aprovisionarla desde juris_contable.sql
                if ((int)$e->getCode() === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                    self::autoProvisionDatabase($config);
                    self::$instance = new PDO(
                        $dsn,
                        $config['username'],
                        $config['password'],
                        $config['options']
                    );
                } else {
                    throw new RuntimeException(
                        'Error crítico de conexión a la Base de Datos MySQL/MariaDB: ' . $e->getMessage(),
                        (int)$e->getCode(),
                        $e
                    );
                }
            }
        }

        return self::$instance;
    }

    /**
     * Aprovisiona automáticamente el esquema y seed inicial si la base de datos no existe aún en XAMPP
     */
    private static function autoProvisionDatabase(array $config): void
    {
        $sqlFile = ROOT_PATH . '/database/juris_contable.sql';
        if (!is_file($sqlFile)) {
            throw new RuntimeException('No se encontró el archivo database/juris_contable.sql para inicializar la BD.');
        }

        $dsnServer = sprintf(
            '%s:host=%s;port=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['charset']
        );

        $pdoServer = new PDO($dsnServer, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $sqlContent = file_get_contents($sqlFile);
        if ($sqlContent === false) {
            throw new RuntimeException('No se pudo leer el archivo database/juris_contable.sql.');
        }

        $pdoServer->exec($sqlContent);
    }

    /**
     * Inicia una transacción segura
     */
    public static function beginTransaction(): bool
    {
        $pdo = self::getInstance();
        if (!$pdo->inTransaction()) {
            return $pdo->beginTransaction();
        }
        return true;
    }

    /**
     * Confirma la transacción activa
     */
    public static function commit(): bool
    {
        $pdo = self::getInstance();
        if ($pdo->inTransaction()) {
            return $pdo->commit();
        }
        return false;
    }

    /**
     * Revierte la transacción activa en caso de error
     */
    public static function rollBack(): bool
    {
        $pdo = self::getInstance();
        if ($pdo->inTransaction()) {
            return $pdo->rollBack();
        }
        return false;
    }
}
