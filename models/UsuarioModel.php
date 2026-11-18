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
    private static bool $esquemaVerificado = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->asegurarEsquema();
    }

    /**
     * Garantiza idempotentemente la existencia de la columna permisos_json en usuarios
     */
    private function asegurarEsquema(): void
    {
        if (self::$esquemaVerificado) {
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
                ':tabla'   => 'usuarios',
                ':columna' => 'permisos_json',
            ]);
            if ((int)$stmt->fetchColumn() === 0) {
                $this->db->exec('ALTER TABLE `usuarios` ADD COLUMN `permisos_json` TEXT NULL DEFAULT NULL AFTER `rol`');
            }
            self::$esquemaVerificado = true;
        } catch (\Throwable) {
            self::$esquemaVerificado = true;
        }
    }

    /**
     * Devuelve el mapa de permisos por defecto según el rol
     */
    public static function permisosPorDefecto(string $rol): array
    {
        if ($rol === 'admin') {
            return [
                'empresas'     => true,
                'contabilidad' => true,
                'tributario'   => true,
                'nomina'       => true,
                'anular'       => true,
                'usuarios'     => true,
            ];
        }
        if ($rol === 'contador') {
            return [
                'empresas'     => true,
                'contabilidad' => true,
                'tributario'   => true,
                'nomina'       => true,
                'anular'       => true,
                'usuarios'     => false,
            ];
        }
        return [
            'empresas'     => false,
            'contabilidad' => true,
            'tributario'   => true,
            'nomina'       => false,
            'anular'       => false,
            'usuarios'     => false,
        ];
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
     * Obtiene un usuario por su ID con sus permisos decodificados
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = 'SELECT id, nombre, email, rol, permisos_json, activo, ultimo_acceso, created_at
                FROM usuarios
                WHERE id = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['permisos'] = $this->decodificarPermisos((string)($row['rol'] ?? 'contador'), $row['permisos_json'] ?? null);
        return $row;
    }

    /**
     * Decodifica permisos desde JSON o devuelve los del rol por defecto
     */
    private function decodificarPermisos(string $rol, ?string $json): array
    {
        $defaults = self::permisosPorDefecto($rol);
        if (is_string($json) && $json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                foreach ($defaults as $k => $v) {
                    if (array_key_exists($k, $decoded)) {
                        $defaults[$k] = (bool)$decoded[$k];
                    }
                }
            }
        }
        return $defaults;
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
        $sql = 'SELECT id, nombre, email, rol, permisos_json, activo, ultimo_acceso, created_at
                FROM usuarios
                ORDER BY id ASC';

        $rows = $this->db->query($sql)->fetchAll();
        foreach ($rows as &$r) {
            $r['permisos'] = $this->decodificarPermisos((string)($r['rol'] ?? 'contador'), $r['permisos_json'] ?? null);
        }
        unset($r);

        return $rows;
    }

    /**
     * Crea o actualiza un usuario (admin, contador o asistente) con su correo, contraseña y permisos
     */
    public function guardarUsuario(array $datos, int $usuarioActualId): int
    {
        $id       = (int)($datos['id'] ?? 0);
        $nombre   = trim((string)($datos['nombre'] ?? ''));
        $email    = strtolower(trim((string)($datos['email'] ?? '')));
        $rol      = strtolower(trim((string)($datos['rol'] ?? 'contador')));
        $password = (string)($datos['password'] ?? '');
        $activo   = isset($datos['activo']) ? ((int)$datos['activo'] === 1 ? 1 : 0) : 1;

        if ($nombre === '' || mb_strlen($nombre) < 3) {
            throw new \RuntimeException('El nombre completo del usuario es obligatorio (mínimo 3 caracteres).');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Debe ingresar un correo electrónico válido.');
        }

        if (!in_array($rol, ['admin', 'contador', 'asistente'], true)) {
            $rol = 'contador';
        }

        if ($id === 0 && mb_strlen($password) < 6) {
            throw new \RuntimeException('La contraseña para un nuevo usuario debe tener al menos 6 caracteres.');
        }

        if ($password !== '' && mb_strlen($password) < 6) {
            throw new \RuntimeException('Si desea cambiar la contraseña, debe tener al menos 6 caracteres.');
        }

        // Proteger la cuenta admin actualmente en sesión contra auto-bloqueo
        if ($id > 0 && $id === $usuarioActualId) {
            if ($rol !== 'admin') {
                throw new \RuntimeException('No puedes quitarte a ti mismo el rol de Administrador mientras estás en sesión.');
            }
            if ($activo !== 1) {
                throw new \RuntimeException('No puedes inactivar tu propia cuenta mientras estás conectado.');
            }
        }

        // Verificar unicidad del correo
        $stmtDup = $this->db->prepare('SELECT id FROM usuarios WHERE email = :email AND id <> :id LIMIT 1');
        $stmtDup->execute([
            ':email' => $email,
            ':id'    => $id,
        ]);
        if ($stmtDup->fetch()) {
            throw new \RuntimeException("El correo {$email} ya se encuentra registrado por otro usuario.");
        }

        // Construir JSON de permisos
        $permisosInput = is_array($datos['permisos'] ?? null) ? $datos['permisos'] : [];
        $permisosFinal = self::permisosPorDefecto($rol);
        if ($rol === 'admin') {
            foreach ($permisosFinal as $k => $v) {
                $permisosFinal[$k] = true;
            }
        } else {
            foreach (['empresas', 'contabilidad', 'tributario', 'nomina', 'anular'] as $clavePerm) {
                if (array_key_exists($clavePerm, $permisosInput)) {
                    $permisosFinal[$clavePerm] = !empty($permisosInput[$clavePerm]);
                }
            }
            $permisosFinal['usuarios'] = false;
        }
        $permisosJson = json_encode($permisosFinal, JSON_UNESCAPED_UNICODE);

        if ($id > 0) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare(
                    'UPDATE usuarios
                     SET nombre = :nombre,
                         email = :email,
                         password_hash = :hash,
                         rol = :rol,
                         permisos_json = :permisos,
                         activo = :activo
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':nombre'   => $nombre,
                    ':email'    => $email,
                    ':hash'     => $hash,
                    ':rol'      => $rol,
                    ':permisos' => $permisosJson,
                    ':activo'   => $activo,
                    ':id'       => $id,
                ]);
            } else {
                $stmt = $this->db->prepare(
                    'UPDATE usuarios
                     SET nombre = :nombre,
                         email = :email,
                         rol = :rol,
                         permisos_json = :permisos,
                         activo = :activo
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':nombre'   => $nombre,
                    ':email'    => $email,
                    ':rol'      => $rol,
                    ':permisos' => $permisosJson,
                    ':activo'   => $activo,
                    ':id'       => $id,
                ]);
            }
            return $id;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash, rol, permisos_json, activo)
             VALUES (:nombre, :email, :hash, :rol, :permisos, :activo)'
        );
        $stmt->execute([
            ':nombre'   => $nombre,
            ':email'    => $email,
            ':hash'     => $hash,
            ':rol'      => $rol,
            ':permisos' => $permisosJson,
            ':activo'   => $activo,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Activa o inactiva un usuario evitando dejar el sistema sin administradores activos
     */
    public function cambiarEstado(int $id, int $nuevoEstado, int $usuarioActualId): void
    {
        if ($id === $usuarioActualId && $nuevoEstado === 0) {
            throw new \RuntimeException('No puedes inactivar tu propio usuario mientras tienes la sesión iniciada.');
        }

        $usuario = $this->obtenerPorId($id);
        if (!$usuario) {
            throw new \RuntimeException('El usuario seleccionado no existe.');
        }

        if ($nuevoEstado === 0 && $usuario['rol'] === 'admin') {
            $adminsActivos = (int)$this->db->query('SELECT COUNT(*) FROM usuarios WHERE rol = "admin" AND activo = 1')->fetchColumn();
            if ($adminsActivos <= 1) {
                throw new \RuntimeException('Debe existir al menos un usuario Administrador activo en el sistema.');
            }
        }

        $stmt = $this->db->prepare('UPDATE usuarios SET activo = :activo WHERE id = :id');
        $stmt->execute([
            ':activo' => $nuevoEstado === 1 ? 1 : 0,
            ':id'     => $id,
        ]);
    }
}
