<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\EmpresaModel;

/**
 * Controlador del Panel Principal (Dashboard Multi-Empresa)
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $empresaModel = new EmpresaModel();
        $empresaActivaId = Session::getEmpresaActivaId();
        $empresaActiva   = $empresaActivaId > 0 ? $empresaModel->obtenerPorId($empresaActivaId) : null;

        if (!$empresaActiva || (int)$empresaActiva['activo'] !== 1) {
            $activas = $empresaModel->listarActivas();
            if (!empty($activas)) {
                $empresaActiva = $empresaModel->obtenerPorId((int)$activas[0]['id']);
                if ($empresaActiva) {
                    Session::setEmpresaActiva($empresaActiva);
                }
            }
        }

        $metricas = [];
        if ($empresaActiva) {
            // Asegurar inicialización de tablas y muestras tributarias de Fase 3
            new \Models\TributarioModel();
            $metricas = $empresaModel->obtenerMetricasDashboard(
                (int)$empresaActiva['id'],
                (string)$empresaActiva['periodo_fiscal_activo']
            );
        }

        $this->render('dashboard/index', [
            'tituloPagina'  => 'Dashboard Ejecutivo | ' . APP_NAME,
            'moduloActivo'  => 'dashboard',
            'empresaActiva' => $empresaActiva,
            'metricas'      => $metricas,
            'alertaFlash'   => Session::flash('alerta'),
        ]);
    }
}
