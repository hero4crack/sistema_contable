<?php
declare(strict_types=1);

namespace Core;

use Models\EmpresaModel;

/**
 * Controlador Base del Patrón MVC
 */
abstract class Controller
{
    /**
     * Renderiza una vista envuelta en el layout principal (header + sidebar + vista + footer)
     */
    protected function render(string $view, array $data = [], bool $useLayout = true): void
    {
        $viewFile = ROOT_PATH . '/views/' . ltrim($view, '/') . '.php';
        if (!is_file($viewFile)) {
            http_response_code(404);
            exit('Vista no encontrada: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8'));
        }

        if ($useLayout && Session::isAuthenticated()) {
            $empresaModel = new EmpresaModel();
            $empresasActivas = $empresaModel->listarActivas();

            $empresaActivaId = Session::getEmpresaActivaId();
            $empresaActiva = null;

            if ($empresaActivaId > 0) {
                $empresaActiva = $empresaModel->obtenerPorId($empresaActivaId);
            }

            // Si no hay empresa activa en sesión o fue desactivada, seleccionar la primera disponible
            if ((!$empresaActiva || (int)$empresaActiva['activo'] !== 1) && !empty($empresasActivas)) {
                $empresaActiva = $empresasActivas[0];
                Session::setEmpresaActiva($empresaActiva);
            }

            $data['empresasSelector'] = $empresasActivas;
            $data['empresaActiva']    = $empresaActiva;
        }

        extract($data, EXTR_SKIP);

        if ($useLayout) {
            require ROOT_PATH . '/views/layouts/header.php';
            require ROOT_PATH . '/views/layouts/sidebar.php';
            require $viewFile;
            require ROOT_PATH . '/views/layouts/footer.php';
        } else {
            require $viewFile;
        }
    }

    /**
     * Envía una respuesta JSON estandarizada y finaliza la ejecución
     */
    protected function json(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Redirige hacia un controlador y acción del sistema
     */
    protected function redirect(string $controller, string $action = 'index', array $params = []): never
    {
        header('Location: ' . route_url($controller, $action, $params));
        exit;
    }

    /**
     * Exige que exista una sesión autenticada válida
     */
    protected function requireAuth(): void
    {
        if (!Session::isAuthenticated()) {
            if ($this->isAjax()) {
                $this->json([
                    'ok'      => false,
                    'message' => 'Su sesión ha expirado. Por favor inicie sesión nuevamente.',
                ], 401);
            }

            $this->redirect('auth', 'login');
        }
    }

    /**
     * Exige que el usuario autenticado posea uno de los roles permitidos
     */
    protected function requireRole(array $rolesPermitidos): void
    {
        $this->requireAuth();
        $rolActual = (string)Session::get('usuario_rol', '');

        if (!in_array($rolActual, $rolesPermitidos, true)) {
            if ($this->isAjax()) {
                $this->json([
                    'ok'      => false,
                    'message' => 'No posee permisos suficientes (' . $rolActual . ') para ejecutar esta operación.',
                ], 403);
            }

            Session::flash('alerta', [
                'tipo'    => 'error',
                'titulo'  => 'Acceso Restringido',
                'mensaje' => 'Su rol actual no tiene privilegios para realizar esta acción.',
            ]);
            $this->redirect('dashboard', 'index');
        }
    }

    /**
     * Verifica la validez del token CSRF en peticiones POST
     */
    protected function requireCsrf(): void
    {
        $input = $this->getJsonInput();
        $token = $input['csrf_token'] ?? null;

        if (!Csrf::validate($token)) {
            if ($this->isAjax()) {
                $this->json([
                    'ok'      => false,
                    'message' => 'Token de seguridad CSRF inválido o expirado. Recargue la página.',
                ], 419);
            }

            Session::flash('alerta', [
                'tipo'    => 'error',
                'titulo'  => 'Error de Seguridad CSRF',
                'mensaje' => 'La solicitud expiró por seguridad. Intente nuevamente.',
            ]);
            $this->redirect('dashboard', 'index');
        }
    }

    /**
     * Detecta si la petición fue realizada vía Fetch / AJAX
     */
    protected function isAjax(): bool
    {
        $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $accept        = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        $contentType   = strtolower($_SERVER['CONTENT_TYPE'] ?? '');

        return $requestedWith === 'xmlhttprequest'
            || str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json');
    }

    /**
     * Obtiene el cuerpo JSON de una petición Fetch si aplica
     */
    protected function getJsonInput(): array
    {
        static $parsed = null;
        if ($parsed !== null) {
            return $parsed;
        }

        $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            $parsed = is_array($decoded) ? $decoded : [];
            return $parsed;
        }

        $parsed = $_POST;
        return $parsed;
    }

    /**
     * Alias compatible para obtener el cuerpo JSON de una petición Fetch
     */
    protected function getJsonBody(): array
    {
        return $this->getJsonInput();
    }

    /**
     * Exige que el método HTTP sea POST
     */
    protected function requirePost(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'ok'      => false,
                'message' => 'Método HTTP no permitido. Se requiere POST.',
            ], 405);
        }
    }

    /**
     * Alias compatible para validar el token CSRF
     */
    protected function validateCsrf(): void
    {
        $this->requireCsrf();
    }
}
