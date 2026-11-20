<?php
declare(strict_types=1);
?>
<!-- Cabecera del Centro de Emisión de Recibos LOTTT -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-600 mb-1">
            <span>Art. 106 LOTTT y Ley Cestaticket</span>
            <span>•</span>
            <span class="font-mono"><?= e((string)$empresaActiva['rif']) ?></span>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
            Emisión e Impresión de Recibos de Pago y Bono de Alimentación
        </h2>
        <p class="text-sm text-slate-600 mt-0.5">
            Consulte lotes procesados e imprima recibos individuales o el lote completo con membrete fiscal, desglose de asignaciones/deducciones y firma del trabajador.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <a href="<?= e(route_url('nomina', 'index')) ?>"
           class="px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition">
            Ir a Nómina General
        </a>
        <a href="<?= e(route_url('nomina', 'cestaticket')) ?>"
           class="px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 border border-amber-500/30 transition">
            Ir a Bono de Alimentación
        </a>
    </div>
</div>

<!-- Selector de Lote de Nómina y Lote de Cestaticket -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Recibos de Nómina de Sueldos y Salarios -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 mb-4 border-b border-slate-200">
            <div>
                <h3 class="text-base font-bold text-slate-900">
                    1. Recibos de Pago de Sueldos y Salarios (Art. 106 LOTTT)
                </h3>
                <p class="text-xs text-slate-500">
                    Desglose de días hábiles, descanso, feriados, IVSS, RPE, FAOV y neto en letras.
                </p>
            </div>

            <?php if ($nominaSeleccionada): ?>
                <a href="<?= e(route_url('nomina', 'reciboPrint', ['nomina_id' => $nominaSeleccionada['id']])) ?>"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30 transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir Todos los Recibos del Lote
                </a>
            <?php endif; ?>
        </div>

        <form method="POST" action="<?= e(route_url('nomina', 'recibos')) ?>" class="mb-4">
            <?= \Core\Csrf::field() ?>
            <?php if (!empty($cestaticketSeleccionado['id'])): ?>
                <input type="hidden" name="cestaticket_id" value="<?= (int)$cestaticketSeleccionado['id'] ?>">
            <?php endif; ?>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Seleccionar Lote de Nómina Procesado:</label>
            <select name="nomina_id" onchange="this.form.submit()"
                    class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                <?php foreach ($lotesNomina as $ln): ?>
                    <option value="<?= (int)$ln['id'] ?>" <?= isset($nominaSeleccionada['id']) && (int)$nominaSeleccionada['id'] === (int)$ln['id'] ? 'selected' : '' ?>>
                        <?= e((string)$ln['codigo_nomina']) ?> — <?= e((string)($ln['descripcion'] ?: $ln['tipo_frecuencia'])) ?> (<?= e(formato_ves((float)$ln['total_neto_pagar'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if (!$nominaSeleccionada): ?>
            <p class="text-xs text-slate-500 py-8 text-center">No hay lotes de nómina procesados en esta empresa.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold uppercase text-slate-500">
                            <th class="py-2.5 px-3">Trabajador</th>
                            <th class="py-2.5 px-2 text-right">Asignaciones</th>
                            <th class="py-2.5 px-2 text-right">Deducciones</th>
                            <th class="py-2.5 px-2 text-right">Neto a Pagar</th>
                            <th class="py-2.5 px-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($nominaSeleccionada['detalles'] as $det): ?>
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900"><?= e((string)$det['nombre_completo']) ?></div>
                                    <div class="font-mono text-[10px] text-slate-500"><?= e((string)$det['cedula']) ?> • <?= e((string)$det['cargo']) ?></div>
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono text-emerald-600">
                                    <?= e(formato_ves((float)$det['total_asignaciones'])) ?>
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono text-rose-600">
                                    <?= e(formato_ves((float)$det['total_deducciones'])) ?>
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono font-bold text-slate-900">
                                    <?= e(formato_ves((float)$det['neto_pagar'])) ?>
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <a href="<?= e(route_url('nomina', 'reciboPrint', ['detalle_id' => $det['id']])) ?>"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-500/10 hover:bg-emerald-600 hover:text-white text-emerald-700 transition">
                                        Imprimir Recibo
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recibos Legales de Bono de Alimentación (Cestaticket) -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 mb-4 border-b border-slate-200">
            <div>
                <h3 class="text-base font-bold text-slate-900">
                    2. Recibos Legales de Bono de Alimentación (Art. 4 Cestaticket)
                </h3>
                <p class="text-xs text-slate-500">
                    Incluye el texto legal del Art. 4 Parágrafo Primero y monto en letras.
                </p>
            </div>

            <?php if ($cestaticketSeleccionado): ?>
                <a href="<?= e(route_url('nomina', 'reciboPrint', ['cestaticket_lote_id' => $cestaticketSeleccionado['id']])) ?>"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white shadow-lg shadow-amber-950/30 transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir Todos los Cestaticket
                </a>
            <?php endif; ?>
        </div>

        <form method="POST" action="<?= e(route_url('nomina', 'recibos')) ?>" class="mb-4">
            <?= \Core\Csrf::field() ?>
            <?php if (!empty($nominaSeleccionada['id'])): ?>
                <input type="hidden" name="nomina_id" value="<?= (int)$nominaSeleccionada['id'] ?>">
            <?php endif; ?>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Seleccionar Lote de Cestaticket Procesado:</label>
            <select name="cestaticket_id" onchange="this.form.submit()"
                    class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                <?php foreach ($lotesCestaticket as $lc): ?>
                    <option value="<?= (int)$lc['id'] ?>" <?= isset($cestaticketSeleccionado['id']) && (int)$cestaticketSeleccionado['id'] === (int)$lc['id'] ? 'selected' : '' ?>>
                        <?= e((string)$lc['codigo_lote']) ?> — Período <?= e((string)$lc['periodo']) ?> (<?= e(formato_ves((float)$lc['total_pagar'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if (!$cestaticketSeleccionado): ?>
            <p class="text-xs text-slate-500 py-8 text-center">No hay lotes de Bono de Alimentación procesados en esta empresa.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold uppercase text-slate-500">
                            <th class="py-2.5 px-3">Trabajador</th>
                            <th class="py-2.5 px-2 text-center">Días Efectivos</th>
                            <th class="py-2.5 px-2 text-right">Valor Diario</th>
                            <th class="py-2.5 px-2 text-right">Total Bono</th>
                            <th class="py-2.5 px-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($cestaticketSeleccionado['detalles'] as $det): ?>
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900"><?= e((string)$det['nombre_completo']) ?></div>
                                    <div class="font-mono text-[10px] text-slate-500"><?= e((string)$det['cedula']) ?> • <?= e((string)$det['cargo']) ?></div>
                                </td>
                                <td class="py-2.5 px-2 text-center font-mono">
                                    <?= (float)$det['dias_efectivos'] ?> días
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono">
                                    <?= e(formato_ves((float)$det['valor_diario'])) ?>
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono font-bold text-amber-600">
                                    <?= e(formato_ves((float)$det['total_cestaticket'])) ?>
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <a href="<?= e(route_url('nomina', 'reciboPrint', ['cestaticket_detalle_id' => $det['id']])) ?>"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-500/10 hover:bg-amber-600 hover:text-white text-amber-700 transition">
                                        Imprimir Cestaticket
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
