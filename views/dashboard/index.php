<?php
declare(strict_types=1);

$emp = $empresaActiva ?? null;
$m   = $metricas ?? [];

$cuentasStats   = $m['cuentas'] ?? ['total_cuentas' => 0, 'cuentas_movimiento' => 0, 'cuentas_grupo' => 0];
$rubrosPlan     = $m['rubros_plan'] ?? [];
$asientosStats  = $m['asientos'] ?? ['total' => 0, 'suma_debe' => 0.0, 'suma_haber' => 0.0];
$tributario     = $m['tributario'] ?? [
    'facturas_venta' => 0, 'debito_fiscal' => 0.0, 'total_ventas' => 0.0,
    'facturas_compra' => 0, 'credito_fiscal' => 0.0, 'total_compras' => 0.0,
    'cuota_iva' => 0.0, 'comprobantes_iva' => 0, 'total_iva_retenido' => 0.0,
    'comprobantes_islr' => 0, 'total_islr_retenido' => 0.0
];
$nominaStats    = $m['nomina'] ?? ['trabajadores_activos' => 0, 'masa_salarial_mensual' => 0.0];
$ultimosAsientos= $m['ultimos_asientos'] ?? [];
$escritorioStats= $m['escritorio'] ?? ['total_empresas' => 0, 'empresas_activas' => 0, 'contribuyentes_especiales' => 0, 'contribuyentes_ordinarios' => 0];
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO EJECUTIVO Y RESUMEN DEL ESCRITORIO -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-700 uppercase tracking-wider">
                <span>Escritorio Contable Multi-Empresa</span>
                <span>•</span>
                <span>Contexto Tenant ID #<?= (int)($emp['id'] ?? 0) ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-0.5">
                Panel de Control Fiscal y Contable
            </h1>
        </div>

        <!-- Mini Indicadores Globales del Escritorio -->
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span class="text-xs text-slate-400">Empresas Activas:</span>
                <span class="font-mono font-bold text-sm text-slate-900"><?= (int)$escritorioStats['empresas_activas'] ?></span>
            </div>
            <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="text-xs text-slate-400">Sujetos Especiales:</span>
                <span class="font-mono font-bold text-sm text-amber-800"><?= (int)$escritorioStats['contribuyentes_especiales'] ?></span>
            </div>
            <button type="button"
                    onclick="window.JurisApp.abrirModalPlanCuentas(<?= (int)($emp['id'] ?? 0) ?>)"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Inspeccionar Plan de Cuentas (<?= (int)$cuentasStats['total_cuentas'] ?>)
            </button>
        </div>
    </div>

    <?php if ($emp): ?>
        <!-- FICHA FISCAL DE LA EMPRESA ACTIVA EN SESIÓN -->
        <div class="rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center font-mono font-bold text-base text-emerald-700">
                        <?= e(substr((string)$emp['rif'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 class="text-lg font-bold text-slate-900"><?= e($emp['razon_social']) ?></h2>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-semibold bg-emerald-500/20 text-emerald-800 border border-emerald-500/30">
                                <?= e($emp['rif']) ?>
                            </span>
                            <?php if ($emp['tipo_contribuyente'] === 'Especial'): ?>
                                <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-amber-500/15 text-amber-800 border border-amber-500/30">
                                    Sujeto Pasivo Especial SENIAT
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-sky-500/15 text-sky-800 border border-sky-500/30">
                                    Contribuyente <?= e($emp['tipo_contribuyente']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <?= e($emp['direccion_fiscal']) ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="<?= e(route_url('empresa', 'index')) ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar Ficha Fiscal
                    </a>
                </div>
            </div>

            <!-- Parámetros Fiscales y Tributarios Vigentes -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-200 bg-white">
                <div class="p-4">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Período Fiscal Activo</p>
                    <p class="mt-1 text-base font-mono font-bold text-emerald-700"><?= e($emp['periodo_fiscal_activo']) ?></p>
                </div>
                <div class="p-4">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">% Retención IVA</p>
                    <p class="mt-1 text-base font-mono font-bold text-slate-900"><?= e($emp['porcentaje_retencion_iva']) ?>%</p>
                </div>
                <div class="p-4">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Alícuota IVA / IGTF</p>
                    <p class="mt-1 text-base font-mono font-bold text-slate-900">
                        <?= number_format((float)$emp['alicuota_iva_general'], 0) ?>% / <?= number_format((float)$emp['alicuota_igtf'], 0) ?>%
                    </p>
                </div>
                <div class="p-4">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Unidad Tributaria (UT)</p>
                    <p class="mt-1 text-base font-mono font-bold text-slate-900"><?= formato_ves($emp['valor_unidad_tributaria']) ?></p>
                </div>
                <div class="p-4">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Representante Legal</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800 truncate"><?= e($emp['representante_legal'] ?: 'No especificado') ?></p>
                </div>
                <div class="p-4">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Contacto / Teléfono</p>
                    <p class="mt-1 text-sm font-mono text-slate-800 truncate"><?= e($emp['telefono'] ?: 'Sin teléfono') ?></p>
                </div>
            </div>
        </div>

        <!-- TARJETAS KPI CONECTADAS A LA BASE DE DATOS (EMPRESA ACTIVA) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5">
            <!-- KPI BANCOS: Saldo Total en Bancos -->
            <a href="<?= e(route_url('bancos', 'index')) ?>" class="rounded-2xl bg-white border border-slate-200 p-5 flex flex-col justify-between hover:border-emerald-500/60 transition-all block">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Saldo Total en Bancos</p>
                        <h3 class="mt-2 text-2xl font-mono font-bold text-slate-900">
                            <?= formato_ves((float)($bancosStats['saldo_ves'] ?? 0)) ?>
                        </h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11m16-11v11M8 14v3m4-3v3m4-3v3"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Cuentas activas: <?= (int)($bancosStats['total_cuentas'] ?? 0) ?></span>
                    <span class="font-mono font-semibold text-emerald-800">
                        USD: $ <?= number_format((float)($bancosStats['saldo_usd'] ?? 0), 2, ',', '.') ?>
                    </span>
                </div>
            </a>
            <!-- KPI 1: Plan de Cuentas -->
            <div class="rounded-2xl bg-white border border-slate-200 p-5 flex flex-col justify-between hover:border-slate-300 transition-all">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Plan de Cuentas</p>
                        <h3 class="mt-2 text-2xl font-mono font-bold text-slate-900">
                            <?= (int)$cuentasStats['total_cuentas'] ?> <span class="text-xs font-sans font-normal text-slate-400">cuentas</span>
                        </h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-teal-500/15 border border-teal-500/30 flex items-center justify-center text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Cuentas de movimiento:</span>
                    <span class="font-mono font-semibold text-emerald-800"><?= (int)$cuentasStats['cuentas_movimiento'] ?> imputables</span>
                </div>
            </div>

            <!-- KPI 2: Libro Diario / Partida Doble -->
            <div class="rounded-2xl bg-white border border-slate-200 p-5 flex flex-col justify-between hover:border-slate-300 transition-all">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Movimientos Contables</p>
                        <h3 class="mt-2 text-2xl font-mono font-bold text-slate-900">
                            <?= formato_ves($asientosStats['suma_debe']) ?>
                        </h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Asientos publicados:</span>
                    <span class="font-mono font-semibold text-emerald-800">
                        <?= (int)$asientosStats['total'] ?> (Debe = Haber)
                    </span>
                </div>
            </div>

            <!-- KPI 3: Posición IVA del Período (SENIAT) -->
            <div class="rounded-2xl bg-white border border-slate-200 p-5 flex flex-col justify-between hover:border-slate-300 transition-all">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Cuota IVA (<?= e($emp['periodo_fiscal_activo']) ?>)</p>
                        <h3 class="mt-2 text-2xl font-mono font-bold <?= $tributario['cuota_iva'] >= 0 ? 'text-amber-800' : 'text-sky-800' ?>">
                            <?= formato_ves(abs((float)$tributario['cuota_iva'])) ?>
                        </h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Débito vs Crédito:</span>
                    <span class="font-mono text-slate-700">
                        <?= formato_ves($tributario['debito_fiscal'], false) ?> / <?= formato_ves($tributario['credito_fiscal'], false) ?>
                    </span>
                </div>
            </div>

            <!-- KPI 4: Nómina Activa LOTTT -->
            <a href="<?= e(route_url('nomina', 'index')) ?>" class="rounded-2xl bg-white border border-slate-200 p-5 flex flex-col justify-between hover:border-indigo-500/60 transition-all block">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nómina Activa (LOTTT)</p>
                        <h3 class="mt-2 text-2xl font-mono font-bold text-slate-900">
                            <?= (int)$nominaStats['trabajadores_activos'] ?> <span class="text-xs font-sans font-normal text-slate-400">trabajadores</span>
                        </h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs">
                    <?php if (!empty($nominaStats['ultima_nomina_neto']) && (float)$nominaStats['ultima_nomina_neto'] > 0): ?>
                        <span class="text-slate-400">Última nómina (<?= e((string)$nominaStats['ultima_nomina_codigo']) ?>):</span>
                        <span class="font-mono font-semibold text-indigo-800"><?= formato_ves((float)$nominaStats['ultima_nomina_neto']) ?></span>
                    <?php else: ?>
                        <span class="text-slate-400">Salario base mensual:</span>
                        <span class="font-mono font-semibold text-indigo-800"><?= formato_ves($nominaStats['masa_salarial_mensual']) ?></span>
                    <?php endif; ?>
                </div>
            </a>
        </div>

        <!-- SECCIÓN INFERIOR: ESTRUCTURA DEL PLAN DE CUENTAS + ÚLTIMOS ASIENTOS DE LA EMPRESA -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Columna Izquierda (5 cols): Estructura del Plan de Cuentas por Rubro -->
            <div class="lg:col-span-5 rounded-2xl bg-white border border-slate-200 overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Catálogo Contable de la Empresa</h3>
                            <p class="text-xs text-slate-400">Distribución por rubros del Plan de Cuentas activo</p>
                        </div>
                        <button type="button"
                                onclick="window.JurisApp.abrirModalPlanCuentas(<?= (int)$emp['id'] ?>)"
                                class="text-xs font-mono text-emerald-700 hover:text-emerald-800 underline">
                            Ver todas →
                        </button>
                    </div>

                    <div class="divide-y divide-slate-200/60">
                        <?php foreach ($rubrosPlan as $rubro): ?>
                            <div class="px-5 py-3 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                    <span class="text-xs font-semibold text-slate-800"><?= e($rubro['tipo']) ?></span>
                                </div>
                                <div class="flex items-center gap-3 text-xs font-mono">
                                    <span class="text-slate-400">Total: <strong class="text-slate-800"><?= (int)$rubro['total'] ?></strong></span>
                                    <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-800 border border-emerald-500/20">
                                        <?= (int)$rubro['imputables'] ?> mov.
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 text-xs text-slate-400 flex items-center justify-between">
                    <span>Clonación automática habilitada para nuevas empresas</span>
                    <span class="font-mono text-emerald-700">4 Niveles</span>
                </div>
            </div>

            <!-- Columna Derecha (7 cols): Últimos Asientos Contables y Resumen Fiscal -->
            <div class="lg:col-span-7 rounded-2xl bg-white border border-slate-200 overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Actividad Reciente en Libro Diario</h3>
                            <p class="text-xs text-slate-400">Asientos filtrados estrictamente para <?= e($emp['rif']) ?></p>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-mono bg-slate-100 text-slate-700 border border-slate-300">
                            Partida Doble Verificada
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="py-3 px-4 font-semibold">N° Asiento</th>
                                    <th class="py-3 px-3 font-semibold">Fecha</th>
                                    <th class="py-3 px-3 font-semibold">Concepto</th>
                                    <th class="py-3 px-3 font-semibold text-right">Total Debe</th>
                                    <th class="py-3 px-4 font-semibold text-right">Total Haber</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/60 text-xs">
                                <?php if (!empty($ultimosAsientos)): ?>
                                    <?php foreach ($ultimosAsientos as $asi): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-3 px-4 font-mono font-semibold text-emerald-700"><?= e($asi['numero_asiento']) ?></td>
                                            <td class="py-3 px-3 font-mono text-slate-700"><?= e($asi['fecha']) ?></td>
                                            <td class="py-3 px-3 text-slate-800 max-w-xs truncate" title="<?= e($asi['concepto']) ?>">
                                                <?= e($asi['concepto']) ?>
                                            </td>
                                            <td class="py-3 px-3 font-mono text-right text-slate-900"><?= formato_ves($asi['total_debe']) ?></td>
                                            <td class="py-3 px-4 font-mono text-right text-slate-900"><?= formato_ves($asi['total_haber']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-500">
                                            No hay asientos contables registrados para esta empresa en el período actual.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pie con Resumen de Compras, Ventas y Retenciones SENIAT del Período -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                    <a href="<?= e(route_url('tributario', 'libros', ['tab' => 'ventas'])) ?>"
                       class="px-3 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 transition-colors">
                        <span class="text-slate-400 block">Ventas Mes (<?= (int)$tributario['facturas_venta'] ?> fact.)</span>
                        <span class="font-mono font-semibold text-emerald-700"><?= formato_ves($tributario['total_ventas']) ?></span>
                    </a>
                    <a href="<?= e(route_url('tributario', 'libros', ['tab' => 'compras'])) ?>"
                       class="px-3 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 transition-colors">
                        <span class="text-slate-400 block">Compras Mes (<?= (int)$tributario['facturas_compra'] ?> fact.)</span>
                        <span class="font-mono font-semibold text-sky-400"><?= formato_ves($tributario['total_compras']) ?></span>
                    </a>
                    <a href="<?= e(route_url('tributario', 'retenciones', ['tab' => 'iva'])) ?>"
                       class="px-3 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 transition-colors">
                        <span class="text-slate-400 block">Ret. IVA Emitidas (<?= (int)$tributario['comprobantes_iva'] ?>)</span>
                        <span class="font-mono font-semibold text-amber-400"><?= formato_ves($tributario['total_iva_retenido']) ?></span>
                    </a>
                    <a href="<?= e(route_url('tributario', 'retenciones', ['tab' => 'islr'])) ?>"
                       class="px-3 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 transition-colors">
                        <span class="text-slate-400 block">Ret. ISLR Emitidas (<?= (int)$tributario['comprobantes_islr'] ?>)</span>
                        <span class="font-mono font-semibold text-rose-400"><?= formato_ves($tributario['total_islr_retenido']) ?></span>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>
