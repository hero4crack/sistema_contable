<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\EmpresaModel;
use Models\PlanCuentasModel;
use Throwable;

/**
 * Controlador del Módulo Plan de Cuentas (Catálogo Contable Jerárquico por Empresa)
 */
final class PlanCuentasController extends Controller
{
    /**
     * Vista principal jerárquica del Plan de Cuentas (?c=plan_cuentas&a=index)
     */
    public function index(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $tipoFiltro = isset($_GET['tipo']) ? trim((string)$_GET['tipo']) : '';
        $busqueda   = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        $planModel = new PlanCuentasModel();
        $cuentas   = $planModel->listarPorEmpresa($empresaId, $tipoFiltro ?: null, $busqueda ?: null);
        $stats     = $planModel->contarEstadisticasPorEmpresa($empresaId);
        $rubros    = $planModel->resumenPorRubro($empresaId);

        $this->render('plan_cuentas/index', [
            'tituloPagina'  => 'Plan de Cuentas Contable | ' . APP_NAME,
            'moduloActivo'  => 'plan_cuentas',
            'empresaActiva' => $empresaActiva,
            'cuentas'       => $cuentas,
            'stats'         => $stats,
            'rubros'        => $rubros,
            'filtroTipo'    => $tipoFiltro,
            'filtroBuscar'  => $busqueda,
            'alertaFlash'   => Session::flash('alerta'),
        ]);
    }

    /**
     * Obtiene una cuenta por su ID dentro de la empresa activa (GET JSON)
     */
    public function obtener(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0 || $empresaId <= 0) {
            $this->json(['ok' => false, 'message' => 'Parámetros de cuenta inválidos.'], 400);
        }

        $planModel = new PlanCuentasModel();
        $cuenta = $planModel->obtenerPorIdYEmpresa($id, $empresaId);

        if (!$cuenta) {
            $this->json(['ok' => false, 'message' => 'La cuenta solicitada no existe en la empresa activa.'], 404);
        }

        $this->json([
            'ok'     => true,
            'cuenta' => $cuenta,
        ]);
    }

    /**
     * Sugiere el próximo subcódigo disponible para crear una subcuenta bajo un código padre (GET JSON)
     */
    public function sugerirSubcodigo(): void
    {
        $this->requireAuth();

        $empresaId   = Session::getEmpresaActivaId();
        $codigoPadre = trim((string)($_GET['codigo_padre'] ?? ''));

        if ($codigoPadre === '' || $empresaId <= 0) {
            $this->json(['ok' => false, 'message' => 'Código padre no especificado.'], 400);
        }

        $planModel = new PlanCuentasModel();
        $padre = $planModel->obtenerPorCodigoYEmpresa($codigoPadre, $empresaId);

        if (!$padre) {
            $this->json(['ok' => false, 'message' => 'La cuenta padre indicada no existe.'], 404);
        }

        $sugerido = $planModel->sugerirSiguienteSubcodigo($empresaId, $codigoPadre);

        $this->json([
            'ok'              => true,
            'codigo_sugerido' => $sugerido,
            'padre'           => $padre,
        ]);
    }

    /**
     * Crea una nueva cuenta/subcuenta o actualiza una existente (POST JSON)
     */
    public function guardar(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        if ($empresaId <= 0) {
            $this->json(['ok' => false, 'message' => 'No hay empresa activa en sesión.'], 400);
        }

        $input = $this->getJsonInput();

        $id               = (int)($input['id'] ?? 0);
        $codigo           = trim((string)($input['codigo'] ?? ''), " \t\n\r\0\x0B.");
        $nombre           = trim((string)($input['nombre'] ?? ''));
        $tipo             = strtoupper(trim((string)($input['tipo'] ?? 'ACTIVO')));
        $naturaleza       = strtoupper(trim((string)($input['naturaleza'] ?? 'DEUDORA')));
        $codigoPadre      = trim((string)($input['codigo_padre'] ?? ''), " \t\n\r\0\x0B.");
        $aceptaMovimiento = (int)($input['acepta_movimiento'] ?? 1) === 1 ? 1 : 0;
        $saldoInicial     = round((float)($input['saldo_inicial'] ?? 0.00), 2);
        $activo           = (int)($input['activo'] ?? 1) === 1 ? 1 : 0;

        if ($id === 0 && !preg_match('/^[1-9](\.[0-9]+)*$/', $codigo)) {
            $this->json([
                'ok'      => false,
                'message' => 'El código contable debe ser numérico separado por puntos (Ej: 1.1.02.006).',
            ], 422);
        }

        if ($nombre === '' || mb_strlen($nombre) < 2) {
            $this->json([
                'ok'      => false,
                'message' => 'El nombre de la cuenta es obligatorio.',
            ], 422);
        }

        if (!in_array($tipo, ['ACTIVO', 'PASIVO', 'CAPITAL', 'INGRESOS', 'COSTO', 'GASTOS'], true)) {
            $this->json(['ok' => false, 'message' => 'Grupo contable inválido.'], 422);
        }

        if (!in_array($naturaleza, ['DEUDORA', 'ACREEDORA'], true)) {
            $this->json(['ok' => false, 'message' => 'Naturaleza de cuenta inválida.'], 422);
        }

        $planModel = new PlanCuentasModel();

        try {
            if ($id > 0) {
                $planModel->actualizarCuenta($id, $empresaId, [
                    'nombre'            => $nombre,
                    'tipo'              => $tipo,
                    'naturaleza'        => $naturaleza,
                    'acepta_movimiento' => $aceptaMovimiento,
                    'saldo_inicial'     => $saldoInicial,
                    'activo'            => $activo,
                ]);

                $this->json([
                    'ok'      => true,
                    'message' => 'Cuenta contable actualizada correctamente.',
                ]);
            } else {
                if ($planModel->existeCodigo($empresaId, $codigo)) {
                    $this->json([
                        'ok'      => false,
                        'message' => 'Ya existe una cuenta con el código ' . $codigo . ' en esta empresa.',
                    ], 409);
                }

                $nuevoId = $planModel->crearCuenta($empresaId, [
                    'codigo'            => $codigo,
                    'nombre'            => $nombre,
                    'tipo'              => $tipo,
                    'naturaleza'        => $naturaleza,
                    'codigo_padre'      => $codigoPadre,
                    'acepta_movimiento' => $aceptaMovimiento,
                    'saldo_inicial'     => $saldoInicial,
                    'activo'            => $activo,
                ]);

                $this->json([
                    'ok'        => true,
                    'message'   => sprintf('Cuenta "%s — %s" registrada exitosamente.', $codigo, $nombre),
                    'cuenta_id' => $nuevoId,
                ]);
            }
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Elimina una cuenta únicamente si no posee movimientos en asiento_detalles ni subcuentas hijas
     */
    public function eliminar(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $input = $this->getJsonInput();
        $id = (int)($input['id'] ?? 0);

        if ($id <= 0 || $empresaId <= 0) {
            $this->json(['ok' => false, 'message' => 'ID de cuenta inválido.'], 400);
        }

        $planModel = new PlanCuentasModel();

        try {
            $planModel->eliminarCuentaSegura($id, $empresaId);
            $this->json([
                'ok'      => true,
                'message' => 'La cuenta contable fue eliminada exitosamente del catálogo.',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
