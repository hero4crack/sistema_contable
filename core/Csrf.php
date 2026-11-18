<?php
declare(strict_types=1);

namespace Core;

/**
 * Protección contra Falsificación de Peticiones en Sitios Cruzados (CSRF)
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Obtiene el token CSRF actual o genera uno nuevo criptográficamente seguro
     */
    public static function getToken(): string
    {
        Session::start();
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION[self::SESSION_KEY];
    }

    /**
     * Genera el campo input oculto para formularios HTML
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Valida un token recibido por POST o cabecera HTTP X-CSRF-Token
     */
    public static function validate(?string $providedToken = null): bool
    {
        Session::start();
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';

        if ($sessionToken === '') {
            return false;
        }

        if ($providedToken === null) {
            $providedToken = $_POST['csrf_token']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? '';
        }

        return is_string($providedToken) && hash_equals($sessionToken, $providedToken);
    }
}
