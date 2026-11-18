<?php
declare(strict_types=1);

use Core\Session;

$moduloActual  = $moduloActivo ?? 'dashboard';
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
$empresaActivaLogo    = (string)($empresaActiva['logo_path'] ?? Session::get('empresa_activa_logo', ''));

$inicialesEmpresa = '';
$palabrasNombre = preg_split('/\s+/', trim($empresaActivaNombre)) ?: [];
foreach (array_slice($palabrasNombre, 0, 2) as $pal) {
    if ($pal !== '') {
        $inicialesEmpresa .= mb_strtoupper(mb_substr($pal, 0, 1));
    }
}
if ($inicialesEmpresa === '') {
    $inicialesEmpresa = 'JC';
}
?>
    <!-- SIDEBAR COLAPSABLE INSTITUCIONAL (BLANCO / VERDE) -->
    <aside id="appSidebar" class="w-72 bg-white border-r border-slate-200 flex flex-col justify-between transition-all duration-300 ease-in-out z-30 shrink-0 select-none">
        <!-- Logo e Identidad -->
        <div>
            <div class="h-16 px-4 flex items-center justify-between border-b border-slate-200 bg-white">
                <a href="<?= e(route_url('dashboard', 'index')) ?>" class="flex items-center gap-2.5 overflow-hidden">
                    <img src="<?= e(base_url('assets/img/logo-juris-contable.png')) ?>"
                         alt="Juris Contable"
                         class="w-10 h-10 object-contain rounded-lg border border-slate-200 bg-white p-0.5 shrink-0">
                    <div class="sidebar-text leading-tight">
                        <span class="font-bold text-slate-900 text-sm tracking-wide block">JURIS CONTABLE</span>
                        <span class="text-[10px] font-mono text-emerald-700 font-semibold uppercase tracking-widest">SENIAT • LOTTT</span>
                    </div>
                </a>

                <button type="button" id="btnToggleSidebar"
                        title="Colapsar / Expandir menú lateral"
                        class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                </button>
            </div>

            <!-- Navegación por Módulos -->
            <nav class="p-3 space-y-5 overflow-y-auto max-h-[calc(100vh-8.5rem)]">
                <!-- MÓDULO 1: ESCRITORIO Y MULTI-EMPRESA -->
                <div>
                    <p class="sidebar-section-label px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        1. Escritorio Multi-Tenant
                    </p>
                    <ul class="space-y-1">
                        <li>
                            <a href="<?= e(route_url('dashboard', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'dashboard' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'dashboard' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    <span class="sidebar-text">Dashboard Fiscal</span>
                                </div>
                                <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded <?= $moduloActual === 'dashboard' ? 'bg-emerald-800 text-emerald-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">Activo</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('empresa', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'empresas' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'empresas' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <span class="sidebar-text">Empresas Clientes</span>
                                </div>
                                <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded <?= $moduloActual === 'empresas' ? 'bg-emerald-800 text-emerald-100' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                                    <?= count($empresasSelector ?? []) ?>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- MÓDULO 2: CONTABILIDAD GENERAL -->
                <div>
                    <p class="sidebar-section-label px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        2. Contabilidad General
                    </p>
                    <ul class="space-y-1">
                        <li>
                            <a href="<?= e(route_url('plan_cuentas', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'plan_cuentas' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'plan_cuentas' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="sidebar-text">Plan de Cuentas</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('contabilidad', 'diario')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'diario' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'diario' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span class="sidebar-text">Libro Diario y Asientos</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('contabilidad', 'mayor')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'mayor' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'mayor' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                                    <span class="sidebar-text">Libro Mayor Analítico</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('contabilidad', 'estados')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'estados' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'estados' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <span class="sidebar-text">Estados Financieros</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('bancos', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'bancos_index' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'bancos_index' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11m16-11v11M8 14v3m4-3v3m4-3v3"/>
                                    </svg>
                                    <span class="sidebar-text">Enlace Bancario</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('bancos', 'conciliar')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'bancos_conciliar' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'bancos_conciliar' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="sidebar-text">Conciliación Bancaria</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- MÓDULO 3: TRIBUTARIO (SENIAT) -->
                <div>
                    <p class="sidebar-section-label px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        3. Tributario (SENIAT)
                    </p>
                    <ul class="space-y-1">
                        <li>
                            <a href="<?= e(route_url('tributario', 'libros')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'tributario_libros' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'tributario_libros' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    <span class="sidebar-text">Libros Compras y Ventas</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('tributario', 'retenciones')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'tributario_retenciones' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'tributario_retenciones' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                    </svg>
                                    <span class="sidebar-text">Retenciones IVA / ISLR</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- MÓDULO 4: LABORAL (LOTTT) -->
                <div>
                    <p class="sidebar-section-label px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        4. Laboral (LOTTT)
                    </p>
                    <ul class="space-y-1">
                        <li>
                            <a href="<?= e(route_url('nomina', 'trabajadores')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'nomina_trabajadores' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'nomina_trabajadores' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="sidebar-text">Directorio Trabajadores</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('nomina', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'nomina_index' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'nomina_index' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="sidebar-text">Nómina Quincenal / Mes</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('nomina', 'cestaticket')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'nomina_cestaticket' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'nomina_cestaticket' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="sidebar-text">Bono de Alimentación</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(route_url('nomina', 'recibos')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'nomina_recibos' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'nomina_recibos' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <p class="sidebar-section-label px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        5. Administración y Ayuda
                    </p>
                    <ul class="space-y-1">
                        <?php if ($usuarioRol === 'admin'): ?>
                            <li>
                                <a href="<?= e(route_url('usuarios', 'index')) ?>"
                                   class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'usuarios' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'usuarios' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <span class="sidebar-text">Usuarios y Permisos</span>
                                    </div>
                                    <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded <?= $moduloActual === 'usuarios' ? 'bg-emerald-800 text-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">Admin</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?= e(route_url('respaldo', 'index')) ?>"
                                   class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'respaldo' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'respaldo' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                                        </svg>
                                        <span class="sidebar-text">Respaldo Base de Datos</span>
                                    </div>
                                    <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded <?= $moduloActual === 'respaldo' ? 'bg-emerald-800 text-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">Admin</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?= e(route_url('manual', 'index')) ?>"
                               class="sidebar-item flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $moduloActual === 'manual' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' ?>">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 <?= $moduloActual === 'manual' ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span class="sidebar-text">Manual de Usuario</span>
                                </div>
                                <span class="sidebar-badge text-[10px] font-mono px-1.5 py-0.5 rounded <?= $moduloActual === 'manual' ? 'bg-emerald-800 text-emerald-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">Guía</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <!-- Pie del Sidebar: Usuario en Sesión -->
        <div class="p-3 border-t border-slate-200 bg-slate-50">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-9 h-9 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center font-bold text-xs text-emerald-700 shrink-0">
                        <?= e(mb_strtoupper(mb_substr($usuarioNombre, 0, 2))) ?>
                    </div>
                    <div class="sidebar-text truncate">
                        <p class="text-xs font-semibold text-slate-900 truncate"><?= e($usuarioNombre) ?></p>
                        <p class="text-[11px] font-mono font-semibold text-emerald-700 uppercase"><?= e($usuarioRol) ?></p>
                    </div>
                </div>
                <a href="<?= e(route_url('auth', 'logout')) ?>"
                   title="Cerrar sesión"
                   class="sidebar-text p-2 rounded-lg text-slate-500 hover:text-rose-700 hover:bg-rose-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- CONTENEDOR DERECHO: TOPBAR MULTI-TENANT + CONTENIDO -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
        <!-- HEADER SUPERIOR CON LOGO DE EMPRESA ACTIVA, SELECTOR MULTI-EMPRESA Y TASA BCV -->
        <header class="min-h-16 py-2.5 bg-white border-b border-slate-200 px-4 lg:px-6 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 shrink-0 z-20">
            <!-- Izquierda: Contexto Multi-Tenant -->
            <div class="flex items-center gap-2.5 flex-wrap min-w-0 flex-1">
                <div class="flex items-center gap-2 min-w-0">
                    <!-- Miniatura del Logo de la Empresa Activa -->
                    <?php if ($empresaActivaLogo !== '' && is_file(ROOT_PATH . '/public/' . ltrim($empresaActivaLogo, '/'))): ?>
                        <img src="<?= e(base_url(ltrim($empresaActivaLogo, '/'))) ?>"
                             alt="Logo <?= e($empresaActivaNombre) ?>"
                             class="w-8 h-8 rounded-lg object-contain border border-slate-200 bg-white p-0.5 shrink-0">
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-center text-[11px] font-mono font-bold text-emerald-800 shrink-0"
                             title="<?= e($empresaActivaNombre) ?>">
                            <?= e($inicialesEmpresa) ?>
                        </div>
                    <?php endif; ?>

                    <span class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap shrink-0">
                        Empresa:
                    </span>

                    <!-- Selector Desplegable Multi-Empresa -->
                    <div class="relative min-w-0">
                        <select id="selectorEmpresaGlobal"
                                aria-label="Seleccionar empresa cliente activa"
                                class="appearance-none max-w-[230px] sm:max-w-[300px] 2xl:max-w-[380px] truncate bg-slate-50 border border-slate-300 hover:border-emerald-700 focus:border-emerald-700 text-slate-900 text-xs sm:text-sm font-semibold rounded-xl pl-3 pr-8 py-1.5 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-700/20">
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
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Píldoras de Metadatos Fiscales SENIAT -->
                <div class="hidden xl:flex items-center gap-2 pl-2.5 border-l border-slate-200 flex-wrap">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-semibold whitespace-nowrap shrink-0 bg-slate-100 text-slate-800 border border-slate-200"
                          title="Registro de Información Fiscal">
                        RIF: <?= e($empresaActivaRif) ?>
                    </span>

                    <?php if ($empresaActivaTipo === 'Especial'): ?>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap shrink-0 bg-amber-50 text-amber-800 border border-amber-200"
                              title="Sujeto Pasivo Especial SENIAT">
                            Especial (Ret. <?= e($empresaActivaRetIva) ?>%)
                        </span>
                    <?php else: ?>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap shrink-0 bg-sky-50 text-sky-800 border border-sky-200">
                            <?= e($empresaActivaTipo) ?> (Ret. <?= e($empresaActivaRetIva) ?>%)
                        </span>
                    <?php endif; ?>

                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-semibold whitespace-nowrap shrink-0 bg-emerald-50 text-emerald-800 border border-emerald-200"
                          title="Período Fiscal Activo">
                        Período: <?= e($empresaActivaPeriodo) ?>
                    </span>
                </div>
            </div>

            <!-- Derecha: Tasa BCV + Acciones Rápidas -->
            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <!-- Botón Rápido de Tasa Oficial BCV (Bs./USD) -->
                <button type="button"
                        onclick="window.JurisApp.abrirModalTasaBcv()"
                        title="Clic para actualizar la Tasa Oficial del Dólar BCV y sincronizar Cestaticket ($40 BCV)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-mono font-bold whitespace-nowrap shrink-0 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition-colors">
                    <span class="w-2 h-2 rounded-full bg-emerald-600 shrink-0"></span>
                    <span>BCV: Bs. <span id="lblTasaBcvHeader"><?= e(number_format($empresaActivaTasaBcv, 2, ',', '.')) ?></span>/$</span>
                </button>

                <a href="<?= e(route_url('manual', 'index')) ?>"
                   title="Abrir Manual de Usuario y Guía de Reportes"
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap shrink-0 bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-300 transition-colors">
                    <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Manual
                </a>

                <a href="<?= e(route_url('empresa', 'index')) ?>"
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap shrink-0 bg-emerald-700 hover:bg-emerald-800 text-white shadow-sm transition-colors">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Empresas
                </a>

                <a href="<?= e(route_url('auth', 'logout')) ?>"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap shrink-0 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition-colors">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Salir
                </a>
            </div>
        </header>

        <!-- CONTENIDO PRINCIPAL SCROLLABLE -->
        <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
