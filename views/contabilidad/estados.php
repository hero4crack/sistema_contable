<?php
declare(strict_types=1);

$emp = $empresaActiva ?? [];
$est = $estados ?? [];

$bc = $est['balance_comprobacion'] ?? ['filas' => [], 'totales' => []];
$er = $est['estado_resultados'] ?? [
    'ingresos' => [], 'costos' => [], 'gastos' => [],
    'total_ingresos' => 0.0, 'total_costos' => 0.0, 'utilidad_bruta' => 0.0,
    'total_gastos' => 0.0, 'resultado_neto' => 0.0
];
$bg = $est['balance_general'] ?? [
    'activos' => [], 'pasivos' => [], 'patrimonio' => [],
    'resultado_ejercicios_anteriores' => 0.0, 'resultado_ejercicio_actual' => 0.0,
    'total_activos' => 0.0, 'total_pasivos' => 0.0, 'total_patrimonio' => 0.0,
    'total_pasivo_patrimonio' => 0.0, 'ecuacion_cuadrada' => true
];

$tab = $tabActiva ?? 'comprobacion';
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO DEL MÓDULO ESTADOS FINANCIEROS -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 no-print">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-700 uppercase tracking-wider">
                <span>Módulo 2 • Contabilidad General y Reportes Legales</span>
                <span>•</span>
                <span><?= e($emp['rif'] ?? '') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-0.5">
                Estados Financieros Bajo Normativa Venezolana
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Balance de Comprobación, Estado de Resultados y Balance General interconectados en tiempo real.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a id="linkExportarEstadosCsv"
               href="<?= e(route_url('contabilidad', 'estados', [
                   'fecha_desde' => $fechaDesde,
                   'fecha_hasta' => $fechaHasta,
                   'tab'         => $tab,
                   'export'      => 'csv',
               ])) ?>"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exportar Reporte Actual (CSV/Excel)
            </a>

            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/40 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Vista de Impresión Oficial
            </button>
        </div>
    </div>

    <!-- BARRA DE FILTRO DE PERÍODO Y PESTAÑAS DE NAVEGACIÓN -->
    <div class="rounded-2xl bg-white border border-slate-200 p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 no-print">
        <!-- Pestañas -->
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    data-tab="comprobacion"
                    onclick="window.JurisContabilidad.cambiarTabEstados('comprobacion')"
                    class="btn-tab-estado px-4 py-2 rounded-xl text-xs font-semibold transition-all border <?= $tab === 'comprobacion' ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
                1. Balance de Comprobación
            </button>

            <button type="button"
                    data-tab="resultados"
                    onclick="window.JurisContabilidad.cambiarTabEstados('resultados')"
                    class="btn-tab-estado px-4 py-2 rounded-xl text-xs font-semibold transition-all border <?= $tab === 'resultados' ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
                2. Estado de Resultados (Ganancias y Pérdidas)
            </button>

            <button type="button"
                    data-tab="general"
                    onclick="window.JurisContabilidad.cambiarTabEstados('general')"
                    class="btn-tab-estado px-4 py-2 rounded-xl text-xs font-semibold transition-all border <?= $tab === 'general' ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
                3. Balance General (Situación Financiera)
            </button>
        </div>

        <!-- Formulario Rango de Fechas -->
        <form method="POST" action="<?= e(route_url('contabilidad', 'estados')) ?>" class="flex flex-wrap items-center gap-2.5">
            <?= \Core\Csrf::field() ?>
            <input type="hidden" id="inputTabActivaEstados" name="tab" value="<?= e($tab) ?>">

            <div class="flex items-center gap-1.5">
                <span class="text-xs text-slate-400">Desde:</span>
                <input type="date" name="fecha_desde" value="<?= e($fechaDesde) ?>"
                       class="bg-slate-50 border border-slate-300 focus:border-emerald-500 rounded-xl px-2.5 py-1.5 text-xs font-mono text-slate-900 focus:outline-none">
            </div>

            <div class="flex items-center gap-1.5">
                <span class="text-xs text-slate-400">Hasta:</span>
                <input type="date" name="fecha_hasta" value="<?= e($fechaHasta) ?>"
                       class="bg-slate-50 border border-slate-300 focus:border-emerald-500 rounded-xl px-2.5 py-1.5 text-xs font-mono text-slate-900 focus:outline-none">
            </div>

            <button type="submit"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-300 transition-colors">
                Actualizar Período
            </button>
        </form>
    </div>

    <!-- MEMBRETE OFICIAL DE LA EMPRESA ACTIVA (VISIBLE EN PANTALLA Y EN IMPRESIÓN) -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 print-container">
        <?php
        $tituloReporte = $tab === 'resultados'
            ? 'ESTADO DE RESULTADOS (GANANCIAS Y PÉRDIDAS)'
            : ($tab === 'general' ? 'BALANCE GENERAL (ESTADO DE SITUACIÓN FINANCIERA)' : 'BALANCE DE COMPROBACIÓN DE SUMAS Y SALDOS');
        $subtituloPeriodo = 'Período: ' . (string)$fechaDesde . ' al ' . (string)$fechaHasta . ' • Expresado en Bolívares (VES)';
        $tituloReporteId  = 'tituloMembreteReporte';
        $claseContenedor  = 'mb-0';
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>
    </div>

    <!-- =======================================================================
         SECCIÓN 1: BALANCE DE COMPROBACIÓN
    ======================================================================== -->
    <section id="seccionEstado_comprobacion" class="seccion-estado <?= $tab === 'comprobacion' ? '' : 'hidden' ?> print-container">
        <div class="rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                        <tr>
                            <th rowspan="2" class="py-3 px-4 font-semibold border-r border-slate-200">Código</th>
                            <th rowspan="2" class="py-3 px-4 font-semibold border-r border-slate-200">Denominación de la Cuenta</th>
                            <th colspan="2" class="py-2 px-4 font-semibold text-center border-b border-r border-slate-200">Saldo Anterior (al <?= e($fechaDesde) ?>)</th>
                            <th colspan="2" class="py-2 px-4 font-semibold text-center border-b border-r border-slate-200">Movimientos del Período</th>
                            <th colspan="2" class="py-2 px-4 font-semibold text-center border-b border-slate-200">Saldo Actual (al <?= e($fechaHasta) ?>)</th>
                        </tr>
                        <tr>
                            <th class="py-2 px-3 font-semibold text-right">Deudor</th>
                            <th class="py-2 px-3 font-semibold text-right border-r border-slate-200">Acreedor</th>
                            <th class="py-2 px-3 font-semibold text-right">Débitos (Debe)</th>
                            <th class="py-2 px-3 font-semibold text-right border-r border-slate-200">Créditos (Haber)</th>
                            <th class="py-2 px-3 font-semibold text-right text-sky-800">Deudor</th>
                            <th class="py-2 px-4 font-semibold text-right text-amber-800">Acreedor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        <?php if (!empty($bc['filas'])): ?>
                            <?php foreach ($bc['filas'] as $f): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="py-2.5 px-4 font-mono font-semibold text-emerald-700 border-r border-slate-200/60 whitespace-nowrap">
                                        <?= e($f['codigo']) ?>
                                    </td>
                                    <td class="py-2.5 px-4 text-slate-800 border-r border-slate-200/60">
                                        <?= e($f['nombre']) ?>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-right text-slate-700">
                                        <?= (float)$f['saldo_anterior_deudor'] > 0 ? formato_ves($f['saldo_anterior_deudor'], false) : '0,00' ?>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-right text-slate-700 border-r border-slate-200/60">
                                        <?= (float)$f['saldo_anterior_acreedor'] > 0 ? formato_ves($f['saldo_anterior_acreedor'], false) : '0,00' ?>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-right <?= (float)$f['debitos_periodo'] > 0 ? 'text-slate-900 font-semibold' : 'text-slate-500' ?>">
                                        <?= formato_ves($f['debitos_periodo'], false) ?>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200/60 <?= (float)$f['creditos_periodo'] > 0 ? 'text-slate-900 font-semibold' : 'text-slate-500' ?>">
                                        <?= formato_ves($f['creditos_periodo'], false) ?>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-right font-semibold <?= (float)$f['saldo_actual_deudor'] > 0 ? 'text-sky-800' : 'text-slate-600' ?>">
                                        <?= (float)$f['saldo_actual_deudor'] > 0 ? formato_ves($f['saldo_actual_deudor'], false) : '0,00' ?>
                                    </td>
                                    <td class="py-2.5 px-4 font-mono text-right font-semibold <?= (float)$f['saldo_actual_acreedor'] > 0 ? 'text-amber-800' : 'text-slate-600' ?>">
                                        <?= (float)$f['saldo_actual_acreedor'] > 0 ? formato_ves($f['saldo_actual_acreedor'], false) : '0,00' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-500">
                                    No existen cuentas con movimientos registrados para el período consultado.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php $t = $bc['totales']; ?>
                    <tfoot class="bg-slate-50 border-t-2 border-emerald-500/40 font-mono text-xs font-bold">
                        <tr>
                            <td colspan="2" class="py-3.5 px-4 text-right uppercase text-slate-900 border-r border-slate-200">
                                SUMAS IGUALES DE COMPROBACIÓN (Bs.):
                            </td>
                            <td class="py-3.5 px-3 text-right text-emerald-700">
                                <?= formato_ves($t['saldo_anterior_deudor'] ?? 0, false) ?>
                            </td>
                            <td class="py-3.5 px-3 text-right text-emerald-700 border-r border-slate-200">
                                <?= formato_ves($t['saldo_anterior_acreedor'] ?? 0, false) ?>
                            </td>
                            <td class="py-3.5 px-3 text-right text-emerald-700">
                                <?= formato_ves($t['debitos_periodo'] ?? 0, false) ?>
                            </td>
                            <td class="py-3.5 px-3 text-right text-emerald-700 border-r border-slate-200">
                                <?= formato_ves($t['creditos_periodo'] ?? 0, false) ?>
                            </td>
                            <td class="py-3.5 px-3 text-right text-sky-800">
                                <?= formato_ves($t['saldo_actual_deudor'] ?? 0, false) ?>
                            </td>
                            <td class="py-3.5 px-4 text-right text-amber-800">
                                <?= formato_ves($t['saldo_actual_acreedor'] ?? 0, false) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </section>

    <!-- =======================================================================
         SECCIÓN 2: ESTADO DE RESULTADOS (GANANCIAS Y PÉRDIDAS)
    ======================================================================== -->
    <section id="seccionEstado_resultados" class="seccion-estado <?= $tab === 'resultados' ? '' : 'hidden' ?> print-container">
        <div class="rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden p-6 space-y-6">

            <!-- 1. INGRESOS OPERACIONALES -->
            <div>
                <div class="px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <span class="font-mono font-bold text-xs text-emerald-700 uppercase tracking-wider">4. INGRESOS OPERACIONALES</span>
                    <span class="font-mono text-xs text-slate-400">Bolívares (VES)</span>
                </div>
                <table class="w-full text-xs mt-2">
                    <tbody class="divide-y divide-slate-200/60">
                        <?php if (!empty($er['ingresos'])): ?>
                            <?php foreach ($er['ingresos'] as $ing): ?>
                                <tr>
                                    <td class="py-2.5 px-4 font-mono text-emerald-700 w-36"><?= e($ing['codigo']) ?></td>
                                    <td class="py-2.5 px-4 text-slate-800"><?= e($ing['nombre']) ?></td>
                                    <td class="py-2.5 px-4 font-mono text-right text-slate-900 w-44"><?= formato_ves($ing['monto']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="py-3 px-4 text-slate-500">Sin ingresos registrados en este período.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="border-t border-slate-200 font-mono font-bold">
                        <tr>
                            <td colspan="2" class="py-2.5 px-4 text-right text-slate-700">TOTAL INGRESOS OPERACIONALES:</td>
                            <td class="py-2.5 px-4 text-right text-emerald-700"><?= formato_ves($er['total_ingresos']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- 2. COSTO DE VENTAS Y SERVICIOS -->
            <div>
                <div class="px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <span class="font-mono font-bold text-xs text-amber-400 uppercase tracking-wider">5. MENOS: COSTO DE VENTAS Y SERVICIOS</span>
                    <span class="font-mono text-xs text-slate-400">Bolívares (VES)</span>
                </div>
                <table class="w-full text-xs mt-2">
                    <tbody class="divide-y divide-slate-200/60">
                        <?php if (!empty($er['costos'])): ?>
                            <?php foreach ($er['costos'] as $cos): ?>
                                <tr>
                                    <td class="py-2.5 px-4 font-mono text-amber-400 w-36"><?= e($cos['codigo']) ?></td>
                                    <td class="py-2.5 px-4 text-slate-800"><?= e($cos['nombre']) ?></td>
                                    <td class="py-2.5 px-4 font-mono text-right text-slate-900 w-44"><?= formato_ves($cos['monto']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="py-3 px-4 text-slate-500">Sin costos directos registrados en este período.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="border-t border-slate-200 font-mono font-bold">
                        <tr>
                            <td colspan="2" class="py-2.5 px-4 text-right text-slate-700">TOTAL COSTO DE VENTAS Y SERVICIOS:</td>
                            <td class="py-2.5 px-4 text-right text-amber-400">(<?= formato_ves($er['total_costos']) ?>)</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- SUBTOTAL: UTILIDAD BRUTA EN OPERACIONES -->
            <div class="px-5 py-3.5 rounded-xl bg-slate-50 border border-emerald-500/30 flex items-center justify-between font-mono">
                <span class="text-xs font-bold uppercase text-slate-900">= UTILIDAD (O PÉRDIDA) BRUTA EN OPERACIONES (Ingresos - Costos):</span>
                <span class="text-sm font-bold <?= (float)$er['utilidad_bruta'] >= 0 ? 'text-emerald-700' : 'text-rose-400' ?>">
                    <?= formato_ves($er['utilidad_bruta']) ?>
                </span>
            </div>

            <!-- 3. GASTOS OPERACIONALES -->
            <div>
                <div class="px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <span class="font-mono font-bold text-xs text-rose-400 uppercase tracking-wider">6. MENOS: GASTOS OPERACIONALES, DE PERSONAL Y ADMINISTRACIÓN</span>
                    <span class="font-mono text-xs text-slate-400">Bolívares (VES)</span>
                </div>
                <table class="w-full text-xs mt-2">
                    <tbody class="divide-y divide-slate-200/60">
                        <?php if (!empty($er['gastos'])): ?>
                            <?php foreach ($er['gastos'] as $gas): ?>
                                <tr>
                                    <td class="py-2.5 px-4 font-mono text-rose-400 w-36"><?= e($gas['codigo']) ?></td>
                                    <td class="py-2.5 px-4 text-slate-800"><?= e($gas['nombre']) ?></td>
                                    <td class="py-2.5 px-4 font-mono text-right text-slate-900 w-44"><?= formato_ves($gas['monto']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="py-3 px-4 text-slate-500">Sin gastos operacionales registrados en este período.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="border-t border-slate-200 font-mono font-bold">
                        <tr>
                            <td colspan="2" class="py-2.5 px-4 text-right text-slate-700">TOTAL GASTOS OPERACIONALES:</td>
                            <td class="py-2.5 px-4 text-right text-rose-400">(<?= formato_ves($er['total_gastos']) ?>)</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- RESULTADO FINAL DEL EJERCICIO -->
            <div class="px-6 py-4 rounded-2xl bg-slate-50 border-2 <?= (float)$er['resultado_neto'] >= 0 ? 'border-emerald-500/50' : 'border-rose-500/50' ?> flex flex-wrap items-center justify-between gap-4 font-mono">
                <div>
                    <span class="text-xs font-bold uppercase text-slate-900 block">
                        = <?= (float)$er['resultado_neto'] >= 0 ? 'UTILIDAD NETA DEL EJERCICIO' : 'PÉRDIDA NETA DEL EJERCICIO' ?>
                    </span>
                    <span class="text-[11px] font-sans text-slate-400">
                        Este resultado se transfiere e integra automáticamente al Patrimonio en el Balance General.
                    </span>
                </div>
                <span class="text-xl font-bold <?= (float)$er['resultado_neto'] >= 0 ? 'text-emerald-700' : 'text-rose-400' ?>">
                    <?= formato_ves($er['resultado_neto']) ?>
                </span>
            </div>

        </div>
    </section>

    <!-- =======================================================================
         SECCIÓN 3: BALANCE GENERAL (ESTADO DE SITUACIÓN FINANCIERA)
    ======================================================================== -->
    <section id="seccionEstado_general" class="seccion-estado <?= $tab === 'general' ? '' : 'hidden' ?> print-container">
        <div class="space-y-6">

            <!-- Banner de Verificación de la Ecuación Patrimonial (Activo = Pasivo + Patrimonio) -->
            <div class="rounded-2xl bg-white border <?= $bg['ecuacion_cuadrada'] ? 'border-emerald-500/40' : 'border-rose-500/40' ?> p-4 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl <?= $bg['ecuacion_cuadrada'] ? 'bg-emerald-500/20 text-emerald-700' : 'bg-rose-500/20 text-rose-400' ?> flex items-center justify-center font-bold">
                        <?= $bg['ecuacion_cuadrada'] ? '✓' : '!' ?>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold uppercase text-slate-900">
                            Ecuación Patrimonial Contable (Activo = Pasivo + Patrimonio)
                        </h3>
                        <p class="text-[11px] text-slate-400">
                            Integrando automáticamente el resultado del ejercicio (<?= formato_ves($bg['resultado_ejercicio_actual']) ?>) en el Patrimonio.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 font-mono text-xs">
                    <span>Total Activo: <strong class="text-sky-800"><?= formato_ves($bg['total_activos']) ?></strong></span>
                    <span class="text-slate-600">=</span>
                    <span>Pasivo + Patrimonio: <strong class="text-emerald-700"><?= formato_ves($bg['total_pasivo_patrimonio']) ?></strong></span>
                </div>
            </div>

            <!-- Columnas de Situación Financiera: Izquierda (ACTIVOS) / Derecha (PASIVOS Y PATRIMONIO) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Columna 1: ACTIVOS -->
                <div class="rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <h3 class="text-xs font-mono font-bold uppercase text-sky-800">1. ACTIVOS (Al <?= e($fechaHasta) ?>)</h3>
                            <span class="text-[11px] font-mono text-slate-400">Saldo en Bs.</span>
                        </div>

                        <table class="w-full text-xs">
                            <tbody class="divide-y divide-slate-200/60">
                                <?php if (!empty($bg['activos'])): ?>
                                    <?php foreach ($bg['activos'] as $act): ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-2.5 px-5 font-mono text-sky-800 w-32"><?= e($act['codigo']) ?></td>
                                            <td class="py-2.5 px-3 text-slate-800"><?= e($act['nombre']) ?></td>
                                            <td class="py-2.5 px-5 font-mono text-right <?= (float)$act['monto'] >= 0 ? 'text-slate-900' : 'text-rose-400' ?>">
                                                <?= formato_ves($act['monto']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="py-6 px-5 text-center text-slate-500">Sin saldos de activo a la fecha.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-5 py-4 bg-slate-50 border-t-2 border-sky-500/40 flex items-center justify-between font-mono">
                        <span class="text-xs font-bold uppercase text-slate-900">TOTAL ACTIVO:</span>
                        <span class="text-base font-bold text-sky-800"><?= formato_ves($bg['total_activos']) ?></span>
                    </div>
                </div>

                <!-- Columna 2: PASIVOS + PATRIMONIO -->
                <div class="rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden flex flex-col justify-between space-y-4">
                    <div class="space-y-4">
                        <!-- Subbloque 2.1: PASIVOS -->
                        <div>
                            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                                <h3 class="text-xs font-mono font-bold uppercase text-amber-800">2. PASIVOS (Al <?= e($fechaHasta) ?>)</h3>
                                <span class="text-[11px] font-mono text-slate-400">Saldo en Bs.</span>
                            </div>

                            <table class="w-full text-xs">
                                <tbody class="divide-y divide-slate-200/60">
                                    <?php if (!empty($bg['pasivos'])): ?>
                                        <?php foreach ($bg['pasivos'] as $pas): ?>
                                            <tr class="hover:bg-slate-50">
                                                <td class="py-2.5 px-5 font-mono text-amber-800 w-32"><?= e($pas['codigo']) ?></td>
                                                <td class="py-2.5 px-3 text-slate-800"><?= e($pas['nombre']) ?></td>
                                                <td class="py-2.5 px-5 font-mono text-right text-slate-900"><?= formato_ves($pas['monto']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="py-4 px-5 text-center text-slate-500">Sin pasivos pendientes a la fecha.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="bg-slate-50 border-t border-slate-200 font-mono font-bold">
                                    <tr>
                                        <td colspan="2" class="py-2.5 px-5 text-right text-slate-700">TOTAL PASIVO:</td>
                                        <td class="py-2.5 px-5 text-right text-amber-800"><?= formato_ves($bg['total_pasivos']) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Subbloque 2.2: PATRIMONIO / CAPITAL -->
                        <div>
                            <div class="px-5 py-3 bg-slate-50 border-y border-slate-200 flex items-center justify-between">
                                <h3 class="text-xs font-mono font-bold uppercase text-emerald-700">3. PATRIMONIO (CAPITAL Y RESULTADOS)</h3>
                                <span class="text-[11px] font-mono text-slate-400">Saldo en Bs.</span>
                            </div>

                            <table class="w-full text-xs">
                                <tbody class="divide-y divide-slate-200/60">
                                    <?php foreach (($bg['patrimonio'] ?? []) as $pat): ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-2.5 px-5 font-mono text-emerald-700 w-32"><?= e($pat['codigo']) ?></td>
                                            <td class="py-2.5 px-3 text-slate-800"><?= e($pat['nombre']) ?></td>
                                            <td class="py-2.5 px-5 font-mono text-right text-slate-900"><?= formato_ves($pat['monto']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (abs((float)$bg['resultado_ejercicios_anteriores']) >= 0.005): ?>
                                        <tr class="bg-slate-50/30">
                                            <td class="py-2.5 px-5 font-mono text-emerald-800 w-32">3.2.02.001</td>
                                            <td class="py-2.5 px-3 text-slate-800 font-medium">Resultado Acumulado Períodos Anteriores</td>
                                            <td class="py-2.5 px-5 font-mono text-right text-slate-900">
                                                <?= formato_ves($bg['resultado_ejercicios_anteriores']) ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <!-- Línea Automática de Utilidad / Pérdida del Ejercicio -->
                                    <tr class="bg-emerald-500/5">
                                        <td class="py-2.5 px-5 font-mono font-bold text-emerald-700 w-32">3.2.02.002</td>
                                        <td class="py-2.5 px-3 font-semibold text-slate-900">
                                            Utilidad o Pérdida del Ejercicio (<?= e($fechaDesde) ?> al <?= e($fechaHasta) ?>)
                                        </td>
                                        <td class="py-2.5 px-5 font-mono font-bold text-right <?= (float)$bg['resultado_ejercicio_actual'] >= 0 ? 'text-emerald-700' : 'text-rose-400' ?>">
                                            <?= formato_ves($bg['resultado_ejercicio_actual']) ?>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-slate-50 border-t border-slate-200 font-mono font-bold">
                                    <tr>
                                        <td colspan="2" class="py-2.5 px-5 text-right text-slate-700">TOTAL PATRIMONIO NETO:</td>
                                        <td class="py-2.5 px-5 text-right text-emerald-700"><?= formato_ves($bg['total_patrimonio']) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="px-5 py-4 bg-slate-50 border-t-2 border-emerald-500/40 flex items-center justify-between font-mono">
                        <span class="text-xs font-bold uppercase text-slate-900">TOTAL PASIVO + PATRIMONIO:</span>
                        <span class="text-base font-bold text-emerald-700"><?= formato_ves($bg['total_pasivo_patrimonio']) ?></span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
</div>
