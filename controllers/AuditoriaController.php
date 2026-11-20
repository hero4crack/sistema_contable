<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Models\AuditoriaModel;

/**
 * Controlador del Módulo de Auditoría y Bitácora Global del Sistema
 */
final class AuditoriaController extends Controller
{
    /**
     * Panel principal de auditoría con filtros, resumen KPI y detalle de eventos
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin']);

        $filtros = $this->extraerFiltros();
        $page    = max(1, (int)($_GET['page'] ?? 1));

        $model     = new AuditoriaModel();
        $resultado = $model->listar($filtros, $page, 50);
        $resumen   = $model->obtenerResumen($filtros);
        $opciones  = $model->listarOpcionesFiltros();

        $this->render('auditoria/index', [
            'tituloPagina' => 'Auditoría y Bitácora del Sistema | ' . APP_NAME,
            'moduloActivo' => 'auditoria',
            'filtros'      => $filtros,
            'resultado'    => $resultado,
            'resumen'      => $resumen,
            'opciones'     => $opciones,
        ]);
    }

    /**
     * Exporta los registros filtrados a CSV compatible con Excel
     */
    public function exportarCsv(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin']);

        $filtros = $this->extraerFiltros();
        $model   = new AuditoriaModel();
        $rows    = $model->listarParaExportar($filtros, 5000);

        $filename = 'auditoria_juris_contable_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            exit;
        }

        // BOM UTF-8 para Excel
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'ID',
            'Fecha y Hora',
            'Usuario',
            'Email',
            'Rol',
            'Empresa Activa',
            'Módulo',
            'Tipo Evento',
            'Método HTTP',
            'Controlador',
            'Acción',
            'Descripción',
            'ID Entidad',
            'Código HTTP',
            'IP',
        ], ';');

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['created_at'],
                $r['usuario_nombre'],
                $r['usuario_email'] ?? '',
                $r['usuario_rol'] ?? '',
                $r['empresa_nombre'] ?? '',
                $r['modulo'],
                $r['tipo_evento'],
                $r['metodo_http'],
                $r['controlador'],
                $r['accion'],
                $r['descripcion'],
                $r['entidad_id'] ?? '',
                $r['codigo_http'],
                $r['ip_address'] ?? '',
            ], ';');
        }

        fclose($out);
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    private function extraerFiltros(): array
    {
        return [
            'q'           => trim((string)($_GET['q'] ?? '')),
            'usuario_id'  => (int)($_GET['usuario_id'] ?? 0),
            'empresa_id'  => (int)($_GET['empresa_id'] ?? 0),
            'modulo'      => trim((string)($_GET['modulo'] ?? '')),
            'tipo_evento' => trim((string)($_GET['tipo_evento'] ?? '')),
            'fecha_desde' => trim((string)($_GET['fecha_desde'] ?? '')),
            'fecha_hasta' => trim((string)($_GET['fecha_hasta'] ?? '')),
        ];
    }
}
