<?php
declare(strict_types=1);

/**
 * Front Controller Principal y Enrutador MVC
 * Juris Contable - Sistema Multi-Empresa (SENIAT / LOTTT)
 */

require_once dirname(__DIR__) . '/config/app.php';

// Autoloader nativo PSR-4 para los namespaces Core, Models y Controllers
spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Core\\'        => ROOT_PATH . '/core/',
        'Models\\'      => ROOT_PATH . '/models/',
        'Controllers\\' => ROOT_PATH . '/controllers/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

use Core\Session;

Session::start();

// Determinar controlador y acción solicitados (?c=controlador&a=accion)
$controllerParam = strtolower(trim((string)($_GET['c'] ?? '')));
$actionParam     = trim((string)($_GET['a'] ?? 'index'));

if ($controllerParam === '') {
    $controllerParam = Session::isAuthenticated() ? 'dashboard' : 'auth';
    if ($controllerParam === 'auth') {
        $actionParam = 'login';
    }
}

// Mapa seguro de controladores habilitados
$controllerMap = [
    'auth'         => \Controllers\AuthController::class,
    'dashboard'    => \Controllers\DashboardController::class,
    'empresa'      => \Controllers\EmpresaController::class,
    'empresas'     => \Controllers\EmpresaController::class,
    'plan_cuentas' => \Controllers\PlanCuentasController::class,
    'plancuentas'  => \Controllers\PlanCuentasController::class,
    'contabilidad' => \Controllers\ContabilidadController::class,
    'bancos'       => \Controllers\BancosController::class,
    'banco'        => \Controllers\BancosController::class,
    'tributario'   => \Controllers\TributarioController::class,
    'nomina'       => \Controllers\NominaController::class,
    'manual'       => \Controllers\ManualController::class,
    'usuarios'     => \Controllers\UsuarioController::class,
    'usuario'      => \Controllers\UsuarioController::class,
];

if (!isset($controllerMap[$controllerParam])) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="es"><body style="background:#0f172a;color:#f8fafc;font-family:sans-serif;padding:3rem;text-align:center;">';
    echo '<h2>404 — Controlador no encontrado</h2>';
    echo '<p><a href="' . e(route_url('dashboard', 'index')) . '" style="color:#10b981;">Volver al Panel Principal</a></p>';
    echo '</body></html>';
    exit;
}

$controllerClass = $controllerMap[$controllerParam];
$controllerInstance = new $controllerClass();

if (!method_exists($controllerInstance, $actionParam)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="es"><body style="background:#0f172a;color:#f8fafc;font-family:sans-serif;padding:3rem;text-align:center;">';
    echo '<h2>404 — Acción no disponible</h2>';
    echo '<p><a href="' . e(route_url('dashboard', 'index')) . '" style="color:#10b981;">Volver al Panel Principal</a></p>';
    echo '</body></html>';
    exit;
}

$controllerInstance->{$actionParam}();
