<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\EmpresaModel;
use Models\UsuarioModel;

/**
 * Controlador de Autenticación y Seguridad de Sesión
 */
final class AuthController extends Controller
{
    /**
     * Muestra la pantalla de inicio de sesión
     */
    public function login(): void
    {
        if (Session::isAuthenticated()) {
            $this->redirect('dashboard', 'index');
        }

        $this->render('auth/login', [
            'tituloPagina' => 'Iniciar Sesión | ' . APP_NAME,
            'errorLogin'   => Session::flash('error_login'),
        ], false);
    }

    /**
     * Procesa el formulario de inicio de sesión (POST)
     */
    public function autenticar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth', 'login');
        }

        $this->requireCsrf();

        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            Session::flash('error_login', [
                'mensaje' => 'Debe ingresar su correo electrónico y contraseña.',
            ]);
            $this->redirect('auth', 'login');
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->autenticar($email, $password);

        if ($usuario === null) {
            Session::flash('error_login', [
                'mensaje' => 'Credenciales inválidas o cuenta de usuario inactiva.',
            ]);
            $this->redirect('auth', 'login');
        }

        // Prevención de Session Fixation
        Session::regenerate();

        Session::set('usuario_id', (int)$usuario['id']);
        Session::set('usuario_nombre', (string)$usuario['nombre']);
        Session::set('usuario_email', (string)$usuario['email']);
        Session::set('usuario_rol', (string)$usuario['rol']);

        // Inicializar contexto Multi-Empresa con la primera empresa cliente activa
        $empresaModel = new EmpresaModel();
        $empresasActivas = $empresaModel->listarActivas();
        if (!empty($empresasActivas)) {
            Session::setEmpresaActiva($empresasActivas[0]);
        }

        Session::flash('alerta', [
            'tipo'    => 'success',
            'titulo'  => 'Bienvenido a Juris Contable',
            'mensaje' => 'Sesión iniciada como ' . $usuario['nombre'] . ' (' . strtoupper($usuario['rol']) . ').',
        ]);

        $this->redirect('dashboard', 'index');
    }

    /**
     * Cierra la sesión activa de forma segura
     */
    public function logout(): void
    {
        Session::destroy();
        $this->redirect('auth', 'login');
    }
}
