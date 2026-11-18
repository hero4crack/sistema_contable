<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Models\RespaldoModel;
use Throwable;

/**
 * Controlador de Respaldo y Restauración de Base de Datos (Solo Admin)
 */
final class RespaldoController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['admin']);

        $respaldoModel = new RespaldoModel();
        $resumenBd     = $respaldoModel->obtenerResumenBaseDatos();
        $respaldos     = $respaldoModel->listarRespaldos();

        $this->render('respaldo/index', [
            'tituloPagina' => 'Respaldo de Base de Datos | ' . APP_NAME,
            'moduloActivo' => 'respaldo',
            'resumenBd'    => $resumenBd,
            'respaldos'    => $respaldos,
        ]);
    }

    public function generar(): void
    {
        $this->requireRole(['admin']);
        $this->requirePost();
        $this->requireCsrf();

        try {
            $respaldoModel = new RespaldoModel();
            $resultado     = $respaldoModel->generarRespaldoSql();
            $this->json([
                'ok'        => true,
                'resultado' => $resultado,
                'message'   => sprintf(
                    'Respaldo %s generado correctamente (%d tablas respaldadas).',
                    $resultado['archivo'],
                    $resultado['tablas']
                ),
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function descargar(): void
    {
        $this->requireRole(['admin']);
        $this->requirePost();
        $this->requireCsrf();

        $archivo = (string)($_POST['archivo'] ?? '');

        try {
            $respaldoModel = new RespaldoModel();
            $ruta          = $respaldoModel->obtenerRutaRespaldoSeguro($archivo);

            header('Content-Description: File Transfer');
            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . basename($ruta) . '"');
            header('Content-Length: ' . (string)filesize($ruta));
            header('Cache-Control: no-store, no-cache, must-revalidate');
            readfile($ruta);
            exit;
        } catch (Throwable $e) {
            http_response_code(404);
            exit($e->getMessage());
        }
    }

    public function eliminar(): void
    {
        $this->requireRole(['admin']);
        $this->requirePost();
        $this->requireCsrf();

        $payload = $this->getJsonInput();
        $archivo = (string)($payload['archivo'] ?? $_POST['archivo'] ?? '');

        try {
            $respaldoModel = new RespaldoModel();
            $respaldoModel->eliminarRespaldo($archivo);
            $this->json([
                'ok'      => true,
                'message' => 'Archivo de respaldo eliminado del servidor.',
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function restaurar(): void
    {
        $this->requireRole(['admin']);
        $this->requirePost();
        $this->requireCsrf();

        $respaldoModel = new RespaldoModel();

        try {
            $archivoServidor = trim((string)($_POST['archivo'] ?? ''));

            if ($archivoServidor !== '') {
                $ruta = $respaldoModel->obtenerRutaRespaldoSeguro($archivoServidor);
            } elseif (!empty($_FILES['archivo_sql']) && (int)($_FILES['archivo_sql']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo((string)$_FILES['archivo_sql']['name'], PATHINFO_EXTENSION));
                if ($ext !== 'sql') {
                    $this->json(['ok' => false, 'message' => 'Solo se admiten archivos con extensión .sql.'], 422);
                }
                $ruta = (string)$_FILES['archivo_sql']['tmp_name'];
            } else {
                $this->json(['ok' => false, 'message' => 'Debe seleccionar un respaldo o subir un archivo .sql válido.'], 422);
            }

            $sentencias = $respaldoModel->restaurarDesdeArchivo($ruta);
            $this->json([
                'ok'      => true,
                'message' => "Base de datos restaurada correctamente ({$sentencias} sentencias SQL ejecutadas).",
            ]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
