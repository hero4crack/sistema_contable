<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\UsuarioModel;
use Throwable;

/**
 * Controlador de Gestión de Usuarios, Roles y Permisos (Exclusivo Administrador)
 * (?c=usuarios&a=index)
 */
final class UsuarioController extends Controller
{
    /**
     * Lista todos los usuarios del servidor local (?c=usuarios&a=index)
     */
    public function index(): void
    {
        $this->requireRole(['admin']);

        $usuarioModel = new UsuarioModel();
        $usuarios     = $usuarioModel->listarTodos();

        $this->render('usuarios/index', [
            'tituloPagina'    => 'Usuarios y Permisos | ' . APP_NAME,
            'moduloActivo'    => 'usuarios',
            'usuarios'        => $usuarios,
            'usuarioActualId' => (int)Session::get('usuario_id', 0),
            'alertaFlash'     => Session::flash('alerta'),
        ]);
    }

    /**
     * Obtiene los datos de un usuario por su ID (POST JSON + CSRF)
     */
    public function obtener(): void
    {
        $this->requireRole(['admin']);
        $this->requirePost();
        $this->validateCsrf();

        $input = $this->getJsonInput();
        $id    = (int)($input['id'] ?? 0);

        $usuarioModel = new UsuarioModel();
        $usuario      = $usuarioModel->obtenerPorId($id);

        if (!$usuario) {
            $this->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        $this->json([
            'ok'      => true,
            'usuario' => $usuario,
        ]);
    }

    /**
     * Crea o actualiza un usuario (admin, contador o asistente) junto con su correo, clave y permisos (POST JSON + CSRF)
     */
    public function guardar(): void
    {
        $this->requireRole(['admin']);
        $this->requirePost();
        $this->validateCsrf();

        $usuarioActualId = (int)Session::get('usuario_id', 0);
        $datos           = $this->getJsonInput();

        try {
            $usuarioModel = new UsuarioModel();
            $idGuardado   = $usuarioModel->guardarUsuario($datos, $usuarioActualId);

            // Si el admin editó su propia cuenta, refrescar los datos en sesión
            if ($idGuardado === $usuarioActualId) {
                $actualizado = $usuarioModel->obtenerPorId($usuarioActualId);
                if ($actualizado) {
                    Session::set('usuario_nombre', (string)$actualizado['nombre']);
                    Session::set('usuario_email', (string)$actualizado['email']);
                    Session::set('usuario_rol', (string)$actualizado['rol']);
                }
            }

            $this->json([
                'ok'         => true,
                'usuario_id' => $idGuardado,
                'message'    => 'Usuario y permisos guardados correctamente.',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Activa o inactiva un usuario del sistema (POST JSON + CSRF)
     */
    public function cambiarEstado(): void
    {
        $this->requireRole(['admin']);
        $this->requirePost();
        $this->validateCsrf();

        $usuarioActualId = (int)Session::get('usuario_id', 0);
        $input           = $this->getJsonInput();
        $id              = (int)($input['id'] ?? 0);
        $nuevoEstado     = (int)($input['activo'] ?? 0) === 1 ? 1 : 0;

        try {
            $usuarioModel = new UsuarioModel();
            $usuarioModel->cambiarEstado($id, $nuevoEstado, $usuarioActualId);

            $this->json([
                'ok'      => true,
                'message' => $nuevoEstado === 1 ? 'Usuario activado correctamente.' : 'Usuario inactivado correctamente.',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
