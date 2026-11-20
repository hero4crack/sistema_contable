<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\AsientoModel;
use Models\EmpresaModel;
use Models\PlanCuentasModel;
use Throwable;

/**
 * Controlador de Contabilidad General y Libros Legales
 * (Libro Diario, Asientos de Partida Doble, Libro Mayor Analítico y Estados Financieros)
 */
final class ContabilidadController extends Controller
{
    public function index(): void
    {
        $this->diario();
    }

    /**
     * Módulo Libro Diario y Registro de Asientos Contables (?c=contabilidad&a=diario)
     */
    public function diario(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $periodoActivo = (string)($empresaActiva['periodo_fiscal_activo'] ?? date('Y-m'));
        $primerDiaPeriodo = $periodoActivo . '-01';
        $ultimoDiaPeriodo = date('Y-m-t', strtotime($primerDiaPeriodo));

        $fechaDesde = isset($_REQUEST['fecha_desde']) && $_REQUEST['fecha_desde'] !== '' ? trim((string)$_REQUEST['fecha_desde']) : '2026-01-01';
        $fechaHasta = isset($_REQUEST['fecha_hasta']) && $_REQUEST['fecha_hasta'] !== '' ? trim((string)$_REQUEST['fecha_hasta']) : $ultimoDiaPeriodo;
        $busqueda   = isset($_REQUEST['q']) ? trim((string)$_REQUEST['q']) : '';
        $estado     = isset($_REQUEST['estado']) ? trim((string)$_REQUEST['estado']) : '';

        $asientoModel = new AsientoModel();
        $asientos = $asientoModel->listarAsientos($empresaId, [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'busqueda'    => $busqueda,
            'estado'      => $estado,
        ]);

        // Si solicita exportación a CSV / Excel
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportarDiarioCsv($empresaActiva, $asientos, $fechaDesde, $fechaHasta);
        }

        $planModel = new PlanCuentasModel();
        $cuentasImputables = $planModel->listarImputablesPorEmpresa($empresaId);
        $proximoNumero = $asientoModel->generarProximoNumero($empresaId, $periodoActivo);

        $this->render('contabilidad/diario', [
            'tituloPagina'      => 'Libro Diario y Asientos Contables | ' . APP_NAME,
            'moduloActivo'      => 'diario',
            'empresaActiva'     => $empresaActiva,
            'asientos'          => $asientos,
            'cuentasImputables' => $cuentasImputables,
            'proximoNumero'     => $proximoNumero,
            'fechaDesde'        => $fechaDesde,
            'fechaHasta'        => $fechaHasta,
            'filtroBuscar'      => $busqueda,
            'filtroEstado'      => $estado,
            'alertaFlash'       => Session::flash('alerta'),
        ]);
    }

    /**
     * Guarda un nuevo asiento contable validando estrictamente la Partida Doble (POST JSON)
     */
    public function guardarAsiento(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $usuarioId = (int)Session::get('usuario_id', 0);

        if ($empresaId <= 0) {
            $this->json(['ok' => false, 'message' => 'No hay empresa activa en sesión.'], 400);
        }

        $input = $this->getJsonInput();
        $cabecera = [
            'fecha'      => $input['fecha'] ?? date('Y-m-d'),
            'concepto'   => $input['concepto'] ?? '',
            'referencia' => $input['referencia'] ?? '',
            'origen'     => 'MANUAL',
        ];
        $renglones = is_array($input['renglones'] ?? null) ? $input['renglones'] : [];

        $asientoModel = new AsientoModel();

        try {
            $resultado = $asientoModel->crearAsientoPartidaDoble($empresaId, $usuarioId, $cabecera, $renglones);

            $this->json([
                'ok'      => true,
                'message' => sprintf(
                    'Asiento Contable %s registrado exitosamente por %s (Debe = Haber).',
                    $resultado['numero_asiento'],
                    formato_ves($resultado['total_debe'])
                ),
                'asiento' => $resultado,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtiene el detalle completo de un asiento contable en JSON para mostrar en modal (POST)
     */
    public function verAsiento(): void
    {
        $this->requireAuth();
        $this->requirePost();
        $this->validateCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $input     = $this->getJsonInput();
        $id        = (int)($input['id'] ?? 0);

        if ($id <= 0 || $empresaId <= 0) {
            $this->json(['ok' => false, 'message' => 'ID de asiento inválido.'], 400);
        }

        $asientoModel = new AsientoModel();
        $asiento = $asientoModel->obtenerAsientoConDetalles($id, $empresaId);

        if (!$asiento) {
            $this->json(['ok' => false, 'message' => 'El asiento solicitado no existe en la empresa activa.'], 404);
        }

        $this->json([
            'ok'      => true,
            'asiento' => $asiento,
        ]);
    }

    /**
     * Anula un asiento contable sin eliminarlo físicamente (POST JSON)
     */
    public function anularAsiento(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $input     = $this->getJsonInput();
        $id        = (int)($input['id'] ?? 0);
        $motivo    = trim((string)($input['motivo'] ?? 'Anulación manual autorizada'));

        if ($id <= 0 || $empresaId <= 0) {
            $this->json(['ok' => false, 'message' => 'ID de asiento inválido.'], 400);
        }

        $asientoModel = new AsientoModel();

        try {
            $asientoModel->anularAsiento($id, $empresaId, $motivo);
            $this->json([
                'ok'      => true,
                'message' => 'El asiento contable ha sido marcado como ANULADO conservando su trazabilidad fiscal.',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Módulo Libro Mayor Analítico (?c=contabilidad&a=mayor)
     */
    public function mayor(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $periodoActivo = (string)($empresaActiva['periodo_fiscal_activo'] ?? date('Y-m'));
        $primerDiaPeriodo = $periodoActivo . '-01';
        $ultimoDiaPeriodo = date('Y-m-t', strtotime($primerDiaPeriodo));

        $fechaDesde = isset($_REQUEST['fecha_desde']) && $_REQUEST['fecha_desde'] !== '' ? trim((string)$_REQUEST['fecha_desde']) : $primerDiaPeriodo;
        $fechaHasta = isset($_REQUEST['fecha_hasta']) && $_REQUEST['fecha_hasta'] !== '' ? trim((string)$_REQUEST['fecha_hasta']) : $ultimoDiaPeriodo;
        $cuentaId   = isset($_REQUEST['cuenta_id']) && (int)$_REQUEST['cuenta_id'] > 0 ? (int)$_REQUEST['cuenta_id'] : null;

        $planModel = new PlanCuentasModel();
        $cuentasImputables = $planModel->listarImputablesPorEmpresa($empresaId);

        $asientoModel = new AsientoModel();
        $libroMayor = $asientoModel->obtenerLibroMayor($empresaId, $fechaDesde, $fechaHasta, $cuentaId);

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportarMayorCsv($empresaActiva, $libroMayor, $fechaDesde, $fechaHasta);
        }

        $this->render('contabilidad/mayor', [
            'tituloPagina'      => 'Libro Mayor Analítico | ' . APP_NAME,
            'moduloActivo'      => 'mayor',
            'empresaActiva'     => $empresaActiva,
            'cuentasImputables' => $cuentasImputables,
            'libroMayor'        => $libroMayor,
            'fechaDesde'        => $fechaDesde,
            'fechaHasta'        => $fechaHasta,
            'cuentaSeleccionada'=> $cuentaId ?? 0,
            'alertaFlash'       => Session::flash('alerta'),
        ]);
    }

    /**
     * Vista de Impresión Oficial de Registro Mercantil: Libro Diario y Resumen de Diario
     * (?c=contabilidad&a=diarioPrint&fecha_desde=X&fecha_hasta=Y&formato=detallado|resumen)
     */
    public function diarioPrint(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $periodoActivo = (string)($empresaActiva['periodo_fiscal_activo'] ?? date('Y-m'));
        $primerDiaPeriodo = $periodoActivo . '-01';
        $ultimoDiaPeriodo = date('Y-m-t', strtotime($primerDiaPeriodo));

        $fechaDesde = isset($_GET['fecha_desde']) && $_GET['fecha_desde'] !== '' ? trim((string)$_GET['fecha_desde']) : '2026-01-01';
        $fechaHasta = isset($_GET['fecha_hasta']) && $_GET['fecha_hasta'] !== '' ? trim((string)$_GET['fecha_hasta']) : $ultimoDiaPeriodo;
        $formato    = isset($_GET['formato']) && $_GET['formato'] === 'resumen' ? 'resumen' : 'detallado';
        $folio      = isset($_GET['folio']) && (int)$_GET['folio'] > 0 ? (int)$_GET['folio'] : 1;

        $asientoModel = new AsientoModel();
        $asientos = $asientoModel->listarAsientos($empresaId, [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'estado'      => 'PUBLICADO',
        ]);

        // Ordenar cronológicamente ascendente para el Libro Diario Legal (Art. 34 Código de Comercio)
        usort($asientos, static fn(array $a, array $b): int => [$a['fecha'], $a['id']] <=> [$b['fecha'], $b['id']]);

        $resumenCuentas = $asientoModel->obtenerResumenDiarioPorCuenta($empresaId, $fechaDesde, $fechaHasta);

        $empresa  = $empresaActiva;
        $viewFile = ROOT_PATH . '/views/contabilidad/diario_print.php';
        require $viewFile;
        exit;
    }

    /**
     * Vista de Impresión Oficial de Registro Mercantil: Mayor Analítico Resumido y Libro Mayor
     * (?c=contabilidad&a=mayorPrint&fecha_desde=X&fecha_hasta=Y&cuenta_id=Z&formato=italo|charq)
     */
    public function mayorPrint(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $periodoActivo = (string)($empresaActiva['periodo_fiscal_activo'] ?? date('Y-m'));
        $primerDiaPeriodo = $periodoActivo . '-01';
        $ultimoDiaPeriodo = date('Y-m-t', strtotime($primerDiaPeriodo));

        $fechaDesde = isset($_REQUEST['fecha_desde']) && $_REQUEST['fecha_desde'] !== '' ? trim((string)$_REQUEST['fecha_desde']) : $primerDiaPeriodo;
        $fechaHasta = isset($_REQUEST['fecha_hasta']) && $_REQUEST['fecha_hasta'] !== '' ? trim((string)$_REQUEST['fecha_hasta']) : $ultimoDiaPeriodo;
        $cuentaId   = isset($_REQUEST['cuenta_id']) && (int)$_REQUEST['cuenta_id'] > 0 ? (int)$_REQUEST['cuenta_id'] : null;
        $formato    = isset($_GET['formato']) && $_GET['formato'] === 'tabla' ? 'tabla' : 'clasico';
        $agruparMes = isset($_GET['agrupar_mes']) && $_GET['agrupar_mes'] === '1';
        $folio      = isset($_GET['folio']) && (int)$_GET['folio'] > 0 ? (int)$_GET['folio'] : 1;

        $asientoModel = new AsientoModel();
        $libroMayor = $asientoModel->obtenerLibroMayor($empresaId, $fechaDesde, $fechaHasta, $cuentaId);

        $empresa  = $empresaActiva;
        $viewFile = ROOT_PATH . '/views/contabilidad/mayor_print.php';
        require $viewFile;
        exit;
    }

    /**
     * Módulo Estados Financieros (?c=contabilidad&a=estados)
     * 1. Balance de Comprobación
     * 2. Estado de Resultados
     * 3. Balance General (Estado de Situación Financiera)
     */
    public function estados(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $periodoActivo = (string)($empresaActiva['periodo_fiscal_activo'] ?? date('Y-m'));
        $primerDiaPeriodo = $periodoActivo . '-01';
        $ultimoDiaPeriodo = date('Y-m-t', strtotime($primerDiaPeriodo));

        $fechaDesde = isset($_REQUEST['fecha_desde']) && $_REQUEST['fecha_desde'] !== '' ? trim((string)$_REQUEST['fecha_desde']) : $primerDiaPeriodo;
        $fechaHasta = isset($_REQUEST['fecha_hasta']) && $_REQUEST['fecha_hasta'] !== '' ? trim((string)$_REQUEST['fecha_hasta']) : $ultimoDiaPeriodo;
        $tabActiva  = isset($_REQUEST['tab']) && in_array($_REQUEST['tab'], ['comprobacion', 'resultados', 'general'], true)
            ? (string)$_REQUEST['tab']
            : 'comprobacion';

        $asientoModel = new AsientoModel();
        $estados = $asientoModel->obtenerEstadosFinancieros($empresaId, $fechaDesde, $fechaHasta);

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportarEstadosCsv($empresaActiva, $estados, $tabActiva, $fechaDesde, $fechaHasta);
        }

        $this->render('contabilidad/estados', [
            'tituloPagina'  => 'Estados Financieros | ' . APP_NAME,
            'moduloActivo'  => 'estados',
            'empresaActiva' => $empresaActiva,
            'estados'       => $estados,
            'fechaDesde'    => $fechaDesde,
            'fechaHasta'    => $fechaHasta,
            'tabActiva'     => $tabActiva,
            'alertaFlash'   => Session::flash('alerta'),
        ]);
    }

    public function estadosPrint(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $periodoActivo = (string)($empresaActiva['periodo_fiscal_activo'] ?? date('Y-m'));
        $primerDiaPeriodo = $periodoActivo . '-01';
        $ultimoDiaPeriodo = date('Y-m-t', strtotime($primerDiaPeriodo));

        $fechaDesde = isset($_GET['fecha_desde']) && $_GET['fecha_desde'] !== '' ? trim((string)$_GET['fecha_desde']) : $primerDiaPeriodo;
        $fechaHasta = isset($_GET['fecha_hasta']) && $_GET['fecha_hasta'] !== '' ? trim((string)$_GET['fecha_hasta']) : $ultimoDiaPeriodo;
        $tabActiva  = isset($_GET['tab']) && in_array($_GET['tab'], ['comprobacion', 'resultados', 'general'], true)
            ? (string)$_GET['tab']
            : 'comprobacion';

        $asientoModel = new AsientoModel();
        $estados = $asientoModel->obtenerEstadosFinancieros($empresaId, $fechaDesde, $fechaHasta);

        $this->render('contabilidad/estados_print', [
            'empresa'       => $empresaActiva,
            'empresaActiva' => $empresaActiva,
            'estados'       => $estados,
            'fechaDesde'    => $fechaDesde,
            'fechaHasta'    => $fechaHasta,
            'tabActiva'     => $tabActiva,
        ], false);
    }

    /**
     * Exporta el Libro Diario a formato CSV compatible con Microsoft Excel (UTF-8 con BOM)
     */
    private function exportarDiarioCsv(array $empresa, array $asientos, string $desde, string $hasta): never
    {
        $filename = sprintf('Libro_Diario_%s_%s_al_%s.csv', $empresa['rif'], $desde, $hasta);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel

        fputcsv($out, [$empresa['razon_social'] . ' - RIF: ' . $empresa['rif']], ';');
        fputcsv($out, ['LIBRO DIARIO LEGAL - DESDE ' . $desde . ' HASTA ' . $hasta], ';');
        fputcsv($out, [], ';');
        fputcsv($out, ['FECHA', 'NRO ASIENTO', 'REFERENCIA', 'ESTADO', 'CODIGO CUENTA', 'NOMBRE DE CUENTA', 'DESCRIPCION / GLOSA', 'DEBE (Bs.)', 'HABER (Bs.)'], ';');

        foreach ($asientos as $asi) {
            foreach ($asi['detalles'] as $det) {
                fputcsv($out, [
                    $asi['fecha'],
                    $asi['numero_asiento'],
                    $asi['referencia'] ?? '',
                    $asi['estado'],
                    $det['cuenta_codigo'],
                    $det['cuenta_nombre'],
                    $det['descripcion'] ?: $asi['concepto'],
                    number_format((float)$det['debe'], 2, ',', '.'),
                    number_format((float)$det['haber'], 2, ',', '.'),
                ], ';');
            }
        }

        fclose($out);
        exit;
    }

    /**
     * Exporta el Libro Mayor Analítico a CSV compatible con Excel
     */
    private function exportarMayorCsv(array $empresa, array $libroMayor, string $desde, string $hasta): never
    {
        $filename = sprintf('Libro_Mayor_%s_%s_al_%s.csv', $empresa['rif'], $desde, $hasta);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [$empresa['razon_social'] . ' - RIF: ' . $empresa['rif']], ';');
        fputcsv($out, ['LIBRO MAYOR ANALÍTICO - DESDE ' . $desde . ' HASTA ' . $hasta], ';');
        fputcsv($out, [], ';');
        fputcsv($out, ['CUENTA', 'NOMBRE CUENTA', 'NATURALEZA', 'FECHA', 'COMPROBANTE', 'DESCRIPCION', 'DEBE (Bs.)', 'HABER (Bs.)', 'SALDO ACUMULADO (Bs.)'], ';');

        foreach ($libroMayor as $bloque) {
            $cta = $bloque['cuenta'];
            fputcsv($out, [
                $cta['codigo'],
                $cta['nombre'],
                $cta['naturaleza'],
                $desde,
                'SALDO-ANT',
                'SALDO ANTERIOR AL ' . $desde,
                '0,00',
                '0,00',
                number_format((float)$bloque['saldo_anterior'], 2, ',', '.'),
            ], ';');

            foreach ($bloque['movimientos'] as $m) {
                fputcsv($out, [
                    $cta['codigo'],
                    $cta['nombre'],
                    $cta['naturaleza'],
                    $m['fecha'],
                    $m['numero_asiento'],
                    $m['descripcion_linea'] ?: $m['concepto'],
                    number_format((float)$m['debe'], 2, ',', '.'),
                    number_format((float)$m['haber'], 2, ',', '.'),
                    number_format((float)$m['saldo_acumulado'], 2, ',', '.'),
                ], ';');
            }
        }

        fclose($out);
        exit;
    }

    /**
     * Exporta el Estado Financiero activo a CSV compatible con Excel
     */
    private function exportarEstadosCsv(array $empresa, array $estados, string $tab, string $desde, string $hasta): never
    {
        $filename = sprintf('Estado_Financiero_%s_%s_%s.csv', strtoupper($tab), $empresa['rif'], $hasta);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [$empresa['razon_social'] . ' - RIF: ' . $empresa['rif']], ';');
        fputcsv($out, ['PERIODO CONSULTADO: ' . $desde . ' AL ' . $hasta], ';');
        fputcsv($out, [], ';');

        if ($tab === 'resultados') {
            $er = $estados['estado_resultados'];
            fputcsv($out, ['ESTADO DE RESULTADOS (GANANCIAS Y PÉRDIDAS)'], ';');
            fputcsv($out, ['CODIGO', 'CONCEPTO / CUENTA', 'MONTO (Bs.)'], ';');
            foreach ($er['ingresos'] as $row) {
                fputcsv($out, [$row['codigo'], $row['nombre'], number_format((float)$row['monto'], 2, ',', '.')], ';');
            }
            fputcsv($out, ['', 'TOTAL INGRESOS OPERACIONALES', number_format((float)$er['total_ingresos'], 2, ',', '.')], ';');
            foreach ($er['costos'] as $row) {
                fputcsv($out, [$row['codigo'], $row['nombre'], number_format((float)$row['monto'], 2, ',', '.')], ';');
            }
            fputcsv($out, ['', 'TOTAL COSTO DE VENTAS', number_format((float)$er['total_costos'], 2, ',', '.')], ';');
            fputcsv($out, ['', 'UTILIDAD BRUTA EN OPERACIONES', number_format((float)$er['utilidad_bruta'], 2, ',', '.')], ';');
            foreach ($er['gastos'] as $row) {
                fputcsv($out, [$row['codigo'], $row['nombre'], number_format((float)$row['monto'], 2, ',', '.')], ';');
            }
            fputcsv($out, ['', 'TOTAL GASTOS OPERACIONALES', number_format((float)$er['total_gastos'], 2, ',', '.')], ';');
            fputcsv($out, ['', 'UTILIDAD O PÉRDIDA DEL EJERCICIO', number_format((float)$er['resultado_neto'], 2, ',', '.')], ';');
        } elseif ($tab === 'general') {
            $bg = $estados['balance_general'];
            fputcsv($out, ['BALANCE GENERAL (ESTADO DE SITUACIÓN FINANCIERA AL ' . $hasta . ')'], ';');
            fputcsv($out, ['SECCION', 'CODIGO', 'CUENTA', 'MONTO (Bs.)'], ';');
            foreach ($bg['activos'] as $row) {
                fputcsv($out, ['ACTIVO', $row['codigo'], $row['nombre'], number_format((float)$row['monto'], 2, ',', '.')], ';');
            }
            fputcsv($out, ['ACTIVO', '', 'TOTAL ACTIVO', number_format((float)$bg['total_activos'], 2, ',', '.')], ';');
            foreach ($bg['pasivos'] as $row) {
                fputcsv($out, ['PASIVO', $row['codigo'], $row['nombre'], number_format((float)$row['monto'], 2, ',', '.')], ';');
            }
            fputcsv($out, ['PASIVO', '', 'TOTAL PASIVO', number_format((float)$bg['total_pasivos'], 2, ',', '.')], ';');
            foreach ($bg['patrimonio'] as $row) {
                fputcsv($out, ['PATRIMONIO', $row['codigo'], $row['nombre'], number_format((float)$row['monto'], 2, ',', '.')], ';');
            }
            fputcsv($out, ['PATRIMONIO', '3.2.02.001', 'Resultado Acumulado Ejercicios Anteriores', number_format((float)$bg['resultado_ejercicios_anteriores'], 2, ',', '.')], ';');
            fputcsv($out, ['PATRIMONIO', '3.2.02.002', 'Utilidad o Pérdida del Ejercicio', number_format((float)$bg['resultado_ejercicio_actual'], 2, ',', '.')], ';');
            fputcsv($out, ['PATRIMONIO', '', 'TOTAL PATRIMONIO', number_format((float)$bg['total_patrimonio'], 2, ',', '.')], ';');
            fputcsv($out, ['ECUACION', '', 'TOTAL PASIVO + PATRIMONIO', number_format((float)$bg['total_pasivo_patrimonio'], 2, ',', '.')], ';');
        } else {
            $bc = $estados['balance_comprobacion'];
            fputcsv($out, ['BALANCE DE COMPROBACIÓN'], ';');
            fputcsv($out, ['CODIGO', 'CUENTA', 'SALDO ANTERIOR DEUDOR', 'SALDO ANTERIOR ACREEDOR', 'DEBITOS PERIODO', 'CREDITOS PERIODO', 'SALDO ACTUAL DEUDOR', 'SALDO ACTUAL ACREEDOR'], ';');
            foreach ($bc['filas'] as $f) {
                fputcsv($out, [
                    $f['codigo'],
                    $f['nombre'],
                    number_format((float)$f['saldo_anterior_deudor'], 2, ',', '.'),
                    number_format((float)$f['saldo_anterior_acreedor'], 2, ',', '.'),
                    number_format((float)$f['debitos_periodo'], 2, ',', '.'),
                    number_format((float)$f['creditos_periodo'], 2, ',', '.'),
                    number_format((float)$f['saldo_actual_deudor'], 2, ',', '.'),
                    number_format((float)$f['saldo_actual_acreedor'], 2, ',', '.'),
                ], ';');
            }
            $t = $bc['totales'];
            fputcsv($out, [
                '',
                'TOTALES IGUALES',
                number_format((float)$t['saldo_anterior_deudor'], 2, ',', '.'),
                number_format((float)$t['saldo_anterior_acreedor'], 2, ',', '.'),
                number_format((float)$t['debitos_periodo'], 2, ',', '.'),
                number_format((float)$t['creditos_periodo'], 2, ',', '.'),
                number_format((float)$t['saldo_actual_deudor'], 2, ',', '.'),
                number_format((float)$t['saldo_actual_acreedor'], 2, ',', '.'),
            ], ';');
        }

        fclose($out);
        exit;
    }
}
