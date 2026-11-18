<?php
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

$csrfToken = Csrf::getToken();
$usuarioNombre = (string)Session::get('usuario_nombre', 'Usuario');
$usuarioRol    = (string)Session::get('usuario_rol', 'contador');
$empresaActivaId = (int)($empresaActiva['id'] ?? Session::getEmpresaActivaId());
$empresaActivaRif = (string)($empresaActiva['rif'] ?? Session::get('empresa_activa_rif', 'N/A'));
$empresaActivaTipo = (string)($empresaActiva['tipo_contribuyente'] ?? Session::get('empresa_activa_tipo', 'Ordinario'));
$empresaActivaPeriodo = (string)($empresaActiva['periodo_fiscal_activo'] ?? Session::get('empresa_activa_periodo', date('Y-m')));
$empresaActivaRetIva = (string)($empresaActiva['porcentaje_retencion_iva'] ?? Session::get('empresa_activa_ret_iva', '75'));
?>
<!DOCTYPE html>
<html lang="es" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">
    <title><?= e($tituloPagina ?? APP_NAME) ?></title>

    <!-- Inicialización temprana de Tema (Evita parpadeo FOUC al cambiar de vista) -->
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('theme') || 'dark';
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <!-- Tipografías: Inter (UI) y JetBrains Mono (Cifras contables y RIF) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN con darkMode: 'class' -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        juris: {
                            950: '#070b14',
                            900: '#0f172a',
                            800: '#1e293b',
                            700: '#334155',
                            accent: '#10b981',
                            gold: '#f59e0b'
                        }
                    }
                }
            }
        };
    </script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        html.dark ::-webkit-scrollbar-track { background: #0f172a; }
        html.dark ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        html:not(.dark) ::-webkit-scrollbar-track { background: #f1f5f9; }
        html:not(.dark) ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

        .sidebar-collapsed { width: 4.5rem !important; }
        .sidebar-collapsed .sidebar-text,
        .sidebar-collapsed .sidebar-section-label,
        .sidebar-collapsed .sidebar-badge { display: none !important; }
        .sidebar-collapsed .sidebar-item { justify-content: center; padding-left: 0.75rem; padding-right: 0.75rem; }

        /* Adaptación automática de alto contraste para Modo Claro (html:not(.dark)) */
        html:not(.dark) body { background-color: #f8fafc !important; color: #0f172a !important; }
        html:not(.dark) main.bg-slate-950 { background-color: #f1f5f9 !important; color: #0f172a !important; }
        html:not(.dark) #appSidebar { background-color: #ffffff !important; border-color: #e2e8f0 !important; }
        html:not(.dark) header.bg-slate-900\/95 { background-color: rgba(255, 255, 255, 0.95) !important; border-color: #e2e8f0 !important; }
        html:not(.dark) footer.bg-slate-900\/90 { background-color: #ffffff !important; border-color: #e2e8f0 !important; color: #475569 !important; }
        html:not(.dark) .bg-slate-900,
        html:not(.dark) .bg-slate-900\/90,
        html:not(.dark) .bg-slate-900\/60 { background-color: #ffffff !important; }
        html:not(.dark) .from-slate-900 {
            --tw-gradient-from: #ffffff !important;
            --tw-gradient-to: #f8fafc !important;
            --tw-gradient-stops: var(--tw-gradient-from), #f8fafc, var(--tw-gradient-to) !important;
        }
        html:not(.dark) .bg-slate-950,
        html:not(.dark) .bg-slate-950\/90,
        html:not(.dark) .bg-slate-950\/80,
        html:not(.dark) .bg-slate-950\/70,
        html:not(.dark) .bg-slate-950\/60,
        html:not(.dark) .bg-slate-950\/50,
        html:not(.dark) .bg-slate-950\/40 { background-color: #f8fafc !important; }
        html:not(.dark) .fixed.bg-slate-950\/80 { background-color: rgba(15, 23, 42, 0.55) !important; }
        html:not(.dark) .bg-slate-800 { background-color: #f1f5f9 !important; }
        html:not(.dark) .hover\:bg-slate-800\/70:hover,
        html:not(.dark) .hover\:bg-slate-800\/50:hover,
        html:not(.dark) .hover\:bg-slate-800\/40:hover,
        html:not(.dark) .hover\:bg-slate-800:hover,
        html:not(.dark) .hover\:bg-slate-700:hover { background-color: #e2e8f0 !important; }
        html:not(.dark) .border-slate-800,
        html:not(.dark) .border-slate-800\/90,
        html:not(.dark) .border-slate-800\/80,
        html:not(.dark) .border-slate-700,
        html:not(.dark) .border-slate-700\/80,
        html:not(.dark) .border-slate-700\/90 { border-color: #cbd5e1 !important; }
        html:not(.dark) .divide-slate-800 > :not([hidden]) ~ :not([hidden]),
        html:not(.dark) .divide-slate-800\/80 > :not([hidden]) ~ :not([hidden]),
        html:not(.dark) .divide-slate-800\/70 > :not([hidden]) ~ :not([hidden]),
        html:not(.dark) .divide-slate-800\/60 > :not([hidden]) ~ :not([hidden]) { border-color: #e2e8f0 !important; }
        html:not(.dark) .text-white,
        html:not(.dark) .text-slate-100,
        html:not(.dark) .text-slate-200 { color: #0f172a !important; }
        html:not(.dark) .text-slate-300 { color: #334155 !important; }
        html:not(.dark) .text-slate-400 { color: #475569 !important; }
        html:not(.dark) .text-emerald-400,
        html:not(.dark) .text-emerald-300 { color: #047857 !important; }
        html:not(.dark) .text-teal-400,
        html:not(.dark) .text-teal-300 { color: #0f766e !important; }
        html:not(.dark) .text-amber-300,
        html:not(.dark) .text-amber-400 { color: #b45309 !important; }
        html:not(.dark) .text-sky-300,
        html:not(.dark) .text-sky-400 { color: #0369a1 !important; }
        html:not(.dark) .text-rose-300,
        html:not(.dark) .text-rose-400 { color: #be123c !important; }
        html:not(.dark) .text-indigo-300,
        html:not(.dark) .text-indigo-400 { color: #4338ca !important; }
        /* Conservar texto blanco en botones de acción con gradiente o fondo sólido */
        html:not(.dark) .from-emerald-600,
        html:not(.dark) .from-emerald-500,
        html:not(.dark) .bg-emerald-600,
        html:not(.dark) .bg-emerald-600 * { color: #ffffff !important; }

        /* Estilos limpios para Impresión Oficial de Libros Legales y Estados Financieros */
        @media print {
            body, html, main { background: #ffffff !important; color: #000000 !important; overflow: visible !important; height: auto !important; }
            #appSidebar, header, footer, .no-print { display: none !important; }
            .flex.h-screen.w-screen { display: block !important; height: auto !important; width: 100% !important; overflow: visible !important; }
            .flex-1.flex.flex-col.h-screen { display: block !important; height: auto !important; overflow: visible !important; }
            main { padding: 0 !important; margin: 0 !important; overflow: visible !important; }
            .print-container { border: none !important; box-shadow: none !important; background: #ffffff !important; color: #000000 !important; }
            .print-only { display: block !important; }
            table { width: 100% !important; border-collapse: collapse !important; font-size: 11px !important; }
            th, td { border-bottom: 1px solid #cbd5e1 !important; color: #000000 !important; padding: 6px 8px !important; }
        }
    </style>
</head>
<body class="h-full font-sans text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-950 antialiased selection:bg-emerald-500 selection:text-white overflow-hidden transition-colors duration-200">

<div class="flex h-screen w-screen overflow-hidden">
