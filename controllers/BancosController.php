<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\BancoModel;
use Models\EmpresaModel;
use Models\PlanCuentasModel;
use Throwable;

/**
 * Controlador del Módulo de Enlace Bancario y Conciliación Bancaria
 */
final class BancosController extends Controller
{
    private function requireEmpresaActiva(): int
    {
        $empresaId    = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresa      = $empresaId > 0 ? $empresaModel->obtenerPorId($empresaId) : null;

        if (!$empresa || (int)$empresa['activo'] !== 1) {
            $activas = $empresaModel->listarActivas();
            if (!empty($activas)) {
                $empresa = $empresaModel->obtenerPorId((int)$activas[0]['id']);
                if ($empresa) {
                    Session::setEmpresaActiva($empresa);
                    return (int)$empresa['id'];
                }
            }

            if ($this->isAjax()) {
                $this->json(['ok' => false, 'message' => 'Debe seleccionar una empresa cliente activa.'], 422);
            }
            $this->redirect('empresa', 'index');
        }

        return (int)$empresa['id'];
    }
    public function index(): void
    {
        $this->requireAuth();
        $empresaId = $this->requireEmpresaActiva();

        $bancoModel = new BancoModel();
        $planModel  = new PlanCuentasModel();

        $cuentas          = $bancoModel->listarCuentas($empresaId);
        $cuentasContables = $planModel->listarImputablesPorEmpresa($empresaId);

        $cuentaSelId = (int)($_POST['cuenta_id'] ?? $_GET['cuenta_id'] ?? 0);
        if ($cuentaSelId <= 0 && !empty($cuentas)) {
            $cuentaSelId = (int)$cuentas[0]['id'];
        }

        $cuentaSeleccionada = $cuentaSelId > 0 ? $bancoModel->obtenerCuentaPorId($cuentaSelId, $empresaId) : null;
        $periodoFiltro      = trim((string)($_POST['periodo'] ?? $_GET['periodo'] ?? ''));

        if ($periodoFiltro !== '' && !preg_match('/^\d{4}-\d{2}$/', $periodoFiltro)) {
            $periodoFiltro = '';
        }

        $movimientos = $cuentaSeleccionada
            ? $bancoModel->listarMovimientos($empresaId, (int)$cuentaSeleccionada['id'], $periodoFiltro !== '' ? $periodoFiltro : null)
            : [];

        $this->render('bancos/index', [
            'tituloPagina'       => 'Enlace Bancario | ' . APP_NAME,
            'moduloActivo'       => 'bancos_index',
            'bancosCatalogo'     => BancoModel::BANCOS_VENEZOLANOS,
            'tiposCuenta'        => BancoModel::TIPOS_CUENTA,
            'tiposMovimiento'    => BancoModel::TIPOS_MOVIMIENTO,
            'cuentas'            => $cuentas,
            'cuentaSeleccionada' => $cuentaSeleccionada,
            'movimientos'        => $movimientos,
            'periodoFiltro'      => $periodoFiltro,
            'cuentasContables'   => $cuentasContables,
        ]);
    }

    public function obtenerCuenta(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = $this->requireEmpresaActiva();
        $payload   = $this->getJsonInput();
        $id        = (int)($payload['id'] ?? $_POST['id'] ?? 0);

        $bancoModel = new BancoModel();
        $cuenta     = $bancoModel->obtenerCuentaPorId($id, $empresaId);

        if (!$cuenta) {
            $this->json(['ok' => false, 'message' => 'Cuenta bancaria no encontrada.'], 404);
        }

        $this->json(['ok' => true, 'cuenta' => $cuenta]);
    }

    public function guardarCuenta(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = $this->requireEmpresaActiva();
        $datos     = $this->getJsonInput();
        if (empty($datos)) {
            $datos = $_POST;
        }

        try {
            $bancoModel = new BancoModel();
            $id = $bancoModel->guardarCuenta($empresaId, $datos);
            $this->json([
                'ok'      => true,
                'id'      => $id,
                'message' => !empty($datos['id']) && (int)$datos['id'] > 0
                    ? 'Cuenta bancaria actualizada correctamente.'
                    : 'Cuenta bancaria registrada y enlazada correctamente.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cambiarEstatusCuenta(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = $this->requireEmpresaActiva();
        $payload   = $this->getJsonInput();
        $id        = (int)($payload['id'] ?? 0);
        $estatus   = (string)($payload['estatus'] ?? 'Activa');

        try {
            $bancoModel = new BancoModel();
            $bancoModel->cambiarEstatusCuenta($id, $empresaId, $estatus);
            $this->json([
                'ok'      => true,
                'message' => 'Estatus de cuenta bancaria actualizado a ' . $estatus . '.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function registrarMovimiento(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = $this->requireEmpresaActiva();
        $datos     = $this->getJsonInput();
        if (empty($datos)) {
            $datos = $_POST;
        }

        try {
            $bancoModel = new BancoModel();
            $id = $bancoModel->registrarMovimiento($empresaId, $datos);
            $this->json([
                'ok'      => true,
                'id'      => $id,
                'message' => 'Movimiento bancario registrado y saldo actualizado.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function previsualizarCsv(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = $this->requireEmpresaActiva();
        $cuentaId  = (int)($_POST['cuenta_bancaria_id'] ?? 0);

        $bancoModel = new BancoModel();
        $cuenta     = $bancoModel->obtenerCuentaPorId($cuentaId, $empresaId);
        if (!$cuenta) {
            $this->json(['ok' => false, 'message' => 'Debe seleccionar una cuenta bancaria válida.'], 422);
        }

        if (empty($_FILES['archivo_csv']) || (int)($_FILES['archivo_csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->json(['ok' => false, 'message' => 'Debe adjuntar un archivo .csv válido.'], 422);
        }

        $tmpPath = (string)$_FILES['archivo_csv']['tmp_name'];
        $ext     = strtolower(pathinfo((string)$_FILES['archivo_csv']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv' && $ext !== 'txt') {
            $this->json(['ok' => false, 'message' => 'Solo se admiten archivos CSV (.csv) exportados del banco o Excel.'], 422);
        }

        $contenido = file_get_contents($tmpPath);
        if ($contenido === false || trim($contenido) === '') {
            $this->json(['ok' => false, 'message' => 'El archivo CSV está vacío.'], 422);
        }

        // Eliminar BOM UTF-8 si existe
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido) ?? $contenido;
        $lineas    = preg_split('/\r\n|\r|\n/', trim($contenido)) ?: [];

        if (empty($lineas)) {
            $this->json(['ok' => false, 'message' => 'No se detectaron filas en el archivo CSV.'], 422);
        }

        // Detectar delimitador (; o ,)
        $primeraLinea = $lineas[0];
        $delimitador  = substr_count($primeraLinea, ';') >= substr_count($primeraLinea, ',') ? ';' : ',';

        $filasPreview = [];
        $errores      = 0;

        foreach ($lineas as $idx => $lineaRaw) {
            if (trim($lineaRaw) === '') {
                continue;
            }
            $cols = str_getcsv($lineaRaw, $delimitador);
            if (count($cols) < 4) {
                if ($idx > 0) {
                    $errores++;
                }
                continue;
            }

            // Saltar cabecera si la primera columna dice "Fecha"
            if ($idx === 0 && preg_match('/fecha|date/i', trim((string)$cols[0]))) {
                continue;
            }

            $fechaIso = $this->normalizarFechaCsv((string)$cols[0]);
            $ref      = trim((string)($cols[1] ?? ''));
            $desc     = trim((string)($cols[2] ?? ''));
            $debe     = $this->normalizarMontoCsv((string)($cols[3] ?? '0'));
            $haber    = isset($cols[4]) ? $this->normalizarMontoCsv((string)$cols[4]) : 0.0;

            if ($fechaIso === null || $ref === '' || ($debe <= 0.0 && $haber <= 0.0)) {
                $errores++;
                continue;
            }

            $esDup = $bancoModel->existeMovimientoDuplicado($cuentaId, $fechaIso, $ref, $debe, $haber);

            $filasPreview[] = [
                'fecha_operacion' => $fechaIso,
                'referencia'      => $ref,
                'descripcion'     => $desc !== '' ? $desc : 'Movimiento bancario CSV',
                'monto_debe'      => $debe,
                'monto_haber'     => $haber,
                'duplicado'       => $esDup,
            ];
        }

        if (empty($filasPreview)) {
            $this->json([
                'ok'      => false,
                'message' => 'No se encontraron movimientos válidos. Verifique el formato: Fecha, Referencia, Descripción, Débito (Entrada), Crédito (Salida).',
            ], 422);
        }

        $this->json([
            'ok'      => true,
            'filas'   => $filasPreview,
            'errores' => $errores,
        ]);
    }

    public function confirmarImportacionCsv(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = $this->requireEmpresaActiva();
        $payload   = $this->getJsonInput();
        $cuentaId  = (int)($payload['cuenta_bancaria_id'] ?? 0);
        $filas     = is_array($payload['filas'] ?? null) ? $payload['filas'] : [];

        try {
            $bancoModel = new BancoModel();
            $res        = $bancoModel->importarMovimientosCsv($empresaId, $cuentaId, $filas);
            $this->json([
                'ok'         => true,
                'insertados' => $res['insertados'],
                'duplicados' => $res['duplicados'],
                'message'    => sprintf(
                    'Importación completada: %d movimientos cargados (%d duplicados omitidos).',
                    $res['insertados'],
                    $res['duplicados']
                ),
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function contabilizarMovimiento(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId       = $this->requireEmpresaActiva();
        $usuarioId       = (int)Session::get('usuario_id', 0);
        $payload         = $this->getJsonInput();
        $movimientoId    = (int)($payload['movimiento_id'] ?? 0);
        $contrapartidaId = (int)($payload['contrapartida_id'] ?? 0);
        $concepto        = trim((string)($payload['concepto'] ?? ''));

        try {
            $bancoModel = new BancoModel();
            $asiento    = $bancoModel->contabilizarMovimiento(
                $empresaId,
                $usuarioId,
                $movimientoId,
                $contrapartidaId,
                $concepto
            );
            $this->json([
                'ok'      => true,
                'asiento' => $asiento,
                'message' => 'Asiento contable ' . $asiento['numero_asiento'] . ' generado y enlazado correctamente.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // =========================================================================
    // MESA DE CONCILIACIÓN BANCARIA
    // =========================================================================

    public function conciliar(): void
    {
        $this->requireAuth();
        $empresaId = $this->requireEmpresaActiva();

        $empresaModel = new EmpresaModel();
        $empresa      = $empresaModel->obtenerPorId($empresaId);
        $bancoModel   = new BancoModel();

        $cuentas = $bancoModel->listarCuentas($empresaId);
        $cuentaId = (int)($_POST['cuenta_id'] ?? $_GET['cuenta_id'] ?? 0);
        if ($cuentaId <= 0 && !empty($cuentas)) {
            $cuentaId = (int)$cuentas[0]['id'];
        }

        $periodo = trim((string)($_POST['periodo'] ?? $_GET['periodo'] ?? ($empresa['periodo_fiscal_activo'] ?? date('Y-m'))));
        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            $periodo = date('Y-m');
        }

        $cuentaSeleccionada = $cuentaId > 0 ? $bancoModel->obtenerCuentaPorId($cuentaId, $empresaId) : null;
        $movimientosBanco   = [];
        $movimientosLibro   = [];
        $saldoBanco         = 0.00;
        $saldoLibros        = 0.00;
        $conciliacionActual = null;

        if ($cuentaSeleccionada) {
            $movimientosBanco   = $bancoModel->listarMovimientos($empresaId, (int)$cuentaSeleccionada['id'], $periodo);
            $saldoBanco         = $bancoModel->calcularSaldoBancoAlCierrePeriodo($empresaId, (int)$cuentaSeleccionada['id'], $periodo);
            $librosData         = $bancoModel->obtenerMovimientosLibros(
                $empresaId,
                !empty($cuentaSeleccionada['cuenta_contable_id']) ? (int)$cuentaSeleccionada['cuenta_contable_id'] : null,
                $periodo
            );
            $movimientosLibro   = $librosData['movimientos'];
            $saldoLibros        = (float)$librosData['saldo_libros'];
            $conciliacionActual = $bancoModel->obtenerConciliacion($empresaId, (int)$cuentaSeleccionada['id'], $periodo);
        }

        $this->render('bancos/conciliar', [
            'tituloPagina'       => 'Conciliación Bancaria | ' . APP_NAME,
            'moduloActivo'       => 'bancos_conciliar',
            'cuentas'            => $cuentas,
            'cuentaSeleccionada' => $cuentaSeleccionada,
            'periodo'            => $periodo,
            'movimientosBanco'   => $movimientosBanco,
            'movimientosLibro'   => $movimientosLibro,
            'saldoBanco'         => $saldoBanco,
            'saldoLibros'        => $saldoLibros,
            'conciliacionActual' => $conciliacionActual,
        ]);
    }

    public function toggleConciliado(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId    = $this->requireEmpresaActiva();
        $payload      = $this->getJsonInput();
        $movimientoId = (int)($payload['movimiento_id'] ?? 0);
        $conciliado   = (int)($payload['conciliado'] ?? 0);

        $bancoModel = new BancoModel();
        $bancoModel->toggleConciliadoMovimiento($empresaId, $movimientoId, $conciliado);

        $this->json(['ok' => true]);
    }

    public function autoConciliar(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = $this->requireEmpresaActiva();
        $payload   = $this->getJsonInput();
        $cuentaId  = (int)($payload['cuenta_bancaria_id'] ?? 0);
        $periodo   = trim((string)($payload['periodo'] ?? ''));

        try {
            $bancoModel   = new BancoModel();
            $conciliados  = $bancoModel->autoConciliar($empresaId, $cuentaId, $periodo);
            $this->json([
                'ok'          => true,
                'conciliados' => $conciliados,
                'message'     => $conciliados > 0
                    ? "Cruce automático exitoso: {$conciliados} partidas marcadas como conciliadas."
                    : 'No se detectaron nuevas coincidencias automáticas pendientes en este período.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function guardarConciliacion(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = $this->requireEmpresaActiva();
        $payload   = $this->getJsonInput();
        if (empty($payload)) {
            $payload = $_POST;
        }

        try {
            $bancoModel = new BancoModel();
            $id         = $bancoModel->guardarConciliacion($empresaId, $payload);
            $estatus    = (string)($payload['estatus'] ?? 'Borrador');
            $this->json([
                'ok'      => true,
                'id'      => $id,
                'message' => $estatus === 'Conciliada'
                    ? 'Conciliación Bancaria cerrada y archivada con Diferencia = 0,00.'
                    : 'Borrador de Conciliación Bancaria guardado correctamente.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function conciliacionPrint(): void
    {
        $this->requireAuth();
        $empresaId = $this->requireEmpresaActiva();

        $empresaModel = new EmpresaModel();
        $empresa      = $empresaModel->obtenerPorId($empresaId);
        $bancoModel   = new BancoModel();

        $cuentaId = (int)($_GET['cuenta_id'] ?? $_POST['cuenta_id'] ?? 0);
        $periodo  = trim((string)($_GET['periodo'] ?? $_POST['periodo'] ?? ($empresa['periodo_fiscal_activo'] ?? date('Y-m'))));

        $cuenta = $bancoModel->obtenerCuentaPorId($cuentaId, $empresaId);
        if (!$cuenta) {
            http_response_code(404);
            exit('Cuenta bancaria no encontrada.');
        }

        $movimientosBanco   = $bancoModel->listarMovimientos($empresaId, $cuentaId, $periodo);
        $saldoBanco         = $bancoModel->calcularSaldoBancoAlCierrePeriodo($empresaId, $cuentaId, $periodo);
        $librosData         = $bancoModel->obtenerMovimientosLibros(
            $empresaId,
            !empty($cuenta['cuenta_contable_id']) ? (int)$cuenta['cuenta_contable_id'] : null,
            $periodo
        );
        $conciliacion = $bancoModel->obtenerConciliacion($empresaId, $cuentaId, $periodo);

        $this->render('bancos/conciliacion_print', [
            'empresa'          => $empresa,
            'empresaActiva'    => $empresa,
            'cuenta'           => $cuenta,
            'periodo'          => $periodo,
            'movimientosBanco' => $movimientosBanco,
            'movimientosLibro' => $librosData['movimientos'],
            'saldoBanco'       => $conciliacion ? (float)$conciliacion['saldo_segun_banco'] : $saldoBanco,
            'saldoLibros'      => $conciliacion ? (float)$conciliacion['saldo_segun_libros'] : (float)$librosData['saldo_libros'],
            'conciliacion'     => $conciliacion,
        ], false);
    }

    private function normalizarFechaCsv(string $raw): ?string
    {
        $raw = trim($raw);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $raw : null;
        }
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $raw, $m)) {
            return checkdate((int)$m[2], (int)$m[1], (int)$m[3]) ? "{$m[3]}-{$m[2]}-{$m[1]}" : null;
        }
        return null;
    }

    private function normalizarMontoCsv(string $raw): float
    {
        $limpio = preg_replace('/[^\d,.\-]/', '', trim($raw)) ?? '0';
        if ($limpio === '' || $limpio === '-') {
            return 0.0;
        }
        // Si contiene coma y punto, asumir formato europeo/venezolano (1.234,56) si la coma está al final
        if (str_contains($limpio, ',') && str_contains($limpio, '.')) {
            if (strrpos($limpio, ',') > strrpos($limpio, '.')) {
                $limpio = str_replace('.', '', $limpio);
                $limpio = str_replace(',', '.', $limpio);
            } else {
                $limpio = str_replace(',', '', $limpio);
            }
        } elseif (str_contains($limpio, ',')) {
            $limpio = str_replace(',', '.', $limpio);
        }
        return round(abs((float)$limpio), 2);
    }
}
