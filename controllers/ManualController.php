<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\EmpresaModel;

/**
 * Controlador del Manual de Usuario Interactivo y Centro de Documentación
 * (?c=manual&a=index)
 */
final class ManualController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $empresaId     = Session::getEmpresaActivaId();
        $empresaModel  = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        $this->render('manual/index', [
            'tituloPagina'  => 'Manual de Usuario Oficial | ' . APP_NAME,
            'moduloActivo'  => 'manual',
            'empresaActiva' => $empresaActiva,
            'alertaFlash'   => Session::flash('alerta'),
        ]);
    }
}
