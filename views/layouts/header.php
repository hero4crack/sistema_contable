<?php
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

$csrfToken            = Csrf::getToken();
$usuarioNombre        = (string)Session::get('usuario_nombre', 'Usuario');
$usuarioRol           = (string)Session::get('usuario_rol', 'contador');
$empresaActivaId      = (int)($empresaActiva['id'] ?? Session::getEmpresaActivaId());
$empresaActivaRif     = (string)($empresaActiva['rif'] ?? Session::get('empresa_activa_rif', 'N/A'));
$empresaActivaTipo    = (string)($empresaActiva['tipo_contribuyente'] ?? Session::get('empresa_activa_tipo', 'Ordinario'));
$empresaActivaPeriodo = (string)($empresaActiva['periodo_fiscal_activo'] ?? Session::get('empresa_activa_periodo', date('Y-m')));
$empresaActivaRetIva  = (string)($empresaActiva['porcentaje_retencion_iva'] ?? Session::get('empresa_activa_ret_iva', '75'));
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">
    <title><?= e($tituloPagina ?? APP_NAME) ?></title>

    <!-- Tailwind CSS Local (Sin dependencia de internet - Modo Claro Blanco y Verde) -->
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Segoe UI"', 'system-ui', '-apple-system', 'Roboto', 'sans-serif'],
                        mono: ['"Cascadia Code"', 'Consolas', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        juris: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b'
                        }
                    }
                }
            }
        };
    </script>

    <!-- SweetAlert2 Local -->
    <script src="<?= e(base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js')) ?>"></script>

    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .sidebar-collapsed { width: 4.5rem !important; }
        .sidebar-collapsed .sidebar-text,
        .sidebar-collapsed .sidebar-section-label,
        .sidebar-collapsed .sidebar-badge { display: none !important; }
        .sidebar-collapsed .sidebar-item { justify-content: center; padding-left: 0.75rem; padding-right: 0.75rem; }

        /* Paleta Institucional Modo Claro (Blanco / Gris Suave / Verde Esmeralda #047857) */
        body { background-color: #f8fafc !important; color: #0f172a !important; }
        main { background-color: #f8fafc !important; color: #0f172a !important; }
        #appSidebar { background-color: #ffffff !important; border-color: #e2e8f0 !important; }
        header { background-color: #ffffff !important; border-color: #e2e8f0 !important; }
        footer { background-color: #ffffff !important; border-color: #e2e8f0 !important; color: #475569 !important; }

        .bg-slate-900,
        .bg-slate-900\/95,
        .bg-slate-900\/90,
        .bg-slate-900\/60 { background-color: #ffffff !important; }

        .from-slate-900 {
            --tw-gradient-from: #ffffff !important;
            --tw-gradient-to: #f8fafc !important;
            --tw-gradient-stops: var(--tw-gradient-from), #f8fafc, var(--tw-gradient-to) !important;
        }

        .bg-slate-950,
        .bg-slate-950\/90,
        .bg-slate-950\/80,
        .bg-slate-950\/70,
        .bg-slate-950\/60,
        .bg-slate-950\/50,
        .bg-slate-950\/40,
        .bg-slate-950\/30 { background-color: #f8fafc !important; }

        .fixed.bg-slate-950\/80,
        .fixed.bg-slate-950\/75 { background-color: rgba(15, 23, 42, 0.45) !important; }

        .bg-slate-800,
        .bg-slate-800\/90 { background-color: #f1f5f9 !important; }
        .hover\:bg-slate-800\/70:hover,
        .hover\:bg-slate-800\/60:hover,
        .hover\:bg-slate-800\/50:hover,
        .hover\:bg-slate-800\/40:hover,
        .hover\:bg-slate-800:hover,
        .hover\:bg-slate-700:hover { background-color: #ecfdf5 !important; }

        .border-slate-800,
        .border-slate-800\/90,
        .border-slate-800\/80,
        .border-slate-700,
        .border-slate-700\/80,
        .border-slate-700\/90 { border-color: #e2e8f0 !important; }

        .divide-slate-800 > :not([hidden]) ~ :not([hidden]),
        .divide-slate-800\/80 > :not([hidden]) ~ :not([hidden]),
        .divide-slate-800\/70 > :not([hidden]) ~ :not([hidden]),
        .divide-slate-800\/60 > :not([hidden]) ~ :not([hidden]) { border-color: #e2e8f0 !important; }

        .text-white,
        .text-slate-100,
        .text-slate-200 { color: #0f172a !important; }
        .text-slate-300 { color: #334155 !important; }
        .text-slate-400 { color: #475569 !important; }
        .text-emerald-400,
        .text-emerald-300 { color: #047857 !important; }
        .text-teal-400,
        .text-teal-300 { color: #0f766e !important; }
        .text-amber-300,
        .text-amber-400 { color: #b45309 !important; }
        .text-sky-300,
        .text-sky-400 { color: #0369a1 !important; }
        .text-rose-300,
        .text-rose-400 { color: #be123c !important; }
        .text-indigo-300,
        .text-indigo-400 { color: #4338ca !important; }

        /* Botones primarios y acentos en Verde Institucional (#047857 / #065f46) */
        .from-emerald-600,
        .from-emerald-500,
        .bg-emerald-600,
        .bg-emerald-700,
        .bg-amber-600 { background-color: #047857 !important; color: #ffffff !important; }
        .bg-emerald-600:hover,
        .bg-emerald-700:hover { background-color: #065f46 !important; }
        .from-emerald-600 *,
        .from-emerald-500 *,
        .bg-emerald-600 *,
        .bg-emerald-700 *,
        .bg-amber-600 * { color: #ffffff !important; }

        /* Impresión Oficial Limpia */
        @page { margin: 0mm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body, html, main { background: #ffffff !important; color: #000000 !important; overflow: visible !important; height: auto !important; padding: 8mm !important; }
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
<body class="h-full font-sans text-slate-900 bg-slate-50 antialiased selection:bg-emerald-700 selection:text-white overflow-hidden">

<div class="flex h-screen w-screen overflow-hidden">
