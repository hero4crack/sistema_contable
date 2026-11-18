<?php
declare(strict_types=1);

namespace Core;

/**
 * Gestor de Sesiones y Contexto Multi-Empresa (Tenant Activo)
 */
final class Session
{
    /**
     * Inicia la sesión de forma segura si aún no está activa
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');

            session_name('JURIS_CONTABLE_SESSID');
            session_start();
        }
    }

    /**
     * Regenera el ID de sesión para prevenir Session Fixation al autenticar
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Almacena un valor en sesión
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Obtiene un valor de la sesión
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica si existe una clave en la sesión
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Elimina una clave de la sesión
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     *Verifica si el usuario ha iniciado sesión
     */
    public static function isAuthenticated(): bool
    {
        self::start();
        return !empty($_SESSION['usuario_id']);
    }

    /**
     * Obtiene el ID de la empresa activa en el contexto Multi-Tenant
     */
    public static function getEmpresaActivaId(): int
    {
        self::start();
        return (int)($_SESSION['empresa_activa_id'] ?? 0);
    }

    /**
     * Establece la empresa activa en el contexto global de la sesión
     */
    public static function setEmpresaActiva(array $empresa): void
    {
        self::start();
        $_SESSION['empresa_activa_id']      = (int)$empresa['id'];
        $_SESSION['empresa_activa_rif']     = (string)$empresa['rif'];
        $_SESSION['empresa_activa_nombre']  = (string)$empresa['razon_social'];
        $_SESSION['empresa_activa_tipo']    = (string)$empresa['tipo_contribuyente'];
        $_SESSION['empresa_activa_periodo']  = (string)$empresa['periodo_fiscal_activo'];
        $_SESSION['empresa_activa_ret_iva']  = (string)$empresa['porcentaje_retencion_iva'];
        $_SESSION['empresa_activa_tasa_bcv'] = (float)($empresa['tasa_bcv_usd'] ?? 45.00);
    }

    /**
     * Registra o recupera un mensaje Flash (notificación de un solo uso)
     */
    public static function flash(string $key, ?array $message = null): ?array
    {
        self::start();
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }

        if (isset($_SESSION['_flash'][$key])) {
            $stored = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $stored;
        }

        return null;
    }

    /**
     * Destruye por completo la sesión activa
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool)$params['secure'],
                (bool)$params['httponly']
            );
        }

        session_destroy();
    }
}
