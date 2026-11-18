<?php
declare(strict_types=1);

use Core\Csrf;
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina ?? 'Acceso | Juris Contable') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        };
    </script>
</head>
<body class="min-h-full font-sans text-slate-100 bg-slate-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">

    <!-- Efectos de iluminación ambiental corporativa -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden z-0">
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-emerald-600/10 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-[28rem] h-[28rem] rounded-full bg-teal-600/10 blur-3xl"></div>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md z-10 px-4">
        <div class="flex flex-col items-center text-center">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center shadow-2xl shadow-emerald-950/90 border border-emerald-400/30 mb-4">
                <span class="font-mono font-bold text-2xl text-white tracking-tighter">JC</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">JURIS CONTABLE</h1>
            <p class="mt-1 text-xs font-mono uppercase tracking-widest text-emerald-400">
                Sistema Integral Multi-Empresa • SENIAT / LOTTT
            </p>
        </div>

        <div class="mt-7 bg-slate-900/90 backdrop-blur-md py-8 px-6 shadow-2xl rounded-2xl border border-slate-800 sm:px-10">
            <div class="mb-6">
                <h2 class="text-base font-semibold text-slate-100">Iniciar Sesión en el Escritorio</h2>
                <p class="text-xs text-slate-400 mt-0.5">Ingrese sus credenciales autorizadas para gestionar las empresas clientes.</p>
            </div>

            <?php if (!empty($errorLogin['mensaje'])): ?>
                <div class="mb-5 rounded-xl bg-rose-500/10 border border-rose-500/30 p-3.5 flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs font-medium text-rose-200"><?= e($errorLogin['mensaje']) ?></p>
                </div>
            <?php endif; ?>

            <form action="<?= e(route_url('auth', 'autenticar')) ?>" method="POST" class="space-y-5" autocomplete="on">
                <?= Csrf::field() ?>

                <div>
                    <label for="email" class="block text-xs font-medium text-slate-300 mb-1.5">
                        Correo Electrónico Corporativo
                    </label>
                    <div class="relative">
                        <input id="email" name="email" type="email" required autofocus
                               value="admin@juriscontable.com"
                               placeholder="admin@juriscontable.com"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700/90 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-medium text-slate-300">
                            Contraseña de Acceso
                        </label>
                        <button type="button" id="btnTogglePass"
                                class="text-[11px] font-mono text-emerald-400 hover:text-emerald-300">
                            Mostrar / Ocultar
                        </button>
                    </div>
                    <div class="relative">
                        <input id="password" name="password" type="password" required
                               value="Admin123*"
                               placeholder="••••••••••••"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700/90 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all">
                    </div>
                </div>

                <div class="pt-1">
                    <button type="submit"
                            class="w-full flex justify-center items-center gap-2 py-2.5 px-4 border border-transparent rounded-xl shadow-lg shadow-emerald-950/60 text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-emerald-500 transition-all">
                        <span>Acceder al Sistema Contable</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Tarjeta de Credenciales Seed Inicial -->
            <div class="mt-6 pt-5 border-t border-slate-800/80">
                <div class="rounded-xl bg-slate-950/90 border border-slate-800 p-3.5">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Acceso Seed Inicial (Fase 1)</span>
                        <span class="px-2 py-0.5 text-[10px] font-mono rounded bg-emerald-500/15 text-emerald-300">Rol: ADMIN</span>
                    </div>
                    <div class="text-xs font-mono text-slate-300 space-y-1">
                        <div>Usuario: <span class="text-emerald-400">admin@juriscontable.com</span></div>
                        <div>Clave: <span class="text-emerald-400">Admin123*</span></div>
                    </div>
                </div>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> • Normativa Venezolana SENIAT / LOTTT
        </p>
    </div>

    <script>
        document.getElementById('btnTogglePass')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            if (input) {
                input.type = input.type === 'password' ? 'text' : 'password';
            }
        });
    </script>
</body>
</html>
