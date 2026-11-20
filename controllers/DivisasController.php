<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\DivisaModel;
use Models\EmpresaModel;
use Throwable;

/**
 * Controlador de Relación en Divisas (Banco / Caja Efectivo USD) y Diferencial Cambiario
 */
final class DivisasController extends Controller
{
    private function requireEmpresaActiva(): array
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
                    return $empresa;
                }
            }

            if ($this->isAjax()) {
                $this->json(['ok' => false, 'message' => 'Debe seleccionar una empresa activa.'], 422);
            }
            $this->redirect('empresa', 'index');
        }

        return $empresa;
    }

    public function index(): void
    {
        $this->requireAuth();
        $empresa   = $this->requireEmpresaActiva();
        $empresaId = (int)$empresa['id'];

        $tipoCaja = strtoupper(trim((string)($_POST['tipo_caja'] ?? $_GET['tipo_caja'] ?? 'BANCO')));
        if (!in_array($tipoCaja, ['BANCO', 'CAJA'], true)) {
            $tipoCaja = 'BANCO';
        }

        $periodo = trim((string)($_POST['periodo'] ?? $_GET['periodo'] ?? ($empresa['periodo_fiscal_activo'] ?? date('Y-m'))));
        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            $periodo = date('Y-m');
        }

        $divisaModel = new DivisaModel();
        $relacion    = $divisaModel->listarRelacionPeriodo($empresaId, $tipoCaja, $periodo);

        $this->render('divisas/index', [
            'tituloPagina'  => 'Relación en Divisas (USD) | ' . APP_NAME,
            'moduloActivo'  => 'divisas',
            'empresaActiva' => $empresa,
            'tipoCaja'      => $tipoCaja,
            'periodo'       => $periodo,
            'relacion'      => $relacion,
        ]);
    }

    public function registrar(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresa   = $this->requireEmpresaActiva();
        $empresaId = (int)$empresa['id'];
        $datos     = $this->getJsonInput();
        if (empty($datos)) {
            $datos = $_POST;
        }

        try {
            $divisaModel = new DivisaModel();
            $id          = $divisaModel->registrarMovimiento($empresaId, $datos);
            $this->json([
                'ok'      => true,
                'id'      => $id,
                'message' => 'Operación en divisas registrada y convertida a tasa BCV.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function ajustarDiferencial(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresa      = $this->requireEmpresaActiva();
        $empresaId    = (int)$empresa['id'];
        $payload      = $this->getJsonInput();
        $tipoCaja     = strtoupper(trim((string)($payload['tipo_caja'] ?? 'BANCO')));
        $periodo      = trim((string)($payload['periodo'] ?? ''));
        $tasaCierre   = (float)($payload['tasa_cierre_bcv'] ?? 0);

        try {
            $divisaModel = new DivisaModel();
            $res         = $divisaModel->generarAjusteDiferencialCierre($empresaId, $tipoCaja, $periodo, $tasaCierre);
            $this->json([
                'ok'      => true,
                'message' => $res['registrado']
                    ? 'Ajuste por Diferencial Cambiario calculado: Bs. ' . number_format((float)$res['diferencial'], 2, ',', '.')
                    : 'El saldo en Bs. ya coincide con la tasa BCV de cierre (diferencial = 0,00).',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function eliminar(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresa   = $this->requireEmpresaActiva();
        $empresaId = (int)$empresa['id'];
        $payload   = $this->getJsonInput();
        $id        = (int)($payload['id'] ?? 0);

        $divisaModel = new DivisaModel();
        $divisaModel->eliminarMovimiento($id, $empresaId);

        $this->json(['ok' => true, 'message' => 'Registro eliminado.']);
    }

    public function imprimir(): void
    {
        $this->requireAuth();
        $empresa   = $this->requireEmpresaActiva();
        $empresaId = (int)$empresa['id'];

        $tipoCaja = strtoupper(trim((string)($_GET['tipo_caja'] ?? $_POST['tipo_caja'] ?? 'BANCO')));
        if (!in_array($tipoCaja, ['BANCO', 'CAJA'], true)) {
            $tipoCaja = 'BANCO';
        }
        $periodo = trim((string)($_GET['periodo'] ?? $_POST['periodo'] ?? ($empresa['periodo_fiscal_activo'] ?? date('Y-m'))));

        $divisaModel = new DivisaModel();
        $relacion    = $divisaModel->listarRelacionPeriodo($empresaId, $tipoCaja, $periodo);

        $this->render('divisas/imprimir', [
            'empresa'       => $empresa,
            'empresaActiva' => $empresa,
            'tipoCaja'      => $tipoCaja,
            'periodo'       => $periodo,
            'relacion'      => $relacion,
        ], false);
    }
}
