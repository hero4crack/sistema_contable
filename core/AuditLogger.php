<?php
declare(strict_types=1);

namespace Core;

use PDO;
use Throwable;

/**
 * Registrador Automático de Auditoría / Bitácora del Sistema
 * Captura todas las acciones ejecutadas en el sistema y quién las realizó.
 */
final class AuditLogger
{
    private static bool $initialized = false;
    private static bool $disabled = false;

    private static string $controller = '';
    private static string $action = '';
    private static string $method = 'GET';
    private static string $urlRuta = '';
    private static string $ipAddress = '';
    private static string $userAgent = '';

    private static ?int $preUserId = null;
    private static string $preUserNombre = '';
    private static ?string $preUserEmail = null;
    private static ?string $preUserRol = null;

    private static ?int $preEmpresaId = null;
    private static ?string $preEmpresaNombre = null;

    /** @var array<string, mixed> */
    private static array $payload = [];

    private const SENSITIVE_KEYS = [
        'password',
        'password_hash',
        'password_confirm',
        'password_actual',
        'password_nueva',
        'clave',
        'secret',
        'token',
        'csrf_token',
    ];

    /**
     * Inicializa el contexto de auditoría antes de despachar el controlador
     */
    public static function captureRequestStart(string $controller, string $action): void
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        self::$controller = strtolower(trim($controller));
        self::$action     = trim($action);
        self::$method     = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        self::$urlRuta    = mb_substr((string)($_SERVER['REQUEST_URI'] ?? ''), 0, 500);
        self::$ipAddress  = self::resolveClientIp();
        self::$userAgent  = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        // No registrar la propia visualización/filtrado de la bitácora para evitar ruido recursivo
        if (self::$controller === 'auditoria' && in_array(self::$action, ['index', 'detalle'], true)) {
            self::$disabled = true;
            return;
        }

        // Snapshot previo del usuario y empresa (útil especialmente para logout)
        $uid = (int)Session::get('usuario_id', 0);
        if ($uid > 0) {
            self::$preUserId     = $uid;
            self::$preUserNombre = (string)Session::get('usuario_nombre', 'Usuario');
            self::$preUserEmail  = (string)Session::get('usuario_email', '');
            self::$preUserRol    = (string)Session::get('usuario_rol', '');
        }

        $eid = Session::getEmpresaActivaId();
        if ($eid > 0) {
            self::$preEmpresaId     = $eid;
            self::$preEmpresaNombre = (string)Session::get('empresa_activa_nombre', '');
        }

        self::$payload = self::collectSanitizedPayload();

        register_shutdown_function([self::class, 'finalizeAndLog']);
    }

    /**
     * Se ejecuta al finalizar la petición (incluso tras exit en JSON/redirect)
     */
    public static function finalizeAndLog(): void
    {
        if (self::$disabled) {
            return;
        }

        try {
            // Determinar usuario final (post-acción si hubo login, o pre-acción si hubo logout)
            $postUid = (int)($_SESSION['usuario_id'] ?? 0);
            if ($postUid > 0) {
                $userId     = $postUid;
                $userNombre = (string)($_SESSION['usuario_nombre'] ?? 'Usuario');
                $userEmail  = (string)($_SESSION['usuario_email'] ?? '');
                $userRol    = (string)($_SESSION['usuario_rol'] ?? '');
            } elseif (self::$preUserId !== null) {
                $userId     = self::$preUserId;
                $userNombre = self::$preUserNombre;
                $userEmail  = self::$preUserEmail;
                $userRol    = self::$preUserRol;
            } else {
                $userId     = null;
                $userNombre = 'Invitado / No autenticado';
                $userEmail  = isset(self::$payload['email']) ? (string)self::$payload['email'] : null;
                $userRol    = null;
            }

            // Omitir cargas GET de la pantalla de login sin sesión iniciada
            if ($userId === null && self::$controller === 'auth' && self::$action === 'login' && self::$method === 'GET') {
                return;
            }

            // Determinar empresa activa final
            $postEid = (int)($_SESSION['empresa_activa_id'] ?? 0);
            if ($postEid > 0) {
                $empresaId     = $postEid;
                $empresaNombre = (string)($_SESSION['empresa_activa_nombre'] ?? '');
            } else {
                $empresaId     = self::$preEmpresaId;
                $empresaNombre = self::$preEmpresaNombre;
            }

            $httpCode = (int)(http_response_code() ?: 200);
            [$modulo, $tipoEvento, $descripcion] = self::describeAction(
                self::$controller,
                self::$action,
                self::$method,
                $userId,
                self::$preUserId,
                self::$payload
            );

            $entidadId = self::extractEntityId(self::$payload);
            $datosJson = !empty(self::$payload)
                ? json_encode(self::$payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)
                : null;

            if (is_string($datosJson) && strlen($datosJson) > 65000) {
                $datosJson = mb_substr($datosJson, 0, 65000) . '... [TRUNCADO]';
            }

            $pdo = Database::getInstance();
            self::ensureTable($pdo);

            $stmt = $pdo->prepare(
                'INSERT INTO auditoria_logs
                    (usuario_id, usuario_nombre, usuario_email, usuario_rol,
                     empresa_id, empresa_nombre, modulo, controlador, accion,
                     tipo_evento, metodo_http, descripcion, entidad_id,
                     datos_json, url_ruta, codigo_http, ip_address, user_agent)
                 VALUES
                    (:usuario_id, :usuario_nombre, :usuario_email, :usuario_rol,
                     :empresa_id, :empresa_nombre, :modulo, :controlador, :accion,
                     :tipo_evento, :metodo_http, :descripcion, :entidad_id,
                     :datos_json, :url_ruta, :codigo_http, :ip_address, :user_agent)'
            );

            $stmt->execute([
                ':usuario_id'     => $userId,
                ':usuario_nombre' => mb_substr($userNombre, 0, 120),
                ':usuario_email'  => $userEmail !== null && $userEmail !== '' ? mb_substr($userEmail, 0, 150) : null,
                ':usuario_rol'    => $userRol !== null && $userRol !== '' ? mb_substr($userRol, 0, 30) : null,
                ':empresa_id'     => $empresaId,
                ':empresa_nombre' => $empresaNombre !== null && $empresaNombre !== '' ? mb_substr($empresaNombre, 0, 200) : null,
                ':modulo'         => mb_substr($modulo, 0, 60),
                ':controlador'    => mb_substr(self::$controller, 0, 60),
                ':accion'         => mb_substr(self::$action, 0, 80),
                ':tipo_evento'    => $tipoEvento,
                ':metodo_http'    => self::$method,
                ':descripcion'    => mb_substr($descripcion, 0, 500),
                ':entidad_id'     => $entidadId !== null ? mb_substr($entidadId, 0, 64) : null,
                ':datos_json'     => $datosJson,
                ':url_ruta'       => self::$urlRuta,
                ':codigo_http'    => $httpCode,
                ':ip_address'     => self::$ipAddress !== '' ? self::$ipAddress : null,
                ':user_agent'     => self::$userAgent !== '' ? self::$userAgent : null,
            ]);
        } catch (Throwable $e) {
            error_log('[AuditLogger] Error al registrar auditoría: ' . $e->getMessage());
        }
    }

    /**
     * Garantiza que exista la tabla auditoria_logs
     */
    public static function ensureTable(PDO $pdo): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `auditoria_logs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `usuario_id` INT UNSIGNED NULL DEFAULT NULL,
                `usuario_nombre` VARCHAR(120) NOT NULL DEFAULT 'Sistema / Invitado',
                `usuario_email` VARCHAR(150) NULL DEFAULT NULL,
                `usuario_rol` VARCHAR(30) NULL DEFAULT NULL,
                `empresa_id` INT UNSIGNED NULL DEFAULT NULL,
                `empresa_nombre` VARCHAR(200) NULL DEFAULT NULL,
                `modulo` VARCHAR(60) NOT NULL,
                `controlador` VARCHAR(60) NOT NULL,
                `accion` VARCHAR(80) NOT NULL,
                `tipo_evento` ENUM('AUTENTICACION', 'CREACION', 'ACTUALIZACION', 'ELIMINACION', 'IMPRESION_EXPORTACION', 'CONSULTA', 'SISTEMA') NOT NULL DEFAULT 'CONSULTA',
                `metodo_http` VARCHAR(10) NOT NULL DEFAULT 'GET',
                `descripcion` VARCHAR(500) NOT NULL,
                `entidad_id` VARCHAR(64) NULL DEFAULT NULL,
                `datos_json` LONGTEXT NULL DEFAULT NULL,
                `url_ruta` VARCHAR(500) NULL DEFAULT NULL,
                `codigo_http` SMALLINT UNSIGNED NOT NULL DEFAULT 200,
                `ip_address` VARCHAR(45) NULL DEFAULT NULL,
                `user_agent` VARCHAR(255) NULL DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_auditoria_fecha` (`created_at`),
                KEY `idx_auditoria_usuario` (`usuario_id`, `created_at`),
                KEY `idx_auditoria_empresa` (`empresa_id`, `created_at`),
                KEY `idx_auditoria_modulo` (`modulo`, `tipo_evento`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * Recolecta y sanitiza los datos enviados en GET, POST, JSON o FILES
     * @return array<string, mixed>
     */
    private static function collectSanitizedPayload(): array
    {
        $data = [];

        $get = $_GET;
        unset($get['c'], $get['a']);
        if (!empty($get)) {
            $data['query'] = self::sanitizeArray($get);
        }

        if (!empty($_POST)) {
            $data['post'] = self::sanitizeArray($_POST);
        } else {
            $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input');
                if (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $data['json'] = self::sanitizeArray($decoded);
                    }
                }
            }
        }

        if (!empty($_FILES)) {
            $filesMeta = [];
            foreach ($_FILES as $key => $info) {
                if (isset($info['name'])) {
                    $filesMeta[$key] = [
                        'name' => $info['name'],
                        'size' => $info['size'] ?? 0,
                        'type' => $info['type'] ?? '',
                    ];
                }
            }
            if (!empty($filesMeta)) {
                $data['files'] = $filesMeta;
            }
        }

        return $data;
    }

    /**
     * @param array<mixed> $arr
     * @return array<mixed>
     */
    private static function sanitizeArray(array $arr): array
    {
        $clean = [];
        foreach ($arr as $k => $v) {
            $keyStr = strtolower((string)$k);
            $isSensitive = false;
            foreach (self::SENSITIVE_KEYS as $sKey) {
                if (str_contains($keyStr, $sKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $clean[$k] = '[PROTEGIDO]';
                continue;
            }

            if (is_array($v)) {
                $clean[$k] = self::sanitizeArray($v);
            } elseif (is_string($v) && mb_strlen($v) > 2000) {
                $clean[$k] = mb_substr($v, 0, 2000) . '... [TRUNCADO]';
            } else {
                $clean[$k] = $v;
            }
        }
        return $clean;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function extractEntityId(array $payload): ?string
    {
        $sources = [$payload['json'] ?? [], $payload['post'] ?? [], $payload['query'] ?? []];
        $keys = ['id', 'cuenta_id', 'cuenta_bancaria_id', 'asiento_id', 'documento_id', 'trabajador_id', 'empresa_id', 'usuario_id', 'periodo'];
        foreach ($sources as $src) {
            if (!is_array($src)) {
                continue;
            }
            foreach ($keys as $k) {
                if (isset($src[$k]) && $src[$k] !== '' && !is_array($src[$k])) {
                    return (string)$src[$k];
                }
            }
        }
        return null;
    }

    /**
     * Genera el nombre del módulo, tipo de evento y descripción legible en español
     * @param array<string, mixed> $payload
     * @return array{0: string, 1: string, 2: string}
     */
    private static function describeAction(
        string $controller,
        string $action,
        string $method,
        ?int $postUserId,
        ?int $preUserId,
        array $payload
    ): array {
        $moduleMap = [
            'auth'         => 'Autenticación',
            'dashboard'    => 'Dashboard Fiscal',
            'empresa'      => 'Empresas Clientes',
            'empresas'     => 'Empresas Clientes',
            'plan_cuentas' => 'Plan de Cuentas',
            'plancuentas'  => 'Plan de Cuentas',
            'contabilidad' => 'Contabilidad',
            'bancos'       => 'Bancos y Conciliación',
            'banco'        => 'Bancos y Conciliación',
            'divisas'      => 'Divisas USD',
            'divisa'       => 'Divisas USD',
            'tributario'   => 'Tributario (SENIAT)',
            'deberes'      => 'Deberes Formales',
            'nomina'       => 'Nómina (LOTTT)',
            'manual'       => 'Manual de Usuario',
            'usuarios'     => 'Usuarios y Permisos',
            'usuario'      => 'Usuarios y Permisos',
            'respaldo'     => 'Respaldo BD',
            'respaldos'    => 'Respaldo BD',
            'auditoria'    => 'Auditoría',
        ];
        $modulo = $moduleMap[$controller] ?? ucfirst($controller);

        // Casos especiales de autenticación
        if ($controller === 'auth') {
            if ($action === 'autenticar') {
                if ($postUserId !== null) {
                    return [$modulo, 'AUTENTICACION', 'Inicio de sesión exitoso en el sistema.'];
                }
                $emailIntento = (string)($payload['post']['email'] ?? $payload['json']['email'] ?? '');
                return [$modulo, 'AUTENTICACION', 'Intento fallido de inicio de sesión' . ($emailIntento !== '' ? " ({$emailIntento})" : '') . '.'];
            }
            if ($action === 'logout') {
                return [$modulo, 'AUTENTICACION', 'Cierre de sesión del usuario.'];
            }
        }

        // Mapa detallado de acciones frecuentes
        $exactMap = [
            'dashboard.index'                => ['CONSULTA', 'Consultó el Dashboard Fiscal.'],
            'empresa.index'                  => ['CONSULTA', 'Consultó el directorio de Empresas Clientes.'],
            'empresa.guardar'                => ['ACTUALIZACION', 'Creó o actualizó una Empresa Cliente.'],
            'empresa.cambiar'                => ['SISTEMA', 'Cambió la empresa activa de trabajo.'],
            'empresa.actualizarTasaBcv'      => ['ACTUALIZACION', 'Actualizó la tasa oficial BCV USD.'],
            'plan_cuentas.index'             => ['CONSULTA', 'Consultó el Plan de Cuentas contable.'],
            'plan_cuentas.guardar'           => ['ACTUALIZACION', 'Creó o actualizó una cuenta en el Plan de Cuentas.'],
            'plan_cuentas.eliminar'          => ['ELIMINACION', 'Eliminó una cuenta del Plan de Cuentas.'],
            'contabilidad.diario'            => ['CONSULTA', 'Consultó el Libro Diario y Asientos.'],
            'contabilidad.guardarAsiento'    => ['CREACION', 'Registró o actualizó un Asiento Contable.'],
            'contabilidad.anularAsiento'     => ['ELIMINACION', 'Anuló un Asiento Contable.'],
            'contabilidad.mayor'             => ['CONSULTA', 'Consultó el Libro Mayor Analítico.'],
            'contabilidad.estados'           => ['CONSULTA', 'Consultó los Estados Financieros.'],
            'contabilidad.estadosPrint'      => ['IMPRESION_EXPORTACION', 'Imprimió/exportó reporte de Estados Financieros.'],
            'bancos.index'                   => ['CONSULTA', 'Consultó el módulo de Enlace Bancario.'],
            'bancos.guardarCuenta'           => ['ACTUALIZACION', 'Creó o actualizó una Cuenta Bancaria.'],
            'bancos.guardarMovimiento'       => ['CREACION', 'Registró un movimiento bancario.'],
            'bancos.importarExtracto'        => ['CREACION', 'Importó un extracto bancario.'],
            'bancos.conciliar'               => ['CONSULTA', 'Consultó el módulo de Conciliación Bancaria.'],
            'bancos.toggleConciliado'        => ['ACTUALIZACION', 'Marcó o desmarcó una partida en conciliación bancaria.'],
            'bancos.autoConciliar'           => ['ACTUALIZACION', 'Ejecutó el cruce automático de conciliación bancaria.'],
            'bancos.guardarConciliacion'     => ['ACTUALIZACION', 'Guardó o cerró una Conciliación Bancaria.'],
            'bancos.eliminarConciliacion'    => ['ELIMINACION', 'Eliminó una Conciliación Bancaria (borrador o aprobada).'],
            'bancos.conciliacionPrint'       => ['IMPRESION_EXPORTACION', 'Imprimió el reporte de Conciliación Bancaria.'],
            'divisas.index'                  => ['CONSULTA', 'Consultó la Relación en Divisas USD.'],
            'tributario.libros'              => ['CONSULTA', 'Consultó los Libros Auxiliares de Compras y Ventas.'],
            'tributario.guardarCompra'       => ['CREACION', 'Registró o modificó una factura en el Libro de Compras.'],
            'tributario.guardarVenta'        => ['CREACION', 'Registró o modificó una factura en el Libro de Ventas.'],
            'tributario.librosPrint'         => ['IMPRESION_EXPORTACION', 'Imprimió los Libros de Compras y Ventas (SENIAT).'],
            'tributario.exportarLibro'       => ['IMPRESION_EXPORTACION', 'Exportó el Libro de Compras/Ventas a CSV/Excel.'],
            'tributario.retenciones'         => ['CONSULTA', 'Consultó el módulo de Retenciones IVA / ISLR.'],
            'tributario.emitirRetencion'     => ['CREACION', 'Emitió un comprobante de Retención IVA / ISLR.'],
            'tributario.actualizarRetencion' => ['ACTUALIZACION', 'Modificó un comprobante de Retención IVA / ISLR.'],
            'tributario.reiniciarCorrelativo'=> ['ACTUALIZACION', 'Reinició o ajustó el correlativo de retenciones.'],
            'tributario.comprobantePrint'    => ['IMPRESION_EXPORTACION', 'Imprimió comprobante de Retención IVA / ISLR.'],
            'deberes.index'                  => ['CONSULTA', 'Consultó el Control de Deberes Formales.'],
            'deberes.guardar'                => ['ACTUALIZACION', 'Registró o actualizó un Deber Formal / soporte.'],
            'nomina.trabajadores'            => ['CONSULTA', 'Consultó el Directorio de Trabajadores.'],
            'nomina.guardarTrabajador'       => ['ACTUALIZACION', 'Creó o actualizó la ficha de un Trabajador.'],
            'nomina.index'                   => ['CONSULTA', 'Consultó el módulo de Nómina Quincenal / Mensual.'],
            'nomina.generar'                 => ['CREACION', 'Generó o recalculó la Nómina del período.'],
            'nomina.cestaticket'             => ['CONSULTA', 'Consultó o imprimió el Bono de Alimentación (Cestaticket).'],
            'nomina.recibos'                 => ['IMPRESION_EXPORTACION', 'Consultó o imprimió Recibos de Pago LOTTT.'],
            'usuarios.index'                 => ['CONSULTA', 'Consultó la gestión de Usuarios y Permisos.'],
            'usuarios.guardar'               => ['ACTUALIZACION', 'Creó o actualizó un Usuario del sistema.'],
            'usuarios.cambiarEstado'         => ['ACTUALIZACION', 'Activó o desactivó un Usuario del sistema.'],
            'respaldo.index'                 => ['CONSULTA', 'Consultó el módulo de Respaldo de Base de Datos.'],
            'respaldo.generar'               => ['CREACION', 'Generó un respaldo SQL de la base de datos.'],
            'respaldo.descargar'             => ['IMPRESION_EXPORTACION', 'Descargó un archivo de respaldo SQL.'],
            'respaldo.restaurar'             => ['SISTEMA', 'Restauró la base de datos desde un respaldo SQL.'],
            'respaldo.eliminar'              => ['ELIMINACION', 'Eliminó un archivo de respaldo SQL.'],
            'auditoria.exportarCsv'          => ['IMPRESION_EXPORTACION', 'Exportó la Bitácora de Auditoría a CSV.'],
        ];

        $canonicalController = match ($controller) {
            'empresas'    => 'empresa',
            'plancuentas' => 'plan_cuentas',
            'banco'       => 'bancos',
            'divisa'      => 'divisas',
            'usuario'     => 'usuarios',
            'respaldos'   => 'respaldo',
            default       => $controller,
        };

        $key = $canonicalController . '.' . $action;
        if (isset($exactMap[$key])) {
            return [$modulo, $exactMap[$key][0], $exactMap[$key][1]];
        }

        // Inferencia automática para cualquier otra acción
        $actLower = strtolower($action);
        if (preg_match('/eliminar|borrar|anular|delete|remove/', $actLower)) {
            $tipo = 'ELIMINACION';
            $desc = "Ejecutó eliminación ({$canonicalController} → {$action}).";
        } elseif (preg_match('/print|pdf|export|descargar|csv|excel|comprobante|recibo/', $actLower)) {
            $tipo = 'IMPRESION_EXPORTACION';
            $desc = "Imprimió o exportó documento ({$canonicalController} → {$action}).";
        } elseif (preg_match('/crear|registrar|agregar|generar|emitir|importar|nuevo/', $actLower)) {
            $tipo = 'CREACION';
            $desc = "Registró datos en {$modulo} ({$action}).";
        } elseif ($method !== 'GET' || preg_match('/guardar|actualizar|editar|modificar|toggle|cambiar|aprobar|conciliar|restaurar|reiniciar/', $actLower)) {
            $tipo = 'ACTUALIZACION';
            $desc = "Actualizó información en {$modulo} ({$action}).";
        } else {
            $tipo = 'CONSULTA';
            $desc = "Consultó {$modulo} ({$action}).";
        }

        return [$modulo, $tipo, $desc];
    }

    private static function resolveClientIp(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $header) {
            if (!empty($_SERVER[$header])) {
                $parts = explode(',', (string)$_SERVER[$header]);
                return trim($parts[0]);
            }
        }
        return '';
    }
}
