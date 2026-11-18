<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\EmpresaModel;
use Models\PlanCuentasModel;
use Throwable;

/**
 * Controlador de Gestión Multi-Empresa (Clientes del Escritorio Contable)
 */
final class EmpresaController extends Controller
{
    /**
     * Directorio principal y CRUD de Empresas Clientes
     */
    public function index(): void
    {
        $this->requireAuth();

        $busqueda = isset($_REQUEST['q']) ? trim((string)$_REQUEST['q']) : null;
        $estado   = isset($_REQUEST['estado']) && $_REQUEST['estado'] !== '' ? (string)$_REQUEST['estado'] : null;

        $empresaModel = new EmpresaModel();
        $empresas = $empresaModel->listarTodas($busqueda, $estado);

        $this->render('empresas/index', [
            'tituloPagina' => 'Empresas Clientes (Multi-Tenant) | ' . APP_NAME,
            'moduloActivo' => 'empresas',
            'empresas'     => $empresas,
            'filtroBuscar' => $busqueda ?? '',
            'filtroEstado' => $estado ?? '',
            'alertaFlash'  => Session::flash('alerta'),
        ]);
    }

    /**
     * Obtiene los datos detallados de una empresa en formato JSON para edición (POST)
     */
    public function obtener(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $input = $this->getJsonInput();
        $id    = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'ID de empresa inválido.'], 400);
        }

        $empresaModel = new EmpresaModel();
        $empresa = $empresaModel->obtenerPorId($id);

        if (!$empresa) {
            $this->json(['ok' => false, 'message' => 'La empresa solicitada no existe.'], 404);
        }

        $this->json([
            'ok'      => true,
            'empresa' => $empresa,
        ]);
    }

    /**
     * Crea una empresa nueva (clonando automáticamente el Plan de Cuentas Base) o actualiza una existente
     */
    public function guardar(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $input = $this->getJsonInput();

        $id                     = (int)($input['id'] ?? 0);
        $rif                    = strtoupper(trim((string)($input['rif'] ?? '')));
        $razonSocial            = trim((string)($input['razon_social'] ?? ''));
        $nombreComercial        = trim((string)($input['nombre_comercial'] ?? ''));
        $direccionFiscal        = trim((string)($input['direccion_fiscal'] ?? ''));
        $telefono               = trim((string)($input['telefono'] ?? ''));
        $emailContacto          = trim((string)($input['email_contacto'] ?? ''));
        $representanteLegal     = trim((string)($input['representante_legal'] ?? ''));
        $tipoContribuyente      = (string)($input['tipo_contribuyente'] ?? 'Ordinario');
        $porcentajeRetencionIva = (string)($input['porcentaje_retencion_iva'] ?? '75');
        $periodoFiscalActivo    = trim((string)($input['periodo_fiscal_activo'] ?? date('Y-m')));
        $valorUnidadTributaria  = (float)($input['valor_unidad_tributaria'] ?? 9.00);
        $montoCestaticket       = (float)($input['monto_cestaticket_mensual'] ?? 1800.00);

        // Validaciones normativas SENIAT
        if (!preg_match('/^[VEJGP]-\d{8}-\d$/', $rif)) {
            $this->json([
                'ok'      => false,
                'message' => 'El formato del RIF no es válido. Use el formato SENIAT con guiones (Ej: J-40123456-7).',
            ], 422);
        }

        if ($razonSocial === '' || mb_strlen($razonSocial) < 3) {
            $this->json([
                'ok'      => false,
                'message' => 'La Razón Social es obligatoria y debe contener al menos 3 caracteres.',
            ], 422);
        }

        if ($direccionFiscal === '') {
            $this->json([
                'ok'      => false,
                'message' => 'La Dirección Fiscal es obligatoria según Providencia SENIAT.',
            ], 422);
        }

        if (!in_array($tipoContribuyente, ['Ordinario', 'Especial', 'Formal'], true)) {
            $this->json(['ok' => false, 'message' => 'Tipo de contribuyente inválido.'], 422);
        }

        if (!in_array($porcentajeRetencionIva, ['0', '75', '100'], true)) {
            $this->json(['ok' => false, 'message' => 'Porcentaje de retención de IVA inválido (permitido: 0%, 75%, 100%).'], 422);
        }

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodoFiscalActivo)) {
            $this->json(['ok' => false, 'message' => 'El Período Fiscal Activo debe tener el formato AAAA-MM (Ej: 2026-11).'], 422);
        }

        $empresaModel = new EmpresaModel();
        if ($empresaModel->existeRif($rif, $id > 0 ? $id : null)) {
            $this->json([
                'ok'      => false,
                'message' => 'Ya existe otra empresa registrada con el RIF ' . $rif . '.',
            ], 409);
        }

        $datos = [
            'rif'                       => $rif,
            'razon_social'              => $razonSocial,
            'nombre_comercial'          => $nombreComercial,
            'direccion_fiscal'          => $direccionFiscal,
            'telefono'                  => $telefono,
            'email_contacto'            => $emailContacto,
            'representante_legal'       => $representanteLegal,
            'tipo_contribuyente'        => $tipoContribuyente,
            'porcentaje_retencion_iva'  => $porcentajeRetencionIva,
            'periodo_fiscal_activo'     => $periodoFiscalActivo,
            'valor_unidad_tributaria'   => $valorUnidadTributaria,
            'monto_cestaticket_mensual' => $montoCestaticket,
        ];

        try {
            if ($id > 0) {
                $empresaModel->actualizar($id, $datos);
                $empresaActualizada = $empresaModel->obtenerPorId($id);

                // Si la empresa editada es la empresa activa en sesión, refrescar sus variables de sesión
                if ($empresaActualizada && Session::getEmpresaActivaId() === $id) {
                    Session::setEmpresaActiva($empresaActualizada);
                }

                $this->json([
                    'ok'      => true,
                    'message' => 'Datos fiscales de "' . $razonSocial . '" actualizados correctamente.',
                    'empresa' => $empresaActualizada,
                ]);
            } else {
                $resultado = $empresaModel->crearConPlanCuentas($datos);
                $nuevaEmpresa = $empresaModel->obtenerPorId((int)$resultado['empresa_id']);

                // Activar automáticamente en sesión la nueva empresa creada
                if ($nuevaEmpresa) {
                    Session::setEmpresaActiva($nuevaEmpresa);
                }

                $this->json([
                    'ok'      => true,
                    'message' => sprintf(
                        'Empresa "%s" (%s) creada exitosamente. Se clonaron %d cuentas del Plan de Cuentas Base.',
                        $razonSocial,
                        $rif,
                        (int)$resultado['cuentas_clonadas']
                    ),
                    'empresa' => $nuevaEmpresa,
                ]);
            }
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => 'Error al guardar la empresa en la base de datos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activa o inactiva una empresa cliente (POST)
     */
    public function cambiarEstado(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $input = $this->getJsonInput();
        $id          = (int)($input['id'] ?? 0);
        $nuevoEstado = (int)($input['activo'] ?? 0) === 1 ? 1 : 0;

        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'ID de empresa inválido.'], 400);
        }

        $empresaModel = new EmpresaModel();
        $empresa = $empresaModel->obtenerPorId($id);
        if (!$empresa) {
            $this->json(['ok' => false, 'message' => 'Empresa no encontrada.'], 404);
        }

        // Impedir inactivar si es la única empresa activa del sistema
        if ($nuevoEstado === 0) {
            $activas = $empresaModel->listarActivas();
            if (count($activas) <= 1 && (int)$activas[0]['id'] === $id) {
                $this->json([
                    'ok'      => false,
                    'message' => 'No puede inactivar la única empresa activa del escritorio contable.',
                ], 422);
            }
        }

        $empresaModel->cambiarEstado($id, $nuevoEstado);

        // Si se inactivó la empresa actualmente seleccionada en sesión, cambiar a otra activa
        if ($nuevoEstado === 0 && Session::getEmpresaActivaId() === $id) {
            $activasRestantes = $empresaModel->listarActivas();
            if (!empty($activasRestantes)) {
                Session::setEmpresaActiva($activasRestantes[0]);
            }
        }

        $accionTexto = $nuevoEstado === 1 ? 'activada' : 'inactivada';
        $this->json([
            'ok'      => true,
            'message' => 'La empresa "' . $empresa['razon_social'] . '" fue ' . $accionTexto . ' correctamente.',
        ]);
    }

    /**
     * Cambia dinámicamente la empresa activa en sesión ($_SESSION['empresa_activa_id'])
     * desde el selector superior del Header o desde la tabla de empresas.
     */
    public function cambiarContexto(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $input = $this->getJsonInput();
        $empresaId = (int)($input['empresa_id'] ?? 0);

        if ($empresaId <= 0) {
            $this->json(['ok' => false, 'message' => 'Debe seleccionar una empresa válida.'], 400);
        }

        $empresaModel = new EmpresaModel();
        $empresa = $empresaModel->obtenerPorId($empresaId);

        if (!$empresa || (int)$empresa['activo'] !== 1) {
            $this->json([
                'ok'      => false,
                'message' => 'La empresa seleccionada no existe o se encuentra inactiva.',
            ], 404);
        }

        Session::setEmpresaActiva($empresa);

        $this->json([
            'ok'      => true,
            'message' => 'Contexto cambiado a: ' . $empresa['razon_social'] . ' (' . $empresa['rif'] . ')',
            'empresa' => [
                'id'                      => (int)$empresa['id'],
                'rif'                     => $empresa['rif'],
                'razon_social'            => $empresa['razon_social'],
                'tipo_contribuyente'      => $empresa['tipo_contribuyente'],
                'porcentaje_retencion_iva'=> $empresa['porcentaje_retencion_iva'],
                'periodo_fiscal_activo'   => $empresa['periodo_fiscal_activo'],
            ],
        ]);
    }

    /**
     * Consulta en JSON el Plan de Cuentas clonado de una empresa específica (POST)
     */
    public function verPlanCuentas(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $input     = $this->getJsonInput();
        $empresaId = (int)($input['empresa_id'] ?? Session::getEmpresaActivaId());
        $tipo      = isset($input['tipo']) ? trim((string)$input['tipo']) : null;
        $busqueda  = isset($input['q']) ? trim((string)$input['q']) : null;

        if ($empresaId <= 0) {
            $this->json(['ok' => false, 'message' => 'Empresa no especificada.'], 400);
        }

        $empresaModel = new EmpresaModel();
        $empresa = $empresaModel->obtenerPorId($empresaId);
        if (!$empresa) {
            $this->json(['ok' => false, 'message' => 'Empresa no encontrada.'], 404);
        }

        $planModel = new PlanCuentasModel();
        $cuentas = $planModel->listarPorEmpresa($empresaId, $tipo, $busqueda);
        $stats   = $planModel->contarEstadisticasPorEmpresa($empresaId);

        $this->json([
            'ok'      => true,
            'empresa' => [
                'id'           => (int)$empresa['id'],
                'rif'          => $empresa['rif'],
                'razon_social' => $empresa['razon_social'],
            ],
            'stats'   => $stats,
            'cuentas' => $cuentas,
        ]);
    }

    /**
     * Guarda la Tasa Oficial del Dólar BCV (Bs./USD) para la empresa activa
     * y opcionalmente actualiza el Bono de Alimentación indexado ($40 BCV)
     */
    public function actualizarTasaBcv(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $body      = $this->getJsonBody();
        $datos     = !empty($body) ? $body : $_POST;

        $tasaBcv               = round((float)($datos['tasa_bcv_usd'] ?? 0.0), 4);
        $recalcularCestaticket = !empty($datos['recalcular_cestaticket']);
        $montoUsdCestaticket   = max(1.0, round((float)($datos['monto_usd_cestaticket'] ?? 40.0), 2));

        if ($tasaBcv <= 0) {
            $this->json([
                'ok'      => false,
                'message' => 'La Tasa Oficial BCV debe ser mayor a Bs. 0,00.',
            ], 422);
        }

        try {
            $empresaModel = new EmpresaModel();
            $res = $empresaModel->actualizarTasaBcv($empresaId, $tasaBcv, $recalcularCestaticket, $montoUsdCestaticket);
            $empresaActualizada = $empresaModel->obtenerPorId($empresaId);
            if ($empresaActualizada) {
                Session::setEmpresaActiva($empresaActualizada);
            }

            $mensaje = sprintf('Tasa BCV actualizada a Bs. %s / USD.', number_format($tasaBcv, 2, ',', '.'));
            if ($recalcularCestaticket) {
                $mensaje .= sprintf(
                    ' El Cestaticket mensual ($%s BCV) se ajustó automáticamente a %s.',
                    number_format($montoUsdCestaticket, 0, ',', '.'),
                    formato_ves((float)$res['monto_cestaticket_mensual'])
                );
            }

            $this->json([
                'ok'        => true,
                'resultado' => $res,
                'message'   => $mensaje,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => 'Error al actualizar la Tasa BCV: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Consulta en línea la Tasa Oficial del Dólar BCV desde API pública (POST + CSRF)
     */
    public function consultarBcvOnline(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $tasaDetectada = null;
        $fechaVigencia = date('d/m/Y');

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 4,
                'header'  => "User-Agent: JurisContable/4.0\r\nAccept: application/json\r\n",
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $raw = @file_get_contents('https://ve.dolarapi.com/v1/dolares/oficial', false, $ctx);
        if (is_string($raw) && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json) && !empty($json['promedio']) && (float)$json['promedio'] > 0) {
                $tasaDetectada = round((float)$json['promedio'], 4);
                if (!empty($json['fechaActualizacion'])) {
                    $fechaVigencia = date('d/m/Y H:i', strtotime((string)$json['fechaActualizacion']));
                }
            }
        }

        if ($tasaDetectada === null) {
            $this->json([
                'ok'      => false,
                'message' => 'No se pudo conectar con el servicio en línea del BCV en este momento. Puede escribir la tasa manualmente.',
            ]);
        }

        $this->json([
            'ok'             => true,
            'tasa_bcv_usd'   => $tasaDetectada,
            'fecha_vigencia' => $fechaVigencia,
            'message'        => sprintf('Tasa Oficial BCV obtenida en línea: Bs. %s / USD', number_format($tasaDetectada, 4, ',', '.')),
        ]);
    }
}
