<?php
declare(strict_types=1);

$periodoActual  = (string)($precalculo['periodo'] ?? date('Y-m'));
$montoBase      = (float)($precalculo['monto_mensual_base'] ?? 1800.00);
$valorDiario    = (float)($precalculo['valor_diario'] ?? 60.00);
$filasPreCest   = $precalculo['filas'] ?? [];
?>
<!-- Cabecera del Submódulo de Bono de Alimentación / Cestaticket -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6 no-print">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-amber-600 mb-1">
            <span>Ley del Cestaticket Socialista</span>
            <span>•</span>
            <span class="font-mono"><?= e((string)$empresaActiva['rif']) ?></span>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
            Control y Liquidación de Bono de Alimentación (Cestaticket)
        </h2>
        <p class="text-sm text-slate-600 mt-0.5">
            Cálculo mensual sobre base de 30 días, descuento proporcional por inasistencias injustificadas (`Monto Mensual / 30 * Días`) y emisión de recibos legales.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <a href="<?= e(route_url('nomina', 'index')) ?>"
           class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition">
            Nómina General
        </a>
        <a href="<?= e(route_url('nomina', 'recibos')) ?>"
           class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30 transition">
            Centro de Recibos
        </a>
    </div>
</div>

<!-- SECCIÓN 1: GENERADOR MENSUAL DE BONO DE ALIMENTACIÓN -->
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-8 overflow-hidden no-print">
    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                Generar Lote Mensual de Bono de Alimentación
            </h3>
            <p class="text-xs text-slate-500">
                Beneficio social de carácter no remunerativo (Art. 4 y Art. 7 de la Ley del Cestaticket Socialista).
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    onclick="window.JurisApp.abrirModalTasaBcv()"
                    class="text-xs font-mono px-3 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 border border-emerald-500/30 transition">
                Tasa BCV: <strong>Bs. <?= e(number_format((float)($configLaboral['tasa_bcv_usd'] ?? 45.00), 2, ',', '.')) ?> / $</strong> (Cambiar)
            </button>
            <div class="text-xs font-mono px-3 py-1.5 rounded-xl bg-amber-500/10 text-amber-700 border border-amber-500/30">
                Alícuota Diaria Base: <strong id="lblValorDiarioCest"><?= e(formato_ves($valorDiario)) ?></strong> / día
            </div>
        </div>
    </div>

    <div class="p-6 border-b border-slate-200 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Período (Mes/Año)</label>
            <input type="month" id="cest_periodo" value="<?= e($periodoActual) ?>"
                   class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha de Emisión</label>
            <input type="date" id="cest_fecha_emision" value="<?= e(date('Y-m-d')) ?>"
                   class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Indexación ($40 × Tasa BCV)</label>
            <button type="button"
                    onclick="window.JurisNomina?.aplicarCestaticketPorTasaBcv(<?= e((string)($configLaboral['tasa_bcv_usd'] ?? 45.00)) ?>)"
                    class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 border border-emerald-500/30 transition">
                Calcular $40 × <?= e(number_format((float)($configLaboral['tasa_bcv_usd'] ?? 45.00), 2, ',', '.')) ?>
            </button>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Monto Mensual Base (Bs.)</label>
            <input type="number" step="0.01" min="1" id="cest_monto_mensual" value="<?= e((string)$montoBase) ?>"
                   oninput="window.JurisNomina?.recalcularTodasLasFilasCestaticket()"
                   class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold bg-slate-50 border border-slate-300 text-amber-600">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Observaciones</label>
            <input type="text" id="cest_observaciones" value="Bono de Alimentación Mes <?= e($periodoActual) ?>"
                   class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="tablaPreCestaticket">
            <thead>
                <tr class="bg-slate-100/80 border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    <th class="py-3 px-4">N°</th>
                    <th class="py-3 px-4">Trabajador / Cédula</th>
                    <th class="py-3 px-3">Cargo / Ingreso</th>
                    <th class="py-3 px-2 text-center">Días Hábiles</th>
                    <th class="py-3 px-2 text-center">Sáb / Dom</th>
                    <th class="py-3 px-2 text-center">Feriados</th>
                    <th class="py-3 px-2 text-center bg-rose-500/5">Inasistencias</th>
                    <th class="py-3 px-2 text-center bg-emerald-500/5">Días Efectivos</th>
                    <th class="py-3 px-3 text-right">Valor Diario</th>
                    <th class="py-3 px-3 text-right text-rose-500">Descuento</th>
                    <th class="py-3 px-4 text-right bg-amber-500/10">Total Cestaticket</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-xs">
                <?php if (empty($filasPreCest)): ?>
                    <tr>
                        <td colspan="11" class="py-8 text-center text-slate-500">
                            No hay trabajadores activos con el beneficio de Cestaticket habilitado en esta empresa.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filasPreCest as $idx => $f): ?>
                        <tr class="fila-precestaticket hover:bg-slate-50/70 transition"
                            data-trabajador-id="<?= (int)$f['trabajador_id'] ?>">
                            <td class="py-2.5 px-4 font-mono"><?= $idx + 1 ?></td>
                            <td class="py-2.5 px-4">
                                <div class="font-semibold text-slate-900"><?= e((string)$f['nombre_completo']) ?></div>
                                <div class="font-mono text-[11px] text-slate-500"><?= e((string)$f['cedula']) ?></div>
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="text-slate-800"><?= e((string)$f['cargo']) ?></div>
                                <div class="text-[10px] font-mono text-slate-500">Ingreso: <?= e(date('d/m/Y', strtotime((string)$f['fecha_ingreso']))) ?></div>
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <input type="number" step="0.5" min="0" max="30"
                                       class="inp-cest-habiles w-16 px-2 py-1 rounded-lg text-center font-mono text-xs bg-white border border-slate-300 text-slate-900"
                                       value="<?= e((string)$f['dias_habiles']) ?>"
                                       oninput="window.JurisNomina?.recalcularFilaCestaticket(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <input type="number" step="0.5" min="0" max="12"
                                       class="inp-cest-descanso w-16 px-2 py-1 rounded-lg text-center font-mono text-xs bg-white border border-slate-300 text-slate-900"
                                       value="<?= e((string)$f['dias_descanso']) ?>"
                                       oninput="window.JurisNomina?.recalcularFilaCestaticket(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <input type="number" step="0.5" min="0" max="10"
                                       class="inp-cest-feriados w-16 px-2 py-1 rounded-lg text-center font-mono text-xs bg-white border border-slate-300 text-slate-900"
                                       value="<?= e((string)$f['dias_feriados']) ?>"
                                       oninput="window.JurisNomina?.recalcularFilaCestaticket(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-2 text-center bg-rose-500/5">
                                <input type="number" step="0.5" min="0" max="30"
                                       class="inp-cest-inasistencias w-16 px-2 py-1 rounded-lg text-center font-mono text-xs bg-white border border-rose-300 text-rose-600"
                                       value="<?= e((string)$f['dias_inasistencia']) ?>"
                                       oninput="window.JurisNomina?.recalcularFilaCestaticket(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-2 text-center font-mono font-bold text-emerald-600 bg-emerald-500/5 lbl-cest-efectivos">
                                30
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-600 lbl-cest-diario">
                                <?= e(number_format($valorDiario, 2, ',', '.')) ?>
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-rose-600 lbl-cest-descuento">
                                0,00
                            </td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-sm text-amber-600 bg-amber-500/10 lbl-cest-total">
                                <?= e(formato_ves($montoBase)) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="p-6 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
            <input type="checkbox" id="cest_generar_asiento" class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500">
            <span class="text-xs font-semibold text-slate-800">
                Generar asiento contable en el Libro Diario (`6.1.01.009 Beneficio de Alimentación` contra `2.4.01.004`)
            </span>
        </label>

        <div class="flex items-center gap-4">
            <div class="text-right font-mono px-4 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30">
                <span class="text-[10px] uppercase tracking-wider text-amber-700 block">Total Bono Alimentación</span>
                <span id="lblTotalGeneralCestaticket" class="text-base font-bold text-amber-700">Bs. 0,00</span>
            </div>

            <button type="button"
                    onclick="window.JurisNomina?.procesarYGuardarCestaticket()"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white shadow-lg shadow-amber-950/30 transition">
                Registrar Lote de Cestaticket
            </button>
        </div>
    </div>
</div>

<!-- SECCIÓN 2: LOTES DE CESTATICKET PROCESADOS Y SÁBANA DE CONTROL -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-1 bg-white border border-slate-200 rounded-2xl p-4 shadow-sm no-print">
        <h3 class="text-sm font-bold text-slate-900 mb-3">Historial de Lotes Cestaticket</h3>
        <?php if (empty($lotesCestaticket)): ?>
            <p class="text-xs text-slate-500 py-6 text-center">No hay lotes procesados.</p>
        <?php else: ?>
            <div class="space-y-2.5">
                <?php foreach ($lotesCestaticket as $lc): ?>
                    <?php $activoLote = isset($loteSeleccionado['id']) && (int)$loteSeleccionado['id'] === (int)$lc['id']; ?>
                    <a href="<?= e(route_url('nomina', 'cestaticket', ['lote_id' => $lc['id']])) ?>"
                       class="block p-3 rounded-xl border transition <?= $activoLote
                           ? 'bg-amber-500/10 border-amber-500/40'
                           : 'bg-slate-50 border-slate-200 hover:border-slate-400' ?>">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-xs text-amber-600"><?= e((string)$lc['codigo_lote']) ?></span>
                            <span class="font-mono text-[11px] text-slate-500"><?= e((string)$lc['periodo']) ?></span>
                        </div>
                        <div class="text-xs font-semibold text-slate-800 mt-1">
                            <?= e((string)($lc['observaciones'] ?: 'Bono de Alimentación')) ?>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-[11px] font-mono">
                            <span class="text-slate-500"><?= (int)$lc['total_trabajadores'] ?> trab.</span>
                            <span class="font-bold text-slate-900"><?= e(formato_ves((float)$lc['total_pagar'])) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="lg:col-span-3 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm overflow-hidden">
        <?php if (!$loteSeleccionado): ?>
            <div class="py-12 text-center text-slate-500 text-sm">
                Seleccione un lote de Bono de Alimentación para visualizar e imprimir sus comprobantes.
            </div>
        <?php else: ?>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 mb-4 border-b border-slate-200">
                <div>
                    <div class="text-xs font-mono font-bold text-amber-600">
                        <?= e((string)$empresaActiva['razon_social']) ?> • RIF: <?= e((string)$empresaActiva['rif']) ?>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">
                        BONO DE ALIMENTACIÓN DE PERSONAL — <?= e((string)$loteSeleccionado['codigo_lote']) ?>
                    </h3>
                    <p class="text-xs text-slate-500">
                        Período: <strong><?= e((string)$loteSeleccionado['periodo']) ?></strong> • Fecha Emisión: <?= e(date('d/m/Y', strtotime((string)$loteSeleccionado['fecha_emision']))) ?>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 no-print">
                    <a href="<?= e(route_url('nomina', 'exportarCestaticketCsv', ['lote_id' => $loteSeleccionado['id']])) ?>"
                       class="px-3 py-2 rounded-xl text-xs font-semibold bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 border border-emerald-500/30 transition">
                        Exportar Excel (CSV)
                    </a>
                    <a href="<?= e(route_url('nomina', 'cestaticketPrint', ['lote_id' => $loteSeleccionado['id']])) ?>"
                       target="_blank"
                       class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Imprimir Relación
                    </a>
                    <a href="<?= e(route_url('nomina', 'reciboPrint', ['cestaticket_lote_id' => $loteSeleccionado['id']])) ?>"
                       target="_blank"
                       class="px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white shadow transition">
                        Imprimir Todos los Recibos Cestaticket
                    </a>
                </div>
            </div>

            <?php
            $tituloReporte    = 'RELACIÓN DE BONO DE ALIMENTACIÓN — ' . (string)$loteSeleccionado['codigo_lote'];
            $subtituloPeriodo = 'Período: ' . (string)$loteSeleccionado['periodo'];
            $claseContenedor  = 'hidden print:block mb-4';
            require ROOT_PATH . '/views/layouts/membrete_reporte.php';
            ?>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-300 text-[10px] font-bold uppercase text-slate-600">
                            <th class="py-2.5 px-3">N°</th>
                            <th class="py-2.5 px-3">Cédula / Trabajador</th>
                            <th class="py-2.5 px-2 text-center">Días Hábiles</th>
                            <th class="py-2.5 px-2 text-center">Sáb/Dom</th>
                            <th class="py-2.5 px-2 text-center">Feriados</th>
                            <th class="py-2.5 px-2 text-center">Inasist.</th>
                            <th class="py-2.5 px-2 text-center">Días Efectivos</th>
                            <th class="py-2.5 px-2 text-right">Valor Diario</th>
                            <th class="py-2.5 px-3 text-right">Total a Pagar</th>
                            <th class="py-2.5 px-3 text-right no-print">Recibo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($loteSeleccionado['detalles'] as $idx => $det): ?>
                            <tr>
                                <td class="py-2.5 px-3 font-mono"><?= $idx + 1 ?></td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900"><?= e((string)$det['nombre_completo']) ?></div>
                                    <div class="font-mono text-[10px] text-slate-500"><?= e((string)$det['cedula']) ?> • <?= e((string)$det['cargo']) ?></div>
                                </td>
                                <td class="py-2.5 px-2 text-center font-mono"><?= (float)$det['dias_habiles'] ?></td>
                                <td class="py-2.5 px-2 text-center font-mono"><?= (float)$det['dias_descanso'] ?></td>
                                <td class="py-2.5 px-2 text-center font-mono"><?= (float)$det['dias_feriados'] ?></td>
                                <td class="py-2.5 px-2 text-center font-mono text-rose-500"><?= (float)$det['dias_inasistencia'] ?></td>
                                <td class="py-2.5 px-2 text-center font-mono font-bold"><?= (float)$det['dias_efectivos'] ?></td>
                                <td class="py-2.5 px-2 text-right font-mono"><?= e(number_format((float)$det['valor_diario'], 2, ',', '.')) ?></td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-amber-600"><?= e(formato_ves((float)$det['total_cestaticket'])) ?></td>
                                <td class="py-2.5 px-3 text-right no-print">
                                    <a href="<?= e(route_url('nomina', 'reciboPrint', ['cestaticket_detalle_id' => $det['id']])) ?>"
                                       target="_blank"
                                       class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 hover:bg-amber-600 hover:text-white text-slate-700 transition">
                                        Recibo Legal
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-mono font-bold text-xs border-t-2 border-slate-300">
                            <td colspan="8" class="py-3 px-3 text-right uppercase">Total Bono de Alimentación del Lote:</td>
                            <td class="py-3 px-3 text-right text-amber-600"><?= e(formato_ves((float)$loteSeleccionado['total_pagar'])) ?></td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php
            $clasePie = 'hidden print:flex mt-6 pt-3 border-t border-slate-300';
            require ROOT_PATH . '/views/layouts/pie_reporte.php';
            ?>
        <?php endif; ?>
    </div>
</div>
