<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\EmpresaModel;
use Models\NominaModel;
use Throwable;

/**
 * Controlador del Módulo Laboral LOTTT:
 * Trabajadores, Procesamiento de Nómina, Bono de Alimentación (Cestaticket) y Recibos de Pago.
 */
final class NominaController extends Controller
{
    /**
     * Submódulo: Procesamiento de Nómina General (?c=nomina&a=index)
     */
    public function index(): void
    {
        $this->requireAuth();

        $empresaId     = Session::getEmpresaActivaId();
        $empresaModel  = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $nominaModel = new NominaModel();
        $config      = $nominaModel->obtenerConfiguracionLaboral($empresaId);
        $lotes       = $nominaModel->listarNominas($empresaId);

        $periodoDefault = (string)($empresaActiva['periodo_fiscal_activo'] ?? date('Y-m'));
        $precalculo     = $nominaModel->precalcularLoteNomina($empresaId, [
            'periodo'         => $periodoDefault,
            'tipo_frecuencia' => 'PRIMERA_QUINCENA',
            'lunes_del_mes'   => 4,
        ]);

        $loteSeleccionadoId = isset($_GET['lote_id']) ? (int)$_GET['lote_id'] : (int)($lotes[0]['id'] ?? 0);
        $loteSeleccionado   = $loteSeleccionadoId > 0
            ? $nominaModel->obtenerNominaConDetalles($empresaId, $loteSeleccionadoId)
            : null;

        $this->render('nomina/index', [
            'tituloPagina'     => 'Procesamiento de Nómina LOTTT | ' . APP_NAME,
            'moduloActivo'     => 'nomina_index',
            'empresaActiva'    => $empresaActiva,
            'configLaboral'    => $config,
            'lotesNomina'      => $lotes,
            'loteSeleccionado' => $loteSeleccionado,
            'precalculo'       => $precalculo,
            'alertaFlash'      => Session::flash('alerta'),
        ]);
    }

    /**
     * Submódulo: Directorio de Trabajadores y Prestaciones Sociales (?c=nomina&a=trabajadores)
     */
    public function trabajadores(): void
    {
        $this->requireAuth();

        $empresaId     = Session::getEmpresaActivaId();
        $empresaModel  = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $filtroEstatus = isset($_REQUEST['estatus']) ? strtoupper(trim((string)$_REQUEST['estatus'])) : 'TODOS';
        $busqueda      = isset($_REQUEST['q']) ? trim((string)$_REQUEST['q']) : '';

        $nominaModel  = new NominaModel();
        $config       = $nominaModel->obtenerConfiguracionLaboral($empresaId);
        $trabajadores = $nominaModel->listarTrabajadores($empresaId, $filtroEstatus, $busqueda);

        $this->render('nomina/trabajadores', [
            'tituloPagina'  => 'Directorio de Trabajadores LOTTT | ' . APP_NAME,
            'moduloActivo'  => 'nomina_trabajadores',
            'empresaActiva' => $empresaActiva,
            'configLaboral' => $config,
            'trabajadores'  => $trabajadores,
            'filtroEstatus' => $filtroEstatus,
            'filtroBuscar'  => $busqueda,
            'alertaFlash'   => Session::flash('alerta'),
        ]);
    }

    /**
     * Submódulo: Bono de Alimentación / Cestaticket Socialista (?c=nomina&a=cestaticket)
     */
    public function cestaticket(): void
    {
        $this->requireAuth();

        $empresaId     = Session::getEmpresaActivaId();
        $empresaModel  = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $nominaModel    = new NominaModel();
        $config         = $nominaModel->obtenerConfiguracionLaboral($empresaId);
        $periodoDefault = (string)($empresaActiva['periodo_fiscal_activo'] ?? date('Y-m'));
        $precalculo     = $nominaModel->precalcularCestaticket($empresaId, $periodoDefault);
        $lotes          = $nominaModel->listarLotesCestaticket($empresaId);

        $loteSeleccionadoId = isset($_GET['lote_id']) ? (int)$_GET['lote_id'] : (int)($lotes[0]['id'] ?? 0);
        $loteSeleccionado   = $loteSeleccionadoId > 0
            ? $nominaModel->obtenerLoteCestaticket($empresaId, $loteSeleccionadoId)
            : null;

        $this->render('nomina/cestaticket', [
            'tituloPagina'     => 'Bono de Alimentación (Cestaticket) | ' . APP_NAME,
            'moduloActivo'     => 'nomina_cestaticket',
            'empresaActiva'    => $empresaActiva,
            'configLaboral'    => $config,
            'precalculo'       => $precalculo,
            'lotesCestaticket' => $lotes,
            'loteSeleccionado' => $loteSeleccionado,
            'alertaFlash'      => Session::flash('alerta'),
        ]);
    }

    public function cestaticketPrint(): void
    {
        $this->requireAuth();

        $empresaId     = Session::getEmpresaActivaId();
        $empresaModel  = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $loteId           = (int)($_GET['lote_id'] ?? 0);
        $nominaModel      = new NominaModel();
        $loteSeleccionado = $nominaModel->obtenerLoteCestaticket($empresaId, $loteId);

        if (!$loteSeleccionado) {
            $this->redirect('nomina', 'cestaticket');
        }

        $this->render('nomina/cestaticket_print', [
            'empresa'          => $empresaActiva,
            'empresaActiva'    => $empresaActiva,
            'loteSeleccionado' => $loteSeleccionado,
        ], false);
    }

    /**
     * Submódulo: Emisión de Recibos de Pago Individuales y Masivos (?c=nomina&a=recibos)
     */
    public function recibos(): void
    {
        $this->requireAuth();

        $empresaId     = Session::getEmpresaActivaId();
        $empresaModel  = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $nominaModel      = new NominaModel();
        $lotesNomina      = $nominaModel->listarNominas($empresaId);
        $lotesCestaticket = $nominaModel->listarLotesCestaticket($empresaId);

        $nominaId = isset($_REQUEST['nomina_id']) ? (int)$_REQUEST['nomina_id'] : (int)($lotesNomina[0]['id'] ?? 0);
        $nominaSeleccionada = $nominaId > 0
            ? $nominaModel->obtenerNominaConDetalles($empresaId, $nominaId)
            : null;

        $cestaticketId = isset($_REQUEST['cestaticket_id']) ? (int)$_REQUEST['cestaticket_id'] : (int)($lotesCestaticket[0]['id'] ?? 0);
        $cestaticketSeleccionado = $cestaticketId > 0
            ? $nominaModel->obtenerLoteCestaticket($empresaId, $cestaticketId)
            : null;

        $this->render('nomina/recibos', [
            'tituloPagina'            => 'Emisión de Recibos de Pago LOTTT | ' . APP_NAME,
            'moduloActivo'            => 'nomina_recibos',
            'empresaActiva'           => $empresaActiva,
            'lotesNomina'             => $lotesNomina,
            'nominaSeleccionada'      => $nominaSeleccionada,
            'lotesCestaticket'        => $lotesCestaticket,
            'cestaticketSeleccionado' => $cestaticketSeleccionado,
            'alertaFlash'             => Session::flash('alerta'),
        ]);
    }

    /**
     * Vista de Impresión Legal de Recibos de Pago (Individual o Masivo de Nómina / Cestaticket)
     * (?c=nomina&a=reciboPrint&detalle_id=X | &nomina_id=X | &cestaticket_detalle_id=X | &cestaticket_lote_id=X)
     */
    public function sabanaPrint(): void
    {
        $this->requireAuth();

        $empresaId     = Session::getEmpresaActivaId();
        $empresaModel  = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $nominaId  = (int)($_GET['nomina_id'] ?? 0);
        $detalleId = (int)($_GET['detalle_id'] ?? 0);

        $nominaModel      = new NominaModel();
        $loteSeleccionado = $nominaModel->obtenerNominaConDetalles($empresaId, $nominaId);

        if (!$loteSeleccionado) {
            $this->redirect('nomina', 'index');
        }

        $this->render('nomina/sabana_print', [
            'empresa'          => $empresaActiva,
            'empresaActiva'    => $empresaActiva,
            'loteSeleccionado' => $loteSeleccionado,
            'detalleId'        => $detalleId,
        ], false);
    }

    public function reciboPrint(): void
    {
        $this->requireAuth();

        $empresaId     = Session::getEmpresaActivaId();
        $empresaModel  = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $nominaModel = new NominaModel();
        $config      = $nominaModel->obtenerConfiguracionLaboral($empresaId);

        $detalleId            = isset($_GET['detalle_id']) ? (int)$_GET['detalle_id'] : 0;
        $nominaId             = isset($_GET['nomina_id']) ? (int)$_GET['nomina_id'] : 0;
        $cestaticketDetalleId = isset($_GET['cestaticket_detalle_id']) ? (int)$_GET['cestaticket_detalle_id'] : 0;
        $cestaticketLoteId    = isset($_GET['cestaticket_lote_id']) ? (int)$_GET['cestaticket_lote_id'] : 0;

        $tipoDocumento = 'NOMINA';
        $recibos       = [];
        $cabeceraLote  = null;

        if ($detalleId > 0) {
            $recibo = $nominaModel->obtenerReciboIndividual($empresaId, $detalleId);
            if ($recibo) {
                $recibos[] = $recibo;
                $cabeceraLote = [
                    'codigo'          => $recibo['codigo_nomina'],
                    'periodo'         => $recibo['periodo'],
                    'tipo_frecuencia' => $recibo['tipo_frecuencia'],
                    'fecha_desde'     => $recibo['fecha_desde'],
                    'fecha_hasta'     => $recibo['fecha_hasta'],
                ];
            }
        } elseif ($nominaId > 0) {
            $lote = $nominaModel->obtenerNominaConDetalles($empresaId, $nominaId);
            if ($lote) {
                $cabeceraLote = [
                    'codigo'          => $lote['codigo_nomina'],
                    'periodo'         => $lote['periodo'],
                    'tipo_frecuencia' => $lote['tipo_frecuencia'],
                    'fecha_desde'     => $lote['fecha_desde'],
                    'fecha_hasta'     => $lote['fecha_hasta'],
                ];
                foreach ($lote['detalles'] as $det) {
                    $det['codigo_nomina']   = $lote['codigo_nomina'];
                    $det['periodo']         = $lote['periodo'];
                    $det['tipo_frecuencia'] = $lote['tipo_frecuencia'];
                    $det['fecha_desde']     = $lote['fecha_desde'];
                    $det['fecha_hasta']     = $lote['fecha_hasta'];
                    $det['lunes_del_mes']   = $lote['lunes_del_mes'];
                    $recibos[] = $det;
                }
            }
        } elseif ($cestaticketDetalleId > 0) {
            $tipoDocumento = 'CESTATICKET';
            $recibo = $nominaModel->obtenerReciboCestaticketIndividual($empresaId, $cestaticketDetalleId);
            if ($recibo) {
                $recibos[] = $recibo;
                $cabeceraLote = [
                    'codigo'        => $recibo['codigo_lote'],
                    'periodo'       => $recibo['periodo'],
                    'fecha_emision' => $recibo['fecha_emision'],
                ];
            }
        } elseif ($cestaticketLoteId > 0) {
            $tipoDocumento = 'CESTATICKET';
            $lote = $nominaModel->obtenerLoteCestaticket($empresaId, $cestaticketLoteId);
            if ($lote) {
                $cabeceraLote = [
                    'codigo'        => $lote['codigo_lote'],
                    'periodo'       => $lote['periodo'],
                    'fecha_emision' => $lote['fecha_emision'],
                ];
                foreach ($lote['detalles'] as $det) {
                    $det['codigo_lote']   = $lote['codigo_lote'];
                    $det['periodo']       = $lote['periodo'];
                    $det['fecha_emision'] = $lote['fecha_emision'];
                    $recibos[] = $det;
                }
            }
        }

        if (empty($recibos)) {
            Session::flash('alerta', [
                'tipo'    => 'warning',
                'titulo'  => 'Recibo no encontrado',
                'mensaje' => 'El recibo o lote solicitado no existe o no pertenece a la empresa activa.',
            ]);
            $this->redirect('nomina', 'recibos');
        }

        $viewFile = ROOT_PATH . '/views/nomina/recibo_print.php';
        $empresa  = $empresaActiva;
        require $viewFile;
        exit;
    }

    // =========================================================================
    // ENDPOINTS JSON (AJAX FETCH CON VALIDACIÓN CSRF)
    // =========================================================================

    /**
     * Guarda los parámetros laborales de la empresa activa (POST JSON)
     */
    public function guardarConfigLaboral(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $body      = $this->getJsonBody();
        $datos     = !empty($body) ? $body : $_POST;

        try {
            $nominaModel = new NominaModel();
            $nominaModel->actualizarConfiguracionLaboral($empresaId, $datos);

            $this->json([
                'ok'      => true,
                'message' => 'Parámetros laborales LOTTT / IVSS / FAOV / Cestaticket actualizados correctamente.',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtiene los datos de un trabajador por su ID dentro de la empresa activa (POST JSON)
     */
    public function obtenerTrabajador(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $empresaId    = Session::getEmpresaActivaId();
        $input        = $this->getJsonInput();
        $trabajadorId = (int)($input['id'] ?? 0);

        $nominaModel = new NominaModel();
        $trabajador  = $nominaModel->obtenerTrabajador($empresaId, $trabajadorId);

        if (!$trabajador) {
            $this->json([
                'ok'      => false,
                'message' => 'Trabajador no encontrado en la empresa activa.',
            ], 404);
        }

        $this->json([
            'ok'         => true,
            'trabajador' => $trabajador,
        ]);
    }

    /**
     * Crea o actualiza un trabajador (POST JSON)
     */
    public function guardarTrabajador(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $body      = $this->getJsonBody();
        $datos     = !empty($body) ? $body : $_POST;

        try {
            $nominaModel = new NominaModel();
            $id = $nominaModel->guardarTrabajador($empresaId, $datos);

            $this->json([
                'ok'            => true,
                'trabajador_id' => $id,
                'message'       => 'Ficha del trabajador guardada correctamente.',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Precalcula la hoja de trabajo de nómina según frecuencia y lunes del mes (POST JSON)
     */
    public function precalcularNomina(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $body      = $this->getJsonBody();
        $datos     = !empty($body) ? $body : $_POST;

        try {
            $nominaModel = new NominaModel();
            $resultado   = $nominaModel->precalcularLoteNomina($empresaId, $datos);

            $this->json([
                'ok'         => true,
                'precalculo' => $resultado,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Procesa y aprueba un lote de nómina (POST JSON)
     */
    public function guardarNomina(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $usuarioId = (int)Session::get('usuario_id', 1);
        $body      = $this->getJsonBody();
        $datos     = !empty($body) ? $body : $_POST;

        try {
            $nominaModel = new NominaModel();
            $res = $nominaModel->procesarYGuardarNomina($empresaId, $usuarioId, $datos);

            $this->json([
                'ok'        => true,
                'resultado' => $res,
                'message'   => sprintf(
                    'Lote de nómina %s procesado exitosamente por un neto total de %s.',
                    $res['codigo_nomina'],
                    formato_ves((float)$res['neto_total'])
                ),
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Anula un lote de nómina y su asiento vinculado (POST JSON)
     */
    public function anularNomina(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $body      = $this->getJsonBody();
        $nominaId  = (int)($body['nomina_id'] ?? $_POST['nomina_id'] ?? 0);

        try {
            $nominaModel = new NominaModel();
            $nominaModel->anularNomina($empresaId, $nominaId);

            $this->json([
                'ok'      => true,
                'message' => 'El lote de nómina y su asiento contable vinculado han sido anulados.',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Guarda un lote mensual de Cestaticket / Bono de Alimentación (POST JSON)
     */
    public function guardarCestaticket(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $usuarioId = (int)Session::get('usuario_id', 1);
        $body      = $this->getJsonBody();
        $datos     = !empty($body) ? $body : $_POST;

        try {
            $nominaModel = new NominaModel();
            $res = $nominaModel->guardarLoteCestaticket($empresaId, $usuarioId, $datos);

            $this->json([
                'ok'        => true,
                'resultado' => $res,
                'message'   => sprintf(
                    'Lote de Cestaticket %s registrado exitosamente por %s.',
                    $res['codigo_lote'],
                    formato_ves((float)$res['total_pagar'])
                ),
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // =========================================================================
    // EXPORTACIÓN A EXCEL (CSV UTF-8 CON BOM)
    // =========================================================================

    /**
     * Exporta un lote de nómina procesado a formato CSV compatible con Excel
     */
    public function exportarNominaCsv(): void
    {
        $this->requireAuth();

        $empresaId    = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresa      = $empresaModel->obtenerPorId($empresaId);
        $nominaId     = isset($_GET['nomina_id']) ? (int)$_GET['nomina_id'] : 0;

        $nominaModel = new NominaModel();
        $lote        = $nominaModel->obtenerNominaConDetalles($empresaId, $nominaId);

        if (!$empresa || !$lote) {
            $this->redirect('nomina', 'index');
        }

        $filename = 'NOMINA_' . preg_replace('/[^A-Z0-9_-]/i', '_', (string)$lote['codigo_nomina']) . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        // BOM UTF-8 para apertura directa en Microsoft Excel
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [(string)$empresa['razon_social'] . ' - RIF: ' . (string)$empresa['rif']], ';');
        fputcsv($out, [
            'LOTE: ' . $lote['codigo_nomina'],
            'PERÍODO: ' . $lote['periodo'],
            'DESDE: ' . $lote['fecha_desde'],
            'HASTA: ' . $lote['fecha_hasta'],
            'LUNES DEL MES: ' . $lote['lunes_del_mes'],
        ], ';');
        fputcsv($out, [], ';');

        fputcsv($out, [
            'N°',
            'Cédula',
            'Apellidos y Nombres',
            'Cargo',
            'Fecha Ingreso',
            'Sueldo Mensual (Bs.)',
            'Sueldo Diario (Bs.)',
            'Días Hábiles',
            'Días Descanso',
            'Días Feriados',
            'Total Días',
            'Sueldo Básico Período',
            'Horas Extras',
            'Bono Producción / Otros',
            'Total Asignaciones',
            'Ret. IVSS (4%)',
            'Ret. RPE (0.5%)',
            'Ret. FAOV (1%)',
            'Ret. ISLR',
            'Otras Deducciones',
            'Total Deducciones',
            'NETO A CANCELAR (Bs.)',
        ], ';');

        $i = 1;
        foreach ($lote['detalles'] as $d) {
            fputcsv($out, [
                $i++,
                $d['cedula'],
                $d['nombre_completo'],
                $d['cargo'],
                $d['fecha_ingreso'],
                number_format((float)$d['salario_mensual'], 2, ',', '.'),
                number_format((float)$d['sueldo_diario'], 2, ',', '.'),
                number_format((float)$d['dias_habiles'], 2, ',', '.'),
                number_format((float)$d['dias_descanso'], 2, ',', '.'),
                number_format((float)$d['dias_feriados'], 2, ',', '.'),
                number_format((float)$d['dias_trabajados'], 2, ',', '.'),
                number_format((float)$d['sueldo_basico_periodo'], 2, ',', '.'),
                number_format((float)$d['horas_extras'], 2, ',', '.'),
                number_format((float)$d['bonificaciones'], 2, ',', '.'),
                number_format((float)$d['total_asignaciones'], 2, ',', '.'),
                number_format((float)$d['retencion_sso'], 2, ',', '.'),
                number_format((float)$d['retencion_rpe'], 2, ',', '.'),
                number_format((float)$d['retencion_faov'], 2, ',', '.'),
                number_format((float)$d['retencion_islr'], 2, ',', '.'),
                number_format((float)$d['otras_deducciones'], 2, ',', '.'),
                number_format((float)$d['total_deducciones'], 2, ',', '.'),
                number_format((float)$d['neto_pagar'], 2, ',', '.'),
            ], ';');
        }

        fclose($out);
        exit;
    }

    /**
     * Exporta un lote de Cestaticket a formato CSV compatible con Excel
     */
    public function exportarCestaticketCsv(): void
    {
        $this->requireAuth();

        $empresaId    = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresa      = $empresaModel->obtenerPorId($empresaId);
        $loteId       = isset($_GET['lote_id']) ? (int)$_GET['lote_id'] : 0;

        $nominaModel = new NominaModel();
        $lote        = $nominaModel->obtenerLoteCestaticket($empresaId, $loteId);

        if (!$empresa || !$lote) {
            $this->redirect('nomina', 'cestaticket');
        }

        $filename = 'BONO_ALIMENTACION_' . preg_replace('/[^A-Z0-9_-]/i', '_', (string)$lote['codigo_lote']) . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [(string)$empresa['razon_social'] . ' - RIF: ' . (string)$empresa['rif']], ';');
        fputcsv($out, [
            'CONTROL DE BONO DE ALIMENTACIÓN (CESTATICKET SOCIALISTA)',
            'LOTE: ' . $lote['codigo_lote'],
            'PERÍODO: ' . $lote['periodo'],
            'FECHA: ' . $lote['fecha_emision'],
        ], ';');
        fputcsv($out, [], ';');

        fputcsv($out, [
            'N°',
            'Cédula',
            'Nombre y Apellido',
            'Cargo',
            'Fecha Ingreso',
            'Días Hábiles',
            'Sáb/Dom (Descanso)',
            'Feriados',
            'Inasistencias',
            'Días Efectivos',
            'Monto Mensual Base (Bs.)',
            'Valor Diario (Bs.)',
            'Descuento Inasistencias (Bs.)',
            'TOTAL A PAGAR (Bs.)',
        ], ';');

        $i = 1;
        foreach ($lote['detalles'] as $d) {
            fputcsv($out, [
                $i++,
                $d['cedula'],
                $d['nombre_completo'],
                $d['cargo'],
                $d['fecha_ingreso'],
                number_format((float)$d['dias_habiles'], 2, ',', '.'),
                number_format((float)$d['dias_descanso'], 2, ',', '.'),
                number_format((float)$d['dias_feriados'], 2, ',', '.'),
                number_format((float)$d['dias_inasistencia'], 2, ',', '.'),
                number_format((float)$d['dias_efectivos'], 2, ',', '.'),
                number_format((float)$d['monto_mensual_base'], 2, ',', '.'),
                number_format((float)$d['valor_diario'], 2, ',', '.'),
                number_format((float)$d['monto_descuento'], 2, ',', '.'),
                number_format((float)$d['total_cestaticket'], 2, ',', '.'),
            ], ';');
        }

        fclose($out);
        exit;
    }
}
