<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\EmpresaModel;
use Models\TributarioModel;
use Throwable;

/**
 * Controlador del Módulo Tributario SENIAT:
 * - Libros Auxiliares de Compras y Ventas + Resumen Artículo 72 (Forma 30)
 * - Comprobantes de Retención de IVA (SNAT/2015/0049 - SNAT/2025/000054)
 * - Comprobantes de Retención de ISLR (Decreto 1.808)
 */
final class TributarioController extends Controller
{
    public function index(): void
    {
        $this->libros();
    }

    /**
     * Vista integrada de Libro de Compras y Libro de Ventas (?c=tributario&a=libros)
     */
    public function libros(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $periodo = isset($_REQUEST['periodo']) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string)$_REQUEST['periodo'])
            ? (string)$_REQUEST['periodo']
            : (string)$empresaActiva['periodo_fiscal_activo'];

        $tabActiva = isset($_REQUEST['tab']) && in_array($_REQUEST['tab'], ['compras', 'ventas', 'resumen72'], true)
            ? (string)$_REQUEST['tab']
            : 'compras';

        $tributarioModel = new TributarioModel();
        $configFiscal    = $tributarioModel->obtenerConfiguracionFiscal($empresaId);
        $libroCompras    = $tributarioModel->listarLibroCompras($empresaId, $periodo);
        $libroVentas     = $tributarioModel->listarLibroVentas($empresaId, $periodo);
        $resumenArt72    = $tributarioModel->obtenerResumenArticulo72($empresaId, $periodo);

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $tipoLibro = (string)($_GET['libro'] ?? 'compras');
            if ($tipoLibro === 'ventas') {
                $this->exportarLibroVentasCsv($empresaActiva, $libroVentas, $resumenArt72, $periodo);
            } else {
                $this->exportarLibroComprasCsv($empresaActiva, $libroCompras, $resumenArt72, $periodo);
            }
        }

        $this->render('tributario/libros', [
            'tituloPagina'  => 'Libros de Compras y Ventas SENIAT | ' . APP_NAME,
            'moduloActivo'  => 'tributario_libros',
            'empresaActiva' => $empresaActiva,
            'configFiscal'  => $configFiscal,
            'libroCompras'  => $libroCompras,
            'libroVentas'   => $libroVentas,
            'resumenArt72'  => $resumenArt72,
            'periodo'       => $periodo,
            'tabActiva'     => $tabActiva,
            'alertaFlash'   => Session::flash('alerta'),
        ]);
    }

    /**
     * Guarda la configuración tributaria de la empresa activa (UT, Sustraendo, % Retención IVA, Excedente)
     */
    public function guardarConfigFiscal(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $input = $this->getJsonInput();

        $tributarioModel = new TributarioModel();

        try {
            $tributarioModel->actualizarConfiguracionFiscal($empresaId, $input);

            $empresaModel = new EmpresaModel();
            $actualizada = $empresaModel->obtenerPorId($empresaId);
            if ($actualizada) {
                Session::setEmpresaActiva($actualizada);
            }

            $this->json([
                'ok'      => true,
                'message' => 'Parámetros tributarios SENIAT (Unidad Tributaria, Sustraendo y Alícuotas) actualizados correctamente.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Registra una factura en el Libro de Compras (con opción de Retención IVA y Asiento Automático)
     */
    public function guardarCompra(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $usuarioId = (int)Session::get('usuario_id', 0);
        $input     = $this->getJsonInput();

        $tributarioModel = new TributarioModel();

        try {
            $res = $tributarioModel->registrarFacturaCompra($empresaId, $usuarioId, $input);

            $detallesMsg = [];
            if (!empty($res['comprobante_iva_num'])) {
                $detallesMsg[] = 'Comprobante Ret. IVA N° ' . $res['comprobante_iva_num'];
            }
            if (!empty($res['asiento_numero'])) {
                $detallesMsg[] = 'Asiento Contable ' . $res['asiento_numero'];
            }

            $extra = !empty($detallesMsg) ? ' (' . implode(' + ', $detallesMsg) . ')' : '';

            $this->json([
                'ok'        => true,
                'message'   => 'Factura registrada en el Libro de Compras por ' . formato_ves($res['total_compra']) . $extra . '.',
                'resultado' => $res,
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Registra una factura en el Libro de Ventas (con opción de Asiento Contable Automático)
     */
    public function guardarVenta(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $usuarioId = (int)Session::get('usuario_id', 0);
        $input     = $this->getJsonInput();

        $tributarioModel = new TributarioModel();

        try {
            $res = $tributarioModel->registrarFacturaVenta($empresaId, $usuarioId, $input);
            $extra = !empty($res['asiento_numero']) ? ' (Asiento Contable ' . $res['asiento_numero'] . ' generado)' : '';

            $this->json([
                'ok'        => true,
                'message'   => 'Factura registrada en el Libro de Ventas por ' . formato_ves($res['total_venta']) . $extra . '.',
                'resultado' => $res,
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Vista de Comprobantes de Retención de IVA e ISLR (?c=tributario&a=retenciones)
     */
    public function retenciones(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $periodo = isset($_REQUEST['periodo']) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string)$_REQUEST['periodo'])
            ? (string)$_REQUEST['periodo']
            : (string)$empresaActiva['periodo_fiscal_activo'];

        $tabActiva = isset($_REQUEST['tab']) && in_array($_REQUEST['tab'], ['iva', 'islr'], true)
            ? (string)$_REQUEST['tab']
            : 'iva';

        $tributarioModel = new TributarioModel();
        $configFiscal           = $tributarioModel->obtenerConfiguracionFiscal($empresaId);
        $retencionesIva         = $tributarioModel->listarRetencionesIva($empresaId, $periodo);
        $retencionesIslr        = $tributarioModel->listarRetencionesIslr($empresaId, $periodo);
        $comprasPendientesRet   = $tributarioModel->listarComprasSinRetencionIva($empresaId);
        $conceptosIslr          = $tributarioModel->listarConceptosIslr();
        $proximoCorrelativoIva  = $tributarioModel->generarCorrelativoIva($empresaId, $periodo);
        $proximoCorrelativoIslr = $tributarioModel->generarCorrelativoIslr($empresaId, $periodo);

        $this->render('tributario/retenciones', [
            'tituloPagina'           => 'Comprobantes de Retención IVA e ISLR | ' . APP_NAME,
            'moduloActivo'           => 'tributario_retenciones',
            'empresaActiva'          => $empresaActiva,
            'configFiscal'           => $configFiscal,
            'retencionesIva'         => $retencionesIva,
            'retencionesIslr'        => $retencionesIslr,
            'comprasPendientesRet'   => $comprasPendientesRet,
            'conceptosIslr'          => $conceptosIslr,
            'proximoCorrelativoIva'  => $proximoCorrelativoIva,
            'proximoCorrelativoIslr' => $proximoCorrelativoIslr,
            'periodo'                => $periodo,
            'tabActiva'              => $tabActiva,
            'alertaFlash'            => Session::flash('alerta'),
        ]);
    }

    /**
     * Emite un Comprobante de Retención de IVA (14 dígitos AAAAMM00000001) seleccionando 1 o más facturas
     * del Libro de Compras de un mismo proveedor.
     */
    public function emitirRetencionIva(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $input = $this->getJsonInput();

        $fechaEmision  = trim((string)($input['fecha_emision'] ?? date('Y-m-d')));
        $periodoFiscal = trim((string)($input['periodo_fiscal'] ?? substr($fechaEmision, 0, 7)));
        $porcentaje    = (float)($input['porcentaje_retencion'] ?? 75);
        $direccionProv = trim((string)($input['direccion_proveedor'] ?? ''));
        $idsCompras    = is_array($input['compras_ids'] ?? null) ? $input['compras_ids'] : [];

        $tributarioModel = new TributarioModel();

        try {
            $res = $tributarioModel->emitirComprobanteIvaDesdeCompras(
                $empresaId,
                $fechaEmision,
                $periodoFiscal,
                $porcentaje,
                $direccionProv,
                $idsCompras
            );

            $this->json([
                'ok'          => true,
                'message'     => sprintf(
                    'Comprobante de Retención de IVA N° %s emitido por %s.',
                    $res['numero_comprobante'],
                    formato_ves($res['monto_retenido'])
                ),
                'comprobante' => $res,
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Emite un Comprobante de Retención de ISLR (Decreto 1.808) calculando el Sustraendo según UT
     */
    public function emitirRetencionIslr(): void
    {
        $this->requireRole(['admin', 'contador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método HTTP no permitido.'], 405);
        }

        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $input     = $this->getJsonInput();

        $tributarioModel = new TributarioModel();

        try {
            $res = $tributarioModel->emitirComprobanteIslr($empresaId, $input);

            $this->json([
                'ok'          => true,
                'message'     => sprintf(
                    'Comprobante de Retención ISLR N° %s emitido por %s (Sustraendo aplicado: %s).',
                    $res['numero_comprobante'],
                    formato_ves($res['monto_retenido']),
                    formato_ves($res['sustraendo'])
                ),
                'comprobante' => $res,
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Vista de Impresión Oficial del Comprobante de Retención de IVA (?c=tributario&a=comprobanteIva&id=...)
     */
    public function comprobanteIva(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $id = (int)($_GET['id'] ?? 0);

        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        $tributarioModel = new TributarioModel();
        $comprobante = $tributarioModel->obtenerComprobanteIvaConFacturas($id, $empresaId);

        if (!$empresaActiva || !$comprobante) {
            $this->redirect('tributario', 'retenciones');
        }

        $this->render('tributario/comprobante_iva_print', [
            'tituloPagina'  => 'Comprobante IVA ' . $comprobante['numero_comprobante'],
            'empresaActiva' => $empresaActiva,
            'comprobante'   => $comprobante,
        ], false);
    }

    /**
     * Vista de Impresión Oficial del Comprobante de Retención de ISLR (?c=tributario&a=comprobanteIslr&id=...)
     */
    public function comprobanteIslr(): void
    {
        $this->requireAuth();

        $empresaId = Session::getEmpresaActivaId();
        $id = (int)($_GET['id'] ?? 0);

        $empresaModel = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        $tributarioModel = new TributarioModel();
        $comprobante = $tributarioModel->obtenerComprobanteIslr($id, $empresaId);

        if (!$empresaActiva || !$comprobante) {
            $this->redirect('tributario', 'retenciones', ['tab' => 'islr']);
        }

        $this->render('tributario/comprobante_islr_print', [
            'tituloPagina'  => 'Comprobante ISLR ' . $comprobante['numero_comprobante'],
            'empresaActiva' => $empresaActiva,
            'comprobante'   => $comprobante,
        ], false);
    }

    public function librosPrint(): void
    {
        $this->requireAuth();
        $empresaId     = Session::getEmpresaActivaId();
        $empresaModel  = new EmpresaModel();
        $empresaActiva = $empresaModel->obtenerPorId($empresaId);

        if (!$empresaActiva) {
            $this->redirect('empresa', 'index');
        }

        $periodo = isset($_GET['periodo']) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string)$_GET['periodo'])
            ? (string)$_GET['periodo']
            : (string)$empresaActiva['periodo_fiscal_activo'];
        $tab = isset($_GET['tab']) && in_array($_GET['tab'], ['compras', 'ventas'], true)
            ? (string)$_GET['tab']
            : 'compras';

        $tributarioModel = new TributarioModel();
        $libroCompras    = $tributarioModel->listarLibroCompras($empresaId, $periodo);
        $libroVentas     = $tributarioModel->listarLibroVentas($empresaId, $periodo);

        $this->render('tributario/libros_print', [
            'empresa'       => $empresaActiva,
            'empresaActiva' => $empresaActiva,
            'periodo'       => $periodo,
            'tab'           => $tab,
            'libroCompras'  => $libroCompras,
            'libroVentas'   => $libroVentas,
        ], false);
    }

    public function actualizarRetencionIva(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $payload   = $this->getJsonInput();
        if (empty($payload)) {
            $payload = $_POST;
        }
        $id = (int)($payload['id'] ?? 0);

        try {
            $tributarioModel = new TributarioModel();
            $res = $tributarioModel->actualizarRetencionIva($id, $empresaId, $payload);
            $this->json([
                'ok'      => true,
                'message' => 'Comprobante de Retención IVA actualizado (Retenido: ' . formato_ves($res['monto_iva_retenido']) . ').',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function actualizarRetencionIslr(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $payload   = $this->getJsonInput();
        if (empty($payload)) {
            $payload = $_POST;
        }
        $id = (int)($payload['id'] ?? 0);

        try {
            $tributarioModel = new TributarioModel();
            $res = $tributarioModel->actualizarRetencionIslr($id, $empresaId, $payload);
            $this->json([
                'ok'      => true,
                'message' => 'Comprobante de Retención ISLR actualizado (Retenido: ' . formato_ves($res['monto_retenido']) . ').',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function eliminarRetencionIva(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $payload   = $this->getJsonInput();
        $id        = (int)($payload['id'] ?? $_POST['id'] ?? 0);

        try {
            $tributarioModel = new TributarioModel();
            $tributarioModel->eliminarRetencionIva($id, $empresaId);
            $this->json(['ok' => true, 'message' => 'Comprobante de Retención de IVA eliminado correctamente.']);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function eliminarRetencionIslr(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'contador']);
        $this->requirePost();
        $this->requireCsrf();

        $empresaId = Session::getEmpresaActivaId();
        $payload   = $this->getJsonInput();
        $id        = (int)($payload['id'] ?? $_POST['id'] ?? 0);

        try {
            $tributarioModel = new TributarioModel();
            $tributarioModel->eliminarRetencionIslr($id, $empresaId);
            $this->json(['ok' => true, 'message' => 'Comprobante de Retención de ISLR eliminado correctamente.']);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Exporta el Libro de Compras + Resumen Artículo 72 a CSV/Excel
     */
    private function exportarLibroComprasCsv(array $empresa, array $compras, array $resumen72, string $periodo): never
    {
        $filename = sprintf('Libro_Compras_%s_%s.csv', $empresa['rif'], $periodo);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['NOMBRE DEL CONTRIBUYENTE: ' . $empresa['razon_social']], ';');
        fputcsv($out, ['RIF: ' . $empresa['rif'] . ' | PERIODO FISCAL: ' . $periodo], ';');
        fputcsv($out, ['LIBRO AUXILIAR DE COMPRAS SENIAT'], ';');
        fputcsv($out, [], ';');

        fputcsv($out, [
            'NRO', 'FECHA FACTURA', 'RIF PROVEEDOR', 'NOMBRE O RAZON SOCIAL',
            'NRO FACTURA', 'NRO CONTROL', 'NRO NOTA CREDITO', 'NRO NOTA DEBITO',
            'TIPO TRANSACCION', 'NRO FACTURA AFECTADA', 'TOTAL COMPRAS CON IVA',
            'COMPRAS EXENTAS / SIN DERECHO CREDITO', 'BASE IMPONIBLE GENERAL (16%)',
            'IMPUESTO IVA GENERAL (16%)', 'BASE IMPONIBLE REDUCIDA (8%)', 'IMPUESTO IVA REDUCIDO (8%)',
            'BASE IGTF', 'IMPUESTO IGTF (3%)', 'NRO COMPROBANTE RET IVA', 'IVA RETENIDO'
        ], ';');

        foreach ($compras as $i => $row) {
            fputcsv($out, [
                $i + 1,
                $row['fecha_factura'],
                $row['rif_proveedor'],
                $row['nombre_proveedor'],
                $row['numero_factura'],
                $row['numero_control'],
                $row['numero_nota_credito'] ?? '',
                $row['numero_nota_debito'] ?? '',
                $row['tipo_transaccion'] ?? '01-Reg',
                $row['numero_factura_afectada'] ?? '',
                number_format((float)$row['total_compra_con_iva'], 2, ',', '.'),
                number_format((float)$row['compras_exentas'], 2, ',', '.'),
                number_format((float)$row['base_imponible_general'], 2, ',', '.'),
                number_format((float)$row['monto_iva_general'], 2, ',', '.'),
                number_format((float)$row['base_imponible_reducida'], 2, ',', '.'),
                number_format((float)$row['monto_iva_reducida'], 2, ',', '.'),
                number_format((float)($row['base_igtf'] ?? 0), 2, ',', '.'),
                number_format((float)($row['monto_igtf'] ?? 0), 2, ',', '.'),
                $row['comprobante_iva_numero'] ?? '',
                number_format((float)($row['monto_iva_retenido'] ?? 0), 2, ',', '.'),
            ], ';');
        }

        fputcsv($out, [], ';');
        fputcsv($out, ['RESUMEN ARTICULO 72 DEL REGLAMENTO DE LA LEY DEL IVA (CREDITOS FISCALES)'], ';');
        fputcsv($out, ['CONCEPTO', 'CASILLA FORMA 30', 'BASE IMPONIBLE', 'CREDITO FISCAL'], ';');
        $cr = $resumen72['creditos'];
        fputcsv($out, ['Compras No Gravadas y/o Sin Derecho a Crédito Fiscal', '30', number_format($cr['casilla_30_no_gravadas'], 2, ',', '.'), '0,00'], ';');
        fputcsv($out, ['Compras Internas Gravadas por Alícuota General (16%)', '33', number_format($cr['casilla_33_base_general'], 2, ',', '.'), number_format($cr['casilla_33_cred_general'], 2, ',', '.')], ';');
        fputcsv($out, ['Compras Internas Gravadas por Alícuota General más Adicional (31%)', '332', number_format($cr['casilla_332_base_adic'], 2, ',', '.'), number_format($cr['casilla_332_cred_adic'], 2, ',', '.')], ';');
        fputcsv($out, ['Compras Internas Gravadas por Alícuota Reducida (8%)', '333', number_format($cr['casilla_333_base_red'], 2, ',', '.'), number_format($cr['casilla_333_cred_red'], 2, ',', '.')], ';');
        fputcsv($out, ['Total Compras y Créditos Fiscales del Período', '35', number_format($cr['casilla_35_total_base'], 2, ',', '.'), number_format($cr['casilla_35_cred_periodo'], 2, ',', '.')], ';');

        fclose($out);
        exit;
    }

    /**
     * Exporta el Libro de Ventas + Resumen Artículo 72 a CSV/Excel
     */
    private function exportarLibroVentasCsv(array $empresa, array $ventas, array $resumen72, string $periodo): never
    {
        $filename = sprintf('Libro_Ventas_%s_%s.csv', $empresa['rif'], $periodo);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['NOMBRE DEL CONTRIBUYENTE: ' . $empresa['razon_social']], ';');
        fputcsv($out, ['RIF: ' . $empresa['rif'] . ' | PERIODO FISCAL: ' . $periodo], ';');
        fputcsv($out, ['LIBRO AUXILIAR DE VENTAS SENIAT'], ';');
        fputcsv($out, [], ';');

        fputcsv($out, [
            'NRO', 'FECHA FACTURA', 'RIF CLIENTE', 'NOMBRE O RAZON SOCIAL', 'CONDICION CLIENTE',
            'NRO FACTURA', 'NRO CONTROL', 'NRO NOTA CREDITO', 'NRO NOTA DEBITO',
            'TIPO TRANSACCION', 'NRO FACTURA AFECTADA', 'TOTAL VENTAS CON IVA',
            'VENTAS EXENTAS / NO GRAVADAS', 'BASE IMPONIBLE GENERAL (16%)',
            'DEBITO FISCAL GENERAL (16%)', 'BASE IMPONIBLE REDUCIDA (8%)', 'DEBITO FISCAL REDUCIDO (8%)',
            'IMPUESTO IGTF (3%)', 'COMPROBANTE RET CLIENTE', 'IVA RETENIDO POR CLIENTE'
        ], ';');

        foreach ($ventas as $i => $row) {
            fputcsv($out, [
                $i + 1,
                $row['fecha_factura'],
                $row['rif_cliente'],
                $row['nombre_cliente'],
                $row['tipo_contribuyente_cliente'],
                $row['numero_factura'],
                $row['numero_control'],
                $row['numero_nota_credito'] ?? '',
                $row['numero_nota_debito'] ?? '',
                $row['tipo_transaccion'] ?? '01-Reg',
                $row['numero_factura_afectada'] ?? '',
                number_format((float)$row['total_venta_con_iva'], 2, ',', '.'),
                number_format((float)$row['ventas_exentas'], 2, ',', '.'),
                number_format((float)$row['base_imponible_general'], 2, ',', '.'),
                number_format((float)$row['monto_iva_general'], 2, ',', '.'),
                number_format((float)($row['base_imponible_reducida'] ?? 0), 2, ',', '.'),
                number_format((float)($row['monto_iva_reducida'] ?? 0), 2, ',', '.'),
                number_format((float)$row['monto_igtf'], 2, ',', '.'),
                $row['numero_comprobante_ret_cliente'] ?? '',
                number_format((float)$row['iva_retenido_por_cliente'], 2, ',', '.'),
            ], ';');
        }

        fputcsv($out, [], ';');
        fputcsv($out, ['RESUMEN ARTICULO 72 DEL REGLAMENTO DE LA LEY DEL IVA (DEBITOS FISCALES)'], ';');
        fputcsv($out, ['CONCEPTO', 'CASILLA FORMA 30', 'BASE IMPONIBLE', 'DEBITO FISCAL'], ';');
        $db = $resumen72['debitos'];
        fputcsv($out, ['Ventas Internas No Gravadas', '40', number_format($db['casilla_40_no_gravadas'], 2, ',', '.'), '0,00'], ';');
        fputcsv($out, ['Ventas Internas Gravadas por Alícuota General (16%)', '42', number_format($db['casilla_42_base_general'], 2, ',', '.'), number_format($db['casilla_42_deb_general'], 2, ',', '.')], ';');
        fputcsv($out, ['Total Ventas y Débitos Fiscales', '46 / 49', number_format($db['casilla_46_total_base'], 2, ',', '.'), number_format($db['casilla_49_total_debito'], 2, ',', '.')], ';');

        fclose($out);
        exit;
    }
}
