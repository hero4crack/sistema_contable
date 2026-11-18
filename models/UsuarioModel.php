<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

/**
 * Modelo de Usuarios y Control de Acceso
 */
final class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Busca un usuario por su correo electrónico
     */
    public function obtenerPorEmail(string $email): ?array
    {
        $sql = 'SELECT id, nombre, email, password_hash, rol, activo, ultimo_acceso
                FROM usuarios
                WHERE email = :email
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => trim(strtolower($email))]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Obtiene un usuario por su ID
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = 'SELECT id, nombre, email, rol, activo, ultimo_acceso, created_at
                FROM usuarios
                WHERE id = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Valida las credenciales de acceso con password_verify()
     */
    public function autenticar(string $email, string $password): ?array
    {
        $usuario = $this->obtenerPorEmail($email);

        if (!$usuario || (int)$usuario['activo'] !== 1) {
            return null;
        }

        if (!password_verify($password, (string)$usuario['password_hash'])) {
            return null;
        }

        $this->registrarUltimoAcceso((int)$usuario['id']);
        unset($usuario['password_hash']);

        return $usuario;
    }

    /**
     * Actualiza la marca de tiempo del último acceso exitoso
     */
    public function registrarUltimoAcceso(int $usuarioId): void
    {
        $sql = 'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $usuarioId]);
    }

    /**
     * Lista todos los usuarios registrados en el escritorio contable
     */
    public function listarTodos(): array
    {
        $sql = 'SELECT id, nombre, email, rol, activo, ultimo_acceso, created_at
                FROM usuarios
                ORDER BY id ASC';

        return $this->db->query($sql)->fetchAll();
    }
}
