<?php
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

$emp    = $empresaActiva ?? [];
$cfg    = $configFiscal ?? [];
$compras= $libroCompras ?? [];
$ventas = $libroVentas ?? [];
$art72  = $resumenArt72 ?? ['debitos' => [], 'creditos' => [], 'autoliquidacion' => []];
$tab    = $tabActiva ?? 'compras';

$usuarioRol  = (string)Session::get('usuario_rol', 'contador');
$puedeEditar = in_array($usuarioRol, ['admin', 'contador'], true);

// Totales acumulados del Libro de Compras
$totC = [
    'total_con_iva' => 0.0, 'exentas' => 0.0,
    'base_gen' => 0.0, 'iva_gen' => 0.0,
    'base_red' => 0.0, 'iva_red' => 0.0,
    'base_adi' => 0.0, 'iva_adi' => 0.0,
    'base_igtf'=> 0.0, 'monto_igtf' => 0.0,
    'iva_retenido' => 0.0,
];
foreach ($compras as $cRow) {
    if ($cRow['estado'] === 'ACTIVO') {
        $totC['total_con_iva'] += (float)$cRow['total_compra_con_iva'];
        $totC['exentas']       += (float)$cRow['compras_exentas'];
        $totC['base_gen']      += (float)$cRow['base_imponible_general'];
        $totC['iva_gen']       += (float)$cRow['monto_iva_general'];
        $totC['base_red']      += (float)$cRow['base_imponible_reducida'];
        $totC['iva_red']       += (float)$cRow['monto_iva_reducida'];
        $totC['base_adi']      += (float)($cRow['base_imponible_adicional'] ?? 0);
        $totC['iva_adi']       += (float)($cRow['monto_iva_adicional'] ?? 0);
        $totC['base_igtf']     += (float)($cRow['base_igtf'] ?? 0);
        $totC['monto_igtf']    += (float)($cRow['monto_igtf'] ?? 0);
        $totC['iva_retenido']  += (float)($cRow['monto_iva_retenido'] ?? 0);
    }
}

// Totales acumulados del Libro de Ventas
$totV = [
    'total_con_iva' => 0.0, 'exentas' => 0.0,
    'base_gen' => 0.0, 'iva_gen' => 0.0,
    'base_red' => 0.0, 'iva_red' => 0.0,
    'base_adi' => 0.0, 'iva_adi' => 0.0,
    'monto_igtf' => 0.0, 'iva_ret_cliente' => 0.0,
];
foreach ($ventas as $vRow) {
    if ($vRow['estado'] === 'ACTIVO') {
        $totV['total_con_iva']   += (float)$vRow['total_venta_con_iva'];
        $totV['exentas']         += (float)$vRow['ventas_exentas'];
        $totV['base_gen']        += (float)$vRow['base_imponible_general'];
        $totV['iva_gen']         += (float)$vRow['monto_iva_general'];
        $totV['base_red']        += (float)($vRow['base_imponible_reducida'] ?? 0);
        $totV['iva_red']         += (float)($vRow['monto_iva_reducida'] ?? 0);
        $totV['base_adi']        += (float)($vRow['base_imponible_adicional'] ?? 0);
        $totV['iva_adi']         += (float)($vRow['monto_iva_adicional'] ?? 0);
        $totV['monto_igtf']      += (float)$vRow['monto_igtf'];
        $totV['iva_ret_cliente'] += (float)$vRow['iva_retenido_por_cliente'];
    }
}
?>

<style>
    @media print {
        @page { size: landscape; margin: 10mm; }
    }
</style>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO DEL MÓDULO LIBROS SENIAT -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 no-print">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-400 uppercase tracking-wider">
                <span>Módulo 3 • Tributario SENIAT</span>
                <span>•</span>
                <span><?= e($emp['rif'] ?? '') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight mt-0.5">
                Libros Auxiliares de Compras y Ventas (IVA)
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Reglamento de la Ley del IVA (Art. 70 al 77) y Resumen Fiscal Forma 30 para <strong class="text-slate-200"><?= e($emp['razon_social'] ?? '') ?></strong>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <?php if ($puedeEditar): ?>
                <button type="button"
                        onclick="window.JurisTributario.abrirModalConfigFiscal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Parámetros UT (Bs. <?= number_format((float)($cfg['valor_unidad_tributaria'] ?? 9), 2, ',', '.') ?>)
                </button>
            <?php endif; ?>

            <a id="linkExportarLibroCsv"
               href="<?= e(route_url('tributario', 'libros', [
                   'periodo' => $periodo,
                   'libro'   => $tab === 'ventas' ? 'ventas' : 'compras',
                   'export'  => 'csv',
               ])) ?>"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exportar Libro (CSV/Excel)
            </a>

            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Vista Impresión Horizontal
            </button>

            <?php if ($puedeEditar): ?>
                <button type="button"
                        onclick="window.JurisTributario.abrirModalCompra()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-950/50 transition-all">
                    + Registrar Compra
                </button>
                <button type="button"
                        onclick="window.JurisTributario.abrirModalVenta()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 shadow-lg shadow-sky-950/50 transition-all">
                    + Registrar Venta
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- BARRA DE PESTAÑAS Y SELECTOR DE PERÍODO FISCAL -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    data-tab="compras"
                    onclick="window.JurisTributario.cambiarTabLibros('compras')"
                    class="btn-tab-libro px-4 py-2 rounded-xl text-xs font-semibold transition-all border <?= $tab === 'compras' ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-950 text-slate-300 border-slate-800 hover:bg-slate-800' ?>">
                1. Libro de Compras (<?= count($compras) ?>)
            </button>

            <button type="button"
                    data-tab="ventas"
                    onclick="window.JurisTributario.cambiarTabLibros('ventas')"
                    class="btn-tab-libro px-4 py-2 rounded-xl text-xs font-semibold transition-all border <?= $tab === 'ventas' ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-950 text-slate-300 border-slate-800 hover:bg-slate-800' ?>">
                2. Libro de Ventas (<?= count($ventas) ?>)
            </button>

            <button type="button"
                    data-tab="resumen72"
                    onclick="window.JurisTributario.cambiarTabLibros('resumen72')"
                    class="btn-tab-libro px-4 py-2 rounded-xl text-xs font-semibold transition-all border <?= $tab === 'resumen72' ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-950 text-slate-300 border-slate-800 hover:bg-slate-800' ?>">
                3. Resumen Fiscal Art. 72 (Forma 30 SENIAT)
            </button>
        </div>

        <form method="GET" action="<?= e(BASE_URL . '/index.php') ?>" class="flex items-center gap-2.5">
            <input type="hidden" name="c" value="tributario">
            <input type="hidden" name="a" value="libros">
            <input type="hidden" id="inputTabActivaLibros" name="tab" value="<?= e($tab) ?>">

            <label class="text-xs font-medium text-slate-400">Período Fiscal:</label>
            <input type="month" name="periodo" value="<?= e($periodo) ?>"
                   class="bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-1.5 text-xs font-mono text-white focus:outline-none">
            <button type="submit"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-100 border border-slate-700 transition-colors">
                Consultar Mes
            </button>
        </form>
    </div>

    <!-- MEMBRETE OFICIAL DE LA EMPRESA ACTIVA -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print-container">
        <div>
            <h2 class="text-base font-bold text-white uppercase">
                NOMBRE DEL CONTRIBUYENTE: <?= e($emp['razon_social'] ?? '') ?>
            </h2>
            <p class="text-xs font-mono text-emerald-400 mt-0.5">
                RIF: <?= e($emp['rif'] ?? '') ?> • Condición: Contribuyente <?= e($emp['tipo_contribuyente'] ?? '') ?> (Ret. IVA <?= e($emp['porcentaje_retencion_iva'] ?? '75') ?>%)
            </p>
            <p class="text-xs text-slate-400 mt-0.5"><?= e($emp['direccion_fiscal'] ?? '') ?></p>
        </div>
        <div class="text-left sm:text-right font-mono text-xs">
            <span id="lblTituloLibroMembrete" class="font-bold text-emerald-400 uppercase block">
                <?= $tab === 'ventas' ? 'LIBRO AUXILIAR DE VENTAS' : 'LIBRO AUXILIAR DE COMPRAS NACIONALES' ?>
            </span>
            <span class="text-slate-400 block mt-0.5">
                PERÍODO FISCAL: <?= e($periodo) ?>
            </span>
        </div>
    </div>

    <!-- =======================================================================
         PESTAÑA 1: LIBRO DE COMPRAS SENIAT
    ======================================================================== -->
    <section id="seccionLibro_compras" class="seccion-libro <?= $tab === 'compras' ? '' : 'hidden' ?> space-y-6 print-container">
        <div class="rounded-2xl bg-slate-900 border border-slate-800 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-[11px]">
                    <thead class="bg-slate-950 uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-2.5 text-center">N°</th>
                            <th class="py-3 px-2.5">Fecha</th>
                            <th class="py-3 px-2.5">RIF</th>
                            <th class="py-3 px-3 min-w-[180px]">Nombre o Razón Social</th>
                            <th class="py-3 px-2.5">N° Factura</th>
                            <th class="py-3 px-2.5">N° Control</th>
                            <th class="py-3 px-2">Tipo</th>
                            <th class="py-3 px-2.5">Fact. Afect.</th>
                            <th class="py-3 px-3 text-right">Total c/IVA</th>
                            <th class="py-3 px-3 text-right">Exentas</th>
                            <th class="py-3 px-3 text-right">Base Imp. 16%</th>
                            <th class="py-3 px-3 text-right">IVA 16%</th>
                            <th class="py-3 px-3 text-right">Base 8%/31%</th>
                            <th class="py-3 px-3 text-right">IVA 8%/31%</th>
                            <th class="py-3 px-2.5 text-right">IGTF 3%</th>
                            <th class="py-3 px-3 text-right">IVA Retenido</th>
                            <th class="py-3 px-3 text-center">Comprobante Ret.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono">
                        <?php if (!empty($compras)): ?>
                            <?php foreach ($compras as $idx => $c): ?>
                                <?php
                                $baseOtras = (float)$c['base_imponible_reducida'] + (float)($c['base_imponible_adicional'] ?? 0);
                                $ivaOtras  = (float)$c['monto_iva_reducida'] + (float)($c['monto_iva_adicional'] ?? 0);
                                ?>
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    <td class="py-2.5 px-2.5 text-center text-slate-400"><?= $idx + 1 ?></td>
                                    <td class="py-2.5 px-2.5 whitespace-nowrap text-slate-300"><?= e($c['fecha_factura']) ?></td>
                                    <td class="py-2.5 px-2.5 whitespace-nowrap font-semibold text-emerald-400"><?= e($c['rif_proveedor']) ?></td>
                                    <td class="py-2.5 px-3 font-sans font-medium text-slate-100"><?= e($c['nombre_proveedor']) ?></td>
                                    <td class="py-2.5 px-2.5 whitespace-nowrap text-slate-200"><?= e($c['numero_factura']) ?></td>
                                    <td class="py-2.5 px-2.5 whitespace-nowrap text-slate-400"><?= e($c['numero_control']) ?></td>
                                    <td class="py-2.5 px-2 whitespace-nowrap text-slate-300"><?= e($c['tipo_transaccion'] ?? '01-Reg') ?></td>
                                    <td class="py-2.5 px-2.5 whitespace-nowrap text-slate-400"><?= e($c['numero_factura_afectada'] ?: '—') ?></td>
                                    <td class="py-2.5 px-3 text-right font-bold text-white"><?= formato_ves($c['total_compra_con_iva'], false) ?></td>
                                    <td class="py-2.5 px-3 text-right text-slate-300"><?= formato_ves($c['compras_exentas'], false) ?></td>
                                    <td class="py-2.5 px-3 text-right text-slate-200"><?= formato_ves($c['base_imponible_general'], false) ?></td>
                                    <td class="py-2.5 px-3 text-right text-sky-300 font-semibold"><?= formato_ves($c['monto_iva_general'], false) ?></td>
                                    <td class="py-2.5 px-3 text-right text-slate-300"><?= formato_ves($baseOtras, false) ?></td>
                                    <td class="py-2.5 px-3 text-right text-slate-300"><?= formato_ves($ivaOtras, false) ?></td>
                                    <td class="py-2.5 px-2.5 text-right text-slate-300"><?= formato_ves($c['monto_igtf'] ?? 0, false) ?></td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-amber-300"><?= formato_ves($c['monto_iva_retenido'] ?? 0, false) ?></td>
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <?php if (!empty($c['retencion_iva_id'])): ?>
                                            <a href="<?= e(route_url('tributario', 'comprobanteIva', ['id' => (int)$c['retencion_iva_id']])) ?>"
                                               target="_blank"
                                               class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-300 border border-amber-500/30 hover:underline">
                                                <?= e($c['comprobante_iva_numero']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-slate-500">Sin retención</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="17" class="py-10 text-center font-sans text-slate-500">
                                    No existen facturas de compra registradas para el período <?= e($periodo) ?>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-slate-950 border-t-2 border-emerald-500/40 font-mono font-bold text-[11px]">
                        <tr>
                            <td colspan="8" class="py-3.5 px-3 text-right uppercase text-white">TOTALES DEL LIBRO DE COMPRAS (Bs.):</td>
                            <td class="py-3.5 px-3 text-right text-emerald-400"><?= formato_ves($totC['total_con_iva'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-slate-200"><?= formato_ves($totC['exentas'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-slate-200"><?= formato_ves($totC['base_gen'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-sky-300"><?= formato_ves($totC['iva_gen'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-slate-200"><?= formato_ves($totC['base_red'] + $totC['base_adi'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-slate-200"><?= formato_ves($totC['iva_red'] + $totC['iva_adi'], false) ?></td>
                            <td class="py-3.5 px-2.5 text-right text-slate-200"><?= formato_ves($totC['monto_igtf'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-amber-300"><?= formato_ves($totC['iva_retenido'], false) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </section>

    <!-- =======================================================================
         PESTAÑA 2: LIBRO DE VENTAS SENIAT
    ======================================================================== -->
    <section id="seccionLibro_ventas" class="seccion-libro <?= $tab === 'ventas' ? '' : 'hidden' ?> space-y-6 print-container">
        <div class="rounded-2xl bg-slate-900 border border-slate-800 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-[11px]">
                    <thead class="bg-slate-950 uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-2.5 text-center">N°</th>
                            <th class="py-3 px-2.5">Fecha</th>
                            <th class="py-3 px-2.5">RIF Cliente</th>
                            <th class="py-3 px-3 min-w-[180px]">Nombre o Razón Social</th>
                            <th class="py-3 px-2.5">Condición</th>
                            <th class="py-3 px-2.5">N° Factura</th>
                            <th class="py-3 px-2.5">N° Control</th>
                            <th class="py-3 px-2">Tipo</th>
                            <th class="py-3 px-3 text-right">Total Venta c/IVA</th>
                            <th class="py-3 px-3 text-right">Ventas Exentas</th>
                            <th class="py-3 px-3 text-right">Base Imp. 16%</th>
                            <th class="py-3 px-3 text-right">Débito IVA 16%</th>
                            <th class="py-3 px-3 text-right">IGTF 3%</th>
                            <th class="py-3 px-3 text-right">IVA Ret. Cliente</th>
                            <th class="py-3 px-3 text-center">N° Comp. Cliente</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono">
                        <?php if (!empty($ventas)): ?>
                            <?php foreach ($ventas as $idx => $v): ?>
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    <td class="py-2.5 px-2.5 text-center text-slate-400"><?= $idx + 1 ?></td>
                                    <td class="py-2.5 px-2.5 whitespace-nowrap text-slate-300"><?= e($v['fecha_factura']) ?></td>
                                    <td class="py-2.5 px-2.5 whitespace-nowrap font-semibold text-emerald-400"><?= e($v['rif_cliente']) ?></td>
                                    <td class="py-2.5 px-3 font-sans font-medium text-slate-100"><?= e($v['nombre_cliente']) ?></td>
                                    <td class="py-2.5 px-2.5 font-sans text-slate-300"><?= e($v['tipo_contribuyente_cliente']) ?></td>
                                    <td class="py-2.5 px-2.5 whitespace-nowrap text-slate-200"><?= e($v['numero_factura']) ?></td>
                                    <td class="py-2.5 px-2.5 whitespace-nowrap text-slate-400"><?= e($v['numero_control']) ?></td>
                                    <td class="py-2.5 px-2 whitespace-nowrap text-slate-300"><?= e($v['tipo_transaccion'] ?? '01-Reg') ?></td>
                                    <td class="py-2.5 px-3 text-right font-bold text-white"><?= formato_ves($v['total_venta_con_iva'], false) ?></td>
                                    <td class="py-2.5 px-3 text-right text-slate-300"><?= formato_ves($v['ventas_exentas'], false) ?></td>
                                    <td class="py-2.5 px-3 text-right text-slate-200"><?= formato_ves($v['base_imponible_general'], false) ?></td>
                                    <td class="py-2.5 px-3 text-right text-amber-300 font-semibold"><?= formato_ves($v['monto_iva_general'], false) ?></td>
                                    <td class="py-2.5 px-3 text-right text-slate-300"><?= formato_ves($v['monto_igtf'], false) ?></td>
                                    <td class="py-2.5 px-3 text-right text-sky-300 font-semibold"><?= formato_ves($v['iva_retenido_por_cliente'], false) ?></td>
                                    <td class="py-2.5 px-3 text-center text-slate-400"><?= e($v['numero_comprobante_ret_cliente'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="15" class="py-10 text-center font-sans text-slate-500">
                                    No existen facturas de venta registradas para el período <?= e($periodo) ?>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-slate-950 border-t-2 border-emerald-500/40 font-mono font-bold text-[11px]">
                        <tr>
                            <td colspan="8" class="py-3.5 px-3 text-right uppercase text-white">TOTALES DEL LIBRO DE VENTAS (Bs.):</td>
                            <td class="py-3.5 px-3 text-right text-emerald-400"><?= formato_ves($totV['total_con_iva'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-slate-200"><?= formato_ves($totV['exentas'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-slate-200"><?= formato_ves($totV['base_gen'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-amber-300"><?= formato_ves($totV['iva_gen'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-slate-200"><?= formato_ves($totV['monto_igtf'], false) ?></td>
                            <td class="py-3.5 px-3 text-right text-sky-300"><?= formato_ves($totV['iva_ret_cliente'], false) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </section>

    <!-- =======================================================================
         PESTAÑA 3: RESUMEN FISCAL ARTÍCULO 72 (CUADRO FORMA 30 SENIAT)
    ======================================================================== -->
    <section id="seccionLibro_resumen72" class="seccion-libro <?= $tab === 'resumen72' ? '' : 'hidden' ?> print-container">
        <?php
        $deb = $art72['debitos'];
        $cre = $art72['creditos'];
        $aut = $art72['autoliquidacion'];
        ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Débitos y Créditos Fiscales (8 cols) -->
            <div class="lg:col-span-8 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl overflow-hidden">
                <div class="px-5 py-3.5 bg-slate-950 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-xs font-mono font-bold uppercase text-emerald-400">
                        ARTÍCULO 72 DEL REGLAMENTO DE LA LEY DEL IVA — RESUMEN DEL PERÍODO <?= e($periodo) ?>
                    </h3>
                    <span class="text-[11px] font-mono text-slate-400">Forma 30 SENIAT</span>
                </div>

                <div class="p-5 space-y-6">
                    <!-- Subtabla Débitos Fiscales -->
                    <div>
                        <h4 class="text-xs font-bold uppercase text-amber-300 mb-2">1. DÉBITOS FISCALES (VENTAS)</h4>
                        <table class="w-full text-xs border-collapse">
                            <thead class="bg-slate-950 text-[11px] uppercase text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="py-2 px-3 text-left">Concepto</th>
                                    <th class="py-2 px-3 text-center w-20">Casilla</th>
                                    <th class="py-2 px-3 text-right w-40">Base Imponible</th>
                                    <th class="py-2 px-3 text-right w-40">Débito Fiscal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 font-mono">
                                <tr>
                                    <td class="py-2 px-3 font-sans text-slate-200">Ventas Internas No Gravadas (Exentas)</td>
                                    <td class="py-2 px-3 text-center text-slate-400">40</td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($deb['casilla_40_no_gravadas'] ?? 0, false) ?></td>
                                    <td class="py-2 px-3 text-right text-slate-500">0,00</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-sans text-slate-200">Ventas Internas Gravadas por Alícuota General (16%)</td>
                                    <td class="py-2 px-3 text-center text-slate-400">42</td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($deb['casilla_42_base_general'] ?? 0, false) ?></td>
                                    <td class="py-2 px-3 text-right text-amber-300"><?= formato_ves($deb['casilla_42_deb_general'] ?? 0, false) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-sans text-slate-200">Ventas Internas Gravadas por Alícuota General más Adicional (31%)</td>
                                    <td class="py-2 px-3 text-center text-slate-400">442</td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($deb['casilla_442_base_adic'] ?? 0, false) ?></td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($deb['casilla_442_deb_adic'] ?? 0, false) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-sans text-slate-200">Ventas Internas Gravadas por Alícuota Reducida (8%)</td>
                                    <td class="py-2 px-3 text-center text-slate-400">443</td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($deb['casilla_443_base_red'] ?? 0, false) ?></td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($deb['casilla_443_deb_red'] ?? 0, false) ?></td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-950 border-t border-slate-800 font-mono font-bold">
                                <tr>
                                    <td class="py-2.5 px-3 font-sans text-white">Total Ventas y Débitos Fiscales del Período</td>
                                    <td class="py-2.5 px-3 text-center text-amber-300">46 / 49</td>
                                    <td class="py-2.5 px-3 text-right text-white"><?= formato_ves($deb['casilla_46_total_base'] ?? 0) ?></td>
                                    <td class="py-2.5 px-3 text-right text-amber-300"><?= formato_ves($deb['casilla_49_total_debito'] ?? 0) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Subtabla Créditos Fiscales -->
                    <div>
                        <h4 class="text-xs font-bold uppercase text-sky-300 mb-2">2. CRÉDITOS FISCALES (COMPRAS)</h4>
                        <table class="w-full text-xs border-collapse">
                            <thead class="bg-slate-950 text-[11px] uppercase text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="py-2 px-3 text-left">Concepto</th>
                                    <th class="py-2 px-3 text-center w-20">Casilla</th>
                                    <th class="py-2 px-3 text-right w-40">Base Imponible</th>
                                    <th class="py-2 px-3 text-right w-40">Crédito Fiscal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 font-mono">
                                <tr>
                                    <td class="py-2 px-3 font-sans text-slate-200">Compras No Gravadas y/o Sin Derecho a Crédito Fiscal</td>
                                    <td class="py-2 px-3 text-center text-slate-400">30</td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($cre['casilla_30_no_gravadas'] ?? 0, false) ?></td>
                                    <td class="py-2 px-3 text-right text-slate-500">0,00</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-sans text-slate-200">Compras Internas Gravadas por Alícuota General (16%)</td>
                                    <td class="py-2 px-3 text-center text-slate-400">33</td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($cre['casilla_33_base_general'] ?? 0, false) ?></td>
                                    <td class="py-2 px-3 text-right text-sky-300"><?= formato_ves($cre['casilla_33_cred_general'] ?? 0, false) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-sans text-slate-200">Compras Internas Gravadas por Alícuota General más Adicional (31%)</td>
                                    <td class="py-2 px-3 text-center text-slate-400">332</td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($cre['casilla_332_base_adic'] ?? 0, false) ?></td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($cre['casilla_332_cred_adic'] ?? 0, false) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-sans text-slate-200">Compras Internas Gravadas por Alícuota Reducida (8%)</td>
                                    <td class="py-2 px-3 text-center text-slate-400">333</td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($cre['casilla_333_base_red'] ?? 0, false) ?></td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($cre['casilla_333_cred_red'] ?? 0, false) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-sans font-medium text-slate-200">Total Compras y Créditos Fiscales del Período</td>
                                    <td class="py-2 px-3 text-center text-slate-400">35</td>
                                    <td class="py-2 px-3 text-right font-semibold"><?= formato_ves($cre['casilla_35_total_base'] ?? 0, false) ?></td>
                                    <td class="py-2 px-3 text-right font-semibold text-sky-300"><?= formato_ves($cre['casilla_35_cred_periodo'] ?? 0, false) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-sans text-slate-200">Excedente de Crédito Fiscal del Mes Anterior</td>
                                    <td class="py-2 px-3 text-center text-slate-400">20</td>
                                    <td class="py-2 px-3 text-right text-slate-500">—</td>
                                    <td class="py-2 px-3 text-right"><?= formato_ves($cre['casilla_20_excedente_ant'] ?? 0, false) ?></td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-950 border-t border-slate-800 font-mono font-bold">
                                <tr>
                                    <td class="py-2.5 px-3 font-sans text-white" colspan="2">Total Créditos Fiscales Deducibles (Casilla 39)</td>
                                    <td class="py-2.5 px-3 text-right"></td>
                                    <td class="py-2.5 px-3 text-right text-sky-300"><?= formato_ves($cre['casilla_39_total_credito'] ?? 0) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha (4 cols): Autoliquidación y Retenciones -->
            <div class="lg:col-span-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl p-5 flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <h3 class="text-xs font-mono font-bold uppercase text-emerald-400 border-b border-slate-800 pb-2.5">
                        3. AUTOLIQUIDACIÓN IVA DEL PERÍODO
                    </h3>

                    <div class="space-y-3 text-xs font-mono">
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex justify-between items-center">
                            <span class="font-sans text-slate-300">Cuota Tributaria del Período (Casilla 53):</span>
                            <strong class="text-amber-300"><?= formato_ves($aut['casilla_53_cuota_periodo'] ?? 0) ?></strong>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex justify-between items-center">
                            <span class="font-sans text-slate-300">Excedente Crédito Mes Siguiente (Casilla 60):</span>
                            <strong class="text-sky-300"><?= formato_ves($aut['casilla_60_excedente_siguiente'] ?? 0) ?></strong>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex justify-between items-center">
                            <span class="font-sans text-slate-300">(-) Retenciones IVA Soportadas en Ventas:</span>
                            <strong class="text-emerald-400"><?= formato_ves($aut['retenciones_soportadas_periodo'] ?? 0) ?></strong>
                        </div>

                        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex justify-between items-center">
                            <span class="font-sans font-bold text-white uppercase">IVA Neto a Pagar SENIAT:</span>
                            <strong class="text-base text-emerald-400"><?= formato_ves($aut['total_iva_pagar'] ?? 0) ?></strong>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-800 space-y-2 text-xs font-mono">
                        <span class="text-slate-400 font-sans block">Obligación como Agente de Retención:</span>
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 flex justify-between items-center">
                            <span class="font-sans font-semibold text-amber-200">IVA Retenido a Proveedores:</span>
                            <strong class="text-amber-300"><?= formato_ves($aut['iva_retenido_a_terceros'] ?? 0) ?></strong>
                        </div>
                    </div>
                </div>

                <a href="<?= e(route_url('tributario', 'retenciones')) ?>"
                   class="w-full py-2.5 px-4 rounded-xl text-center text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-100 border border-slate-700 transition-colors no-print">
                    Ir a Comprobantes de Retención IVA / ISLR →
                </a>
            </div>
        </div>
    </section>

</div>

<!-- ===========================================================================
     MODAL 1: REGISTRAR FACTURA EN EL LIBRO DE COMPRAS
============================================================================ -->
<div id="modalRegistrarCompra" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 no-print">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">Registrar Factura / Documento en Libro de Compras</h3>
                <p class="text-xs font-mono text-emerald-400">
                    Cálculo automático de IVA (16%, 8%, 31%), IGTF (3%), Retención de IVA y Asiento Contable
                </p>
            </div>
            <button type="button" onclick="window.JurisTributario.cerrarModalCompra()"
                    class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="formRegistrarCompra" class="p-6 overflow-y-auto space-y-5">
            <?= Csrf::field() ?>
            <input type="hidden" name="periodo_fiscal" value="<?= e($periodo) ?>">

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Fecha de Factura <span class="text-rose-400">*</span></label>
                    <input type="date" name="fecha_factura" required value="<?= e($periodo . '-' . date('d')) ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-300 mb-1">RIF Proveedor <span class="text-rose-400">*</span></label>
                    <input type="text" id="comp_rif" name="rif_proveedor" required maxlength="15" placeholder="J-31445902-8"
                           class="input-rif-format w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono uppercase text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-6">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Nombre o Razón Social del Proveedor <span class="text-rose-400">*</span></label>
                    <input type="text" name="nombre_proveedor" required maxlength="200" placeholder="FERRETERÍA MATERIALES PUERTO, C.A."
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-12">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Dirección Fiscal del Proveedor (Para Comprobante de Retención)</label>
                    <input type="text" name="direccion_proveedor" maxlength="250" placeholder="Av. Principal, Local PB, Ciudad, Estado"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Tipo Documento</label>
                    <select name="tipo_documento" id="comp_tipo_doc"
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        <option value="FACTURA">Factura</option>
                        <option value="NOTA_DEBITO">Nota de Débito</option>
                        <option value="NOTA_CREDITO">Nota de Crédito</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Tipo Transacción</label>
                    <select name="tipo_transaccion"
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                        <option value="01-Reg">01-Reg (Registro)</option>
                        <option value="02-Comp">02-Comp (Complemento)</option>
                        <option value="03-Anul">03-Anul (Anulación)</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-300 mb-1">N° Factura <span class="text-rose-400">*</span></label>
                    <input type="text" name="numero_factura" required placeholder="00021256"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-300 mb-1">N° Control <span class="text-rose-400">*</span></label>
                    <input type="text" name="numero_control" required placeholder="00-0021256"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-300 mb-1">N° Fact. Afectada</label>
                    <input type="text" name="numero_factura_afectada" placeholder="Si es N/C o N/D"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <!-- Desglose de Montos en Tiempo Real -->
            <div class="pt-4 border-t border-slate-800 space-y-3">
                <h4 class="text-xs font-mono font-bold uppercase text-emerald-400">Desglose de Bases Imponibles y Alícuotas (Bs.)</h4>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Compras Exentas / Sin Derecho IVA</label>
                        <input type="number" step="0.01" min="0" id="comp_exentas" name="compras_exentas" value="0.00"
                               class="calc-compra w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-right text-white">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Base Imponible General (16%)</label>
                        <input type="number" step="0.01" min="0" id="comp_base_gen" name="base_imponible_general" value="0.00"
                               class="calc-compra w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-right text-white">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">IVA Alícuota General (16%)</label>
                        <input type="text" id="comp_iva_gen" readonly value="0,00"
                               class="w-full rounded-xl bg-slate-950/60 border border-slate-800 px-3 py-2 text-xs font-mono text-right text-sky-300 font-bold">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Base Imponible Reducida (8%)</label>
                        <input type="number" step="0.01" min="0" id="comp_base_red" name="base_imponible_reducida" value="0.00"
                               class="calc-compra w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-right text-white">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Base Imponible Adicional (31%)</label>
                        <input type="number" step="0.01" min="0" id="comp_base_adi" name="base_imponible_adicional" value="0.00"
                               class="calc-compra w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-right text-white">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Base IGTF Divisas (3%)</label>
                        <input type="number" step="0.01" min="0" id="comp_base_igtf" name="base_igtf" value="0.00"
                               class="calc-compra w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-right text-white">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">% Retención IVA a Aplicar</label>
                        <select id="comp_porc_ret" name="porcentaje_retencion_iva"
                                class="calc-compra w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-white">
                            <option value="0" <?= (int)($emp['porcentaje_retencion_iva'] ?? 75) === 0 ? 'selected' : '' ?>>0% (Sin Retención)</option>
                            <option value="75" <?= (int)($emp['porcentaje_retencion_iva'] ?? 75) === 75 ? 'selected' : '' ?>>75% (Contribuyente Especial)</option>
                            <option value="100" <?= (int)($emp['porcentaje_retencion_iva'] ?? 75) === 100 ? 'selected' : '' ?>>100% (Retención Total)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Monto IVA Retenido</label>
                        <input type="text" id="comp_iva_retenido_vista" readonly value="Bs. 0,00"
                               class="w-full rounded-xl bg-slate-950/60 border border-slate-800 px-3 py-2 text-xs font-mono text-right text-amber-300 font-bold">
                    </div>
                </div>

                <!-- Resumen Total Compra + Opciones de Automatización -->
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex flex-wrap items-center justify-between gap-4">
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs text-slate-200 cursor-pointer">
                            <input type="checkbox" name="emitir_comprobante_ret_iva" value="1" checked
                                   class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500">
                            <span>Emitir automáticamente el <strong>Comprobante de Retención de IVA (14 dígitos)</strong> si aplica retención</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-200 cursor-pointer">
                            <input type="checkbox" name="generar_asiento_automatico" value="1" checked
                                   class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500">
                            <span>Generar <strong>Asiento Contable de Partida Doble automáticamente</strong> en el Libro Diario</span>
                        </label>
                    </div>

                    <div class="text-right font-mono">
                        <span class="text-xs text-slate-400 block">TOTAL COMPRA CON IVA:</span>
                        <span id="comp_total_vista" class="text-lg font-bold text-emerald-400">Bs. 0,00</span>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end gap-3">
                <button type="button" onclick="window.JurisTributario.cerrarModalCompra()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700">
                    Cancelar
                </button>
                <button type="submit" id="btnSubmitCompra"
                        class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-950/50">
                    Guardar Factura en Libro de Compras
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================================
     MODAL 2: REGISTRAR FACTURA EN EL LIBRO DE VENTAS
============================================================================ -->
<div id="modalRegistrarVenta" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 no-print">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">Registrar Factura / Documento en Libro de Ventas</h3>
                <p class="text-xs font-mono text-sky-400">
                    Cálculo de Débito Fiscal IVA (16%, 8%, 31%), Retenciones Soportadas de Clientes Especiales y Asiento Contable
                </p>
            </div>
            <button type="button" onclick="window.JurisTributario.cerrarModalVenta()"
                    class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="formRegistrarVenta" class="p-6 overflow-y-auto space-y-5">
            <?= Csrf::field() ?>
            <input type="hidden" name="periodo_fiscal" value="<?= e($periodo) ?>">

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Fecha de Factura <span class="text-rose-400">*</span></label>
                    <input type="date" name="fecha_factura" required value="<?= e($periodo . '-' . date('d')) ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-white">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-300 mb-1">RIF Cliente <span class="text-rose-400">*</span></label>
                    <input type="text" name="rif_cliente" required maxlength="15" placeholder="J-30881920-1"
                           class="input-rif-format w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono uppercase text-white">
                </div>

                <div class="sm:col-span-6">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Nombre o Razón Social del Cliente <span class="text-rose-400">*</span></label>
                    <input type="text" name="nombre_cliente" required maxlength="200" placeholder="CONDOMINIO RESIDENCIAS TORRE DEL PARQUE"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs text-white">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Condición del Cliente</label>
                    <select name="tipo_contribuyente_cliente"
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-white">
                        <option value="Ordinario">Ordinario</option>
                        <option value="Especial">Especial (Agente Retención)</option>
                        <option value="No Sujeto">No Sujeto / Persona Natural</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Tipo Documento</label>
                    <select name="tipo_documento"
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-white">
                        <option value="FACTURA">Factura</option>
                        <option value="REPORTE_Z">Reporte Z Fiscal</option>
                        <option value="NOTA_DEBITO">Nota de Débito</option>
                        <option value="NOTA_CREDITO">Nota de Crédito</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-300 mb-1">N° Factura <span class="text-rose-400">*</span></label>
                    <input type="text" name="numero_factura" required placeholder="00001851"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-white">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-300 mb-1">N° Control <span class="text-rose-400">*</span></label>
                    <input type="text" name="numero_control" required placeholder="00-00001851"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-white">
                </div>
            </div>

            <!-- Desglose de Montos Venta -->
            <div class="pt-4 border-t border-slate-800 space-y-3">
                <h4 class="text-xs font-mono font-bold uppercase text-sky-400">Desglose de Ventas, Débito Fiscal y Retenciones de Clientes</h4>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Ventas Exentas / No Gravadas</label>
                        <input type="number" step="0.01" min="0" id="ven_exentas" name="ventas_exentas" value="0.00"
                               class="calc-venta w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-right text-white">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Base Imponible General (16%)</label>
                        <input type="number" step="0.01" min="0" id="ven_base_gen" name="base_imponible_general" value="0.00"
                               class="calc-venta w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-right text-white">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Débito Fiscal IVA (16%)</label>
                        <input type="text" id="ven_iva_gen" readonly value="0,00"
                               class="w-full rounded-xl bg-slate-950/60 border border-slate-800 px-3 py-2 text-xs font-mono text-right text-amber-300 font-bold">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Base IGTF Cobrado en Divisas (3%)</label>
                        <input type="number" step="0.01" min="0" id="ven_base_igtf" name="base_igtf" value="0.00"
                               class="calc-venta w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-right text-white">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">N° Comprobante Retención IVA Recibido del Cliente (Opcional)</label>
                        <input type="text" name="numero_comprobante_ret_cliente" maxlength="20" placeholder="Ej: 20261100000412"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-white">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Monto IVA Retenido por el Cliente Especial (Bs.)</label>
                        <input type="number" step="0.01" min="0" name="iva_retenido_por_cliente" value="0.00"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs font-mono text-right text-white">
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex flex-wrap items-center justify-between gap-4">
                    <label class="flex items-center gap-2 text-xs text-slate-200 cursor-pointer">
                        <input type="checkbox" name="generar_asiento_automatico" value="1" checked
                               class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500">
                        <span>Generar <strong>Asiento Contable de Partida Doble automáticamente</strong> en el Libro Diario</span>
                    </label>

                    <div class="text-right font-mono">
                        <span class="text-xs text-slate-400 block">TOTAL VENTA CON IVA:</span>
                        <span id="ven_total_vista" class="text-lg font-bold text-emerald-400">Bs. 0,00</span>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end gap-3">
                <button type="button" onclick="window.JurisTributario.cerrarModalVenta()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700">
                    Cancelar
                </button>
                <button type="submit" id="btnSubmitVenta"
                        class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 shadow-lg">
                    Guardar Factura en Libro de Ventas
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================================
     MODAL 3: CONFIGURACIÓN TRIBUTARIA DE LA EMPRESA (UT, SUSTRAENDO, EXCEDENTE)
============================================================================ -->
<div id="modalConfigFiscal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 no-print">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="px-6 py-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">Parámetros Tributarios SENIAT</h3>
                <p class="text-xs font-mono text-amber-400"><?= e($emp['razon_social'] ?? '') ?></p>
            </div>
            <button type="button" onclick="window.JurisTributario.cerrarModalConfigFiscal()"
                    class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="formConfigFiscal" class="p-6 space-y-4">
            <?= Csrf::field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Valor Unidad Tributaria (UT Bs.)</label>
                    <input type="number" step="0.01" min="0" name="valor_unidad_tributaria" required
                           value="<?= e((string)($cfg['valor_unidad_tributaria'] ?? 9.00)) ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs font-mono text-white">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Factor Sustraendo ISLR (83.3334)</label>
                    <input type="number" step="0.0001" min="0" name="factor_sustraendo_islr" required
                           value="<?= e((string)($cfg['factor_sustraendo_islr'] ?? 83.3334)) ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs font-mono text-white">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">% Retención IVA por Defecto</label>
                    <select name="porcentaje_retencion_iva"
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs font-mono text-white">
                        <option value="0" <?= (int)($cfg['porcentaje_retencion_iva'] ?? 75) === 0 ? 'selected' : '' ?>>0% (Contribuyente Ordinario)</option>
                        <option value="75" <?= (int)($cfg['porcentaje_retencion_iva'] ?? 75) === 75 ? 'selected' : '' ?>>75% (Sujeto Pasivo Especial)</option>
                        <option value="100" <?= (int)($cfg['porcentaje_retencion_iva'] ?? 75) === 100 ? 'selected' : '' ?>>100% (Retención Total)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Excedente Créd. Fiscal Mes Anterior (Casilla 20)</label>
                    <input type="number" step="0.01" min="0" name="excedente_credito_fiscal_anterior"
                           value="<?= e((string)($cfg['excedente_credito_fiscal_anterior'] ?? 0.00)) ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs font-mono text-white">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                <button type="button" onclick="window.JurisTributario.cerrarModalConfigFiscal()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500">
                    Guardar Configuración SENIAT
                </button>
            </div>
        </form>
    </div>
</div>
