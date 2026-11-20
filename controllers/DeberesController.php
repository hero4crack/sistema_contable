<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\DeberesFormalesModel;
use Models\EmpresaModel;
use RuntimeException;
use Throwable;

/**
 * Controlador de Control y Alertas de Vencimientos de Deberes Formales
 */
final class DeberesController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $empresaId    = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresa      = $empresaId > 0 ? $empresaModel->obtenerPorId($empresaId) : null;

        if (!$empresa) {
            $this->redirect('empresa', 'index');
        }

        $tab = (string)($_GET['tab'] ?? 'detalle');
        if (!in_array($tab, ['detalle', 'matriz'], true)) {
            $tab = 'detalle';
        }

        $model        = new DeberesFormalesModel();
        $obligaciones = $model->listarPorEmpresa((int)$empresa['id']);
        $alertas      = $model->obtenerAlertasVencimiento((int)$empresa['id']);
        $matrizGlobal = $tab === 'matriz' ? $model->listarMatrizGlobal() : [];

        $this->render('deberes/index', [
            'tituloPagina'     => 'Deberes Formales y Alertas | ' . APP_NAME,
            'moduloActivo'     => 'deberes',
            'empresaActiva'    => $empresa,
            'tab'              => $tab,
            'obligaciones'     => $obligaciones,
            'alertas'          => $alertas,
            'matrizGlobal'     => $matrizGlobal,
            'obligacionesBase' => DeberesFormalesModel::OBLIGACIONES_BASE,
        ]);
    }

    public function imprimir(): void
    {
        $this->requireAuth();
        $empresaId    = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresa      = $empresaId > 0 ? $empresaModel->obtenerPorId($empresaId) : null;

        if (!$empresa) {
            $this->redirect('empresa', 'index');
        }

        $tab = (string)($_GET['tab'] ?? 'matriz');
        if (!in_array($tab, ['detalle', 'matriz'], true)) {
            $tab = 'matriz';
        }

        $model        = new DeberesFormalesModel();
        $obligaciones = $model->listarPorEmpresa((int)$empresa['id']);
        $matrizGlobal = $tab === 'matriz' ? $model->listarMatrizGlobal() : [];

        $this->render('deberes/imprimir', [
            'empresa'          => $empresa,
            'empresaActiva'    => $empresa,
            'tab'              => $tab,
            'obligaciones'     => $obligaciones,
            'matrizGlobal'     => $matrizGlobal,
            'obligacionesBase' => DeberesFormalesModel::OBLIGACIONES_BASE,
        ], false);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $payload   = !empty($_POST) ? $_POST : $this->getJsonInput();

        try {
            $soportePath = $this->procesarSoporteSubido($empresaId);
            if ($soportePath !== null) {
                $payload['soporte_path'] = $soportePath;
            }

            $model = new DeberesFormalesModel();
            $model->guardarObligacion($empresaId, $payload);
            $this->json([
                'ok'      => true,
                'message' => 'Deber formal actualizado correctamente.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function renovar(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId   = Session::getEmpresaActivaId();
        $payload     = $this->getJsonInput();
        $codigo      = (string)($payload['codigo_obligacion'] ?? '');
        $fechaRenov  = trim((string)($payload['fecha_renovacion'] ?? date('Y-m-d')));
        $proximoVenc = trim((string)($payload['fecha_vencimiento'] ?? ''));
        $observacion = trim((string)($payload['observacion'] ?? ''));

        try {
            $model = new DeberesFormalesModel();
            $model->marcarRenovado($empresaId, $codigo, $fechaRenov, $proximoVenc, $observacion);
            $this->json([
                'ok'      => true,
                'message' => 'Obligación marcada como Vigente / Renovada.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function procesarSoporteSubido(int $empresaId): ?string
    {
        if (empty($_FILES['soporte']) || (int)($_FILES['soporte']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ((int)$_FILES['soporte']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error al subir el archivo soporte.');
        }

        $size = (int)($_FILES['soporte']['size'] ?? 0);
        if ($size <= 0 || $size > 4 * 1024 * 1024) {
            throw new RuntimeException('El soporte debe pesar máximo 4 MB.');
        }

        $ext = strtolower(pathinfo((string)$_FILES['soporte']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'webp'], true)) {
            throw new RuntimeException('Formato de soporte no permitido. Use PDF, PNG, JPG o WEBP.');
        }

        $dir = ROOT_PATH . '/public/uploads/deberes';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $nombre = sprintf('emp%d_%s.%s', $empresaId, bin2hex(random_bytes(8)), $ext);
        if (!move_uploaded_file((string)$_FILES['soporte']['tmp_name'], $dir . '/' . $nombre)) {
            throw new RuntimeException('No se pudo guardar el archivo soporte en el servidor.');
        }

        return 'uploads/deberes/' . $nombre;
    }
}
