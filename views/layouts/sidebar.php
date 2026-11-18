<?php
declare(strict_types=1);

use Core\Session;

$moduloActual = $moduloActivo ?? 'dashboard';
$usuarioNombre = (string)Session::get('usuario_nombre', 'Usuario');
$usuarioEmail  = (string)Session::get('usuario_email', '');
$usuarioRol    = (string)Session::get('usuario_rol', 'contador');

$empresaActivaId      = (int)($empresaActiva['id'] ?? Session::getEmpresaActivaId());
$empresaActivaRif     = (string)($empresaActiva['rif'] ?? Session::get('empresa_activa_rif', 'SIN-RIF'));
$empresaActivaNombre  = (string)($empresaActiva['razon_social'] ?? Session::get('empresa_activa_nombre', 'Sin Empresa Seleccionada'));
$empresaActivaTipo    = (string)($empresaActiva['tipo_contribuyente'] ?? Session::get('empresa_activa_tipo', 'Ordinario'));
$empresaActivaPeriodo = (string)($empresaActiva['periodo_fiscal_activo'] ?? Session::get('empresa_activa_periodo', date('Y-m')));
$empresaActivaRetIva  = (string)($empresaActiva['porcentaje_retencion_iva'] ?? Session::get('empresa_activa_ret_iva', '75'));
$empresaActivaTasaBcv = (float)($empresaActiva['tasa_bcv_usd'] ?? Session::get('empresa_activa_tasa_bcv', 45.00));
?>
    <!-- SIDEBAR COLAPSABLE -->
    <aside id="appSidebar" class="w-72 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800/90 flex flex-col justify-between transition-all duration-300 ease-in-out z-30 shrink-0 select-none">
        <!-- Logo e Identidad -->
        <div>
            <div class="h-16 px-4 flex items-center justify-between border-b border-slate-200 dark:border-slate-800/90 bg-slate-50 dark:bg-slate-950/40">
                <a href="<?= e(route_url('dashboard', 'index')) ?>" class="flex items-center gap-3 overflow-hidden">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center shadow-lg shadow-emerald-950/40 shrink-0 border border-emerald-400/30">
                        <span class="font-mono font-bold text-lg text-white tracking-tighter">JC</span>
                    </div>
                    <div class="sidebar-text leading-tight">
                        <span class="block font-bold text-slate-900 dark:text-slate-100 tracking-tight text-base">JURIS CONTABLE</span>
                        <span class="block text-[11px] font-mono text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">SENIAT • LOTTT</span>
                    </div>
                </a>

                <button type="button" id="btnToggleSidebar" title="Colapsar / Expandir menú lateral"
                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/70 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h10"/>
                    </svg>
                </button>
            </div>

            <!-- Navegación por Módulos -->
            <nav class="p-3 space-y-5 overflow-y-auto max-h-[calc(100vh-9.5rem)]">
                <!-- MÓDULO 1: NÚCLEO Y MULTI-EMPRESA -->
                <div>
                    <p class="sidebar-section-label px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        1. Escritorio Multi-Tenant
                    </p>
                    <ul class="space-y-1">
                        <li>
                            <a href="<?= e(route_url('dashboard', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'dashboard' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'dashboard' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    <span class="sidebar-text">Dashboard Fiscal</span>
                                </div>
                                <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-700 dark:text-emerald-300">Activo</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('empresa', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'empresas' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'empresas' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <span class="sidebar-text">Empresas Clientes</span>
                                </div>
                                <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-700">
                                    <?= count($empresasSelector ?? []) ?>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- MÓDULO 2: CONTABILIDAD GENERAL Y LIBROS LEGALES (FASE 2 ACTIVA) -->
                <div>
                    <p class="sidebar-section-label px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        2. Contabilidad General
                    </p>
                    <ul class="space-y-1">
                        <li>
                            <a href="<?= e(route_url('plan_cuentas', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'plan_cuentas' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'plan_cuentas' ? 'text-emerald-600 dark:text-emerald-400' : 'text-teal-500 dark:text-teal-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                                    <span class="sidebar-text">Plan de Cuentas</span>
                                </div>
                                <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded bg-teal-500/20 text-teal-700 dark:text-teal-300">CRUD</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('contabilidad', 'diario')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'diario' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'diario' ? 'text-emerald-600 dark:text-emerald-400' : 'text-emerald-500 dark:text-emerald-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="sidebar-text">Libro Diario y Asientos</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('contabilidad', 'mayor')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'mayor' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'mayor' ? 'text-emerald-600 dark:text-emerald-400' : 'text-sky-500 dark:text-sky-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    <span class="sidebar-text">Libro Mayor Analítico</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('contabilidad', 'estados')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'estados' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'estados' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500 dark:text-amber-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <span class="sidebar-text">Estados Financieros</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- MÓDULO 3: TRIBUTARIO SENIAT (FASE 3 ACTIVA) -->
                <div>
                    <p class="sidebar-section-label px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        3. Tributario (SENIAT)
                    </p>
                    <ul class="space-y-1">
                        <li>
                            <a href="<?= e(route_url('tributario', 'libros')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'tributario_libros' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'tributario_libros' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500 dark:text-amber-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span class="sidebar-text">Compras y Ventas</span>
                                </div>
                                <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-700 dark:text-amber-300">IVA</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('tributario', 'retenciones')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'tributario_retenciones' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'tributario_retenciones' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500 dark:text-rose-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                    </svg>
                                    <span class="sidebar-text">Retenciones IVA / ISLR</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- MÓDULO 4: NÓMINA Y LOTTT (FASE 4 ACTIVA) -->
                <div>
                    <p class="sidebar-section-label px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        4. Laboral (LOTTT)
                    </p>
                    <ul class="space-y-1">
                        <li>
                            <a href="<?= e(route_url('nomina', 'trabajadores')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'nomina_trabajadores'
                                   ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 shadow-sm'
                                   : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'nomina_trabajadores' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="sidebar-text">Directorio Trabajadores</span>
                                </div>
                            </a>
                        </li>

                        <li>
                            <a href="<?= e(route_url('nomina', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'nomina_index'
                                   ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 shadow-sm'
                                   : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'nomina_index' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="sidebar-text">Nómina Quincenal / Mes</span>
                                </div>
                            </a>
                        </li>

                        <li>
                            <a href="<?= e(route_url('nomina', 'cestaticket')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'nomina_cestaticket'
                                   ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 shadow-sm'
                                   : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'nomina_cestaticket' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="sidebar-text">Bono de Alimentación</span>
                                </div>
                            </a>
                        </li>

                        <li>
                            <a href="<?= e(route_url('nomina', 'recibos')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'nomina_recibos'
                                   ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 shadow-sm'
                                   : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'nomina_recibos' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    <span class="sidebar-text">Recibos de Pago LOTTT</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- MÓDULO 5: ADMINISTRACIÓN Y DOCUMENTACIÓN -->
                <div>
                    <p class="sidebar-section-label px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        5. Administración y Ayuda
                    </p>
                    <ul class="space-y-1">
                        <?php if ($usuarioRol === 'admin'): ?>
                            <li>
                                <a href="<?= e(route_url('usuarios', 'index')) ?>"
                                   class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'usuarios'
                                       ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 shadow-sm'
                                       : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100' ?>">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'usuarios' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <span class="sidebar-text">Usuarios y Permisos</span>
                                    </div>
                                    <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-600 dark:text-amber-300">Admin</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?= e(route_url('manual', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'manual'
                                   ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 shadow-sm'
                                   : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'manual' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span class="sidebar-text">Manual de Usuario</span>
                                </div>
                                <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">Guía</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <!-- Pie del Sidebar: Usuario en Sesión -->
        <div class="p-3 border-t border-slate-200 dark:border-slate-800/90 bg-slate-50 dark:bg-slate-950/60">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-9 h-9 rounded-full bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 flex items-center justify-center font-bold text-xs text-emerald-700 dark:text-emerald-400 shrink-0">
                        <?= e(mb_strtoupper(mb_substr($usuarioNombre, 0, 2))) ?>
                    </div>
                    <div class="sidebar-text truncate">
                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate"><?= e($usuarioNombre) ?></p>
                        <p class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 uppercase"><?= e($usuarioRol) ?></p>
                    </div>
                </div>
                <a href="<?= e(route_url('auth', 'logout')) ?>"
                   title="Cerrar sesión"
                   class="sidebar-text p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-500/10 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- CONTENEDOR DERECHO: TOPBAR MULTI-TENANT + CONTENIDO -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-950">
        <!-- HEADER SUPERIOR CON SELECTOR DINÁMICO DE EMPRESA ACTIVA E INTERRUPTOR DE TEMA -->
        <header class="min-h-16 py-2.5 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-b border-slate-200 dark:border-slate-800 px-4 lg:px-6 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 shrink-0 z-20">
            <!-- Izquierda: Contexto Multi-Tenant -->
            <div class="flex items-center gap-2.5 flex-wrap min-w-0 flex-1">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 whitespace-nowrap shrink-0">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                        Empresa:
                    </span>

                    <!-- Selector Desplegable Multi-Empresa -->
                    <div class="relative min-w-0">
                        <select id="selectorEmpresaGlobal"
                                aria-label="Seleccionar empresa cliente activa"
                                class="appearance-none max-w-[230px] sm:max-w-[300px] 2xl:max-w-[380px] truncate bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 hover:border-emerald-500/60 focus:border-emerald-500 text-slate-900 dark:text-slate-100 text-xs sm:text-sm font-medium rounded-xl pl-3 pr-8 py-1.5 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            <?php if (!empty($empresasSelector)): ?>
                                <?php foreach ($empresasSelector as $empItem): ?>
                                    <option value="<?= (int)$empItem['id'] ?>"
                                            <?= (int)$empItem['id'] === $empresaActivaId ? 'selected' : '' ?>>
                                        <?= e($empItem['rif']) ?> — <?= e($empItem['razon_social']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="0">Sin empresas activas</option>
                            <?php endif; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500 dark:text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Píldoras de Metadatos Fiscales SENIAT -->
                <div class="hidden xl:flex items-center gap-2 pl-2.5 border-l border-slate-200 dark:border-slate-800 flex-wrap">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-medium whitespace-nowrap shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-700"
                          title="Registro de Información Fiscal">
                        RIF: <?= e($empresaActivaRif) ?>
                    </span>

                    <?php if ($empresaActivaTipo === 'Especial'): ?>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap shrink-0 bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30"
                              title="Sujeto Pasivo Especial SENIAT">
                            Especial (Ret. <?= e($empresaActivaRetIva) ?>%)
                        </span>
                    <?php else: ?>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap shrink-0 bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30">
                            <?= e($empresaActivaTipo) ?> (Ret. <?= e($empresaActivaRetIva) ?>%)
                        </span>
                    <?php endif; ?>

                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono whitespace-nowrap shrink-0 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20"
                          title="Período Fiscal Activo">
                        Período: <?= e($empresaActivaPeriodo) ?>
                    </span>
                </div>
            </div>

            <!-- Derecha: Tasa BCV + Selector de Tema (Sol / Luna) + Acciones Rápidas -->
            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <!-- Botón Rápido de Tasa Oficial BCV (Bs./USD) -->
                <button type="button"
                        onclick="window.JurisApp.abrirModalTasaBcv()"
                        title="Clic para actualizar la Tasa Oficial del Dólar BCV y sincronizar Cestaticket ($40 BCV)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-mono font-bold whitespace-nowrap shrink-0 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 transition-colors">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span>BCV: Bs. <span id="lblTasaBcvHeader"><?= e(number_format($empresaActivaTasaBcv, 2, ',', '.')) ?></span>/$</span>
                </button>

                <!-- Botón Interruptor Modo Claro / Modo Oscuro -->
                <button type="button" id="btnToggleTheme"
                        onclick="window.JurisApp.alternarTema()"
                        title="Cambiar entre Modo Claro y Modo Oscuro"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium whitespace-nowrap shrink-0 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition-colors">
                    <!-- Icono Sol (visible en modo oscuro para cambiar a claro) -->
                    <svg id="iconThemeSun" class="w-4 h-4 text-amber-400 hidden dark:inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <!-- Icono Luna (visible en modo claro para cambiar a oscuro) -->
                    <svg id="iconThemeMoon" class="w-4 h-4 text-indigo-600 inline-block dark:hidden shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <span id="labelThemeMode" class="hidden md:inline">Tema</span>
                </button>

                <a href="<?= e(route_url('manual', 'index')) ?>"
                   title="Abrir Manual de Usuario y Guía de Reportes"
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium whitespace-nowrap shrink-0 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition-colors">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Manual
                </a>

                <a href="<?= e(route_url('empresa', 'index')) ?>"
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium whitespace-nowrap shrink-0 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition-colors">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Empresas
                </a>

                <a href="<?= e(route_url('auth', 'logout')) ?>"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium whitespace-nowrap shrink-0 bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/20 transition-colors">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Salir
                </a>
            </div>
        </header>

        <!-- CONTENIDO PRINCIPAL SCROLLABLE -->
        <main class="flex-1 overflow-y-auto p-6 bg-slate-50 dark:bg-slate-950">
