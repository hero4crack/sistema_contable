<?php
declare(strict_types=1);

$periodoActual = (string)($empresaActiva['periodo_fiscal_activo'] ?? date('Y-m'));
$filasPrecalculo = $precalculo['filas'] ?? [];
?>
<!-- Cabecera del Módulo de Procesamiento de Nómina -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6 no-print">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-600 mb-1">
            <span>Módulo Laboral LOTTT</span>
            <span>•</span>
            <span class="font-mono"><?= e((string)$empresaActiva['rif']) ?></span>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
            Procesamiento de Nómina General (Sueldos y Salarios)
        </h2>
        <p class="text-sm text-slate-600 mt-0.5">
            Generación de lotes quincenales y mensuales, cálculo en tiempo real de IVSS (4%), RPE (0.5%), FAOV (1%), aportes patronales e integración contable.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <a href="<?= e(route_url('nomina', 'trabajadores')) ?>"
           class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition">
            Directorio de Personal
        </a>
        <a href="<?= e(route_url('nomina', 'cestaticket')) ?>"
           class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 border border-amber-500/30 transition">
            Bono de Alimentación
        </a>
        <a href="<?= e(route_url('nomina', 'recibos')) ?>"
           class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30 transition">
            Emitir Recibos de Pago
        </a>
    </div>
</div>

<!-- SECCIÓN 1: GENERADOR INTERACTIVO DE NUEVO LOTE DE NÓMINA -->
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-8 overflow-hidden no-print"
     id="panelGeneradorNomina"
     data-config='<?= e((string)json_encode($configLaboral)) ?>'>
    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                Hoja de Trabajo: Nuevo Lote de Nómina
            </h3>
            <p class="text-xs text-slate-500">
                Ajuste días hábiles, descanso, feriados, horas extras o bonificaciones antes de aprobar el lote.
            </p>
        </div>

        <div class="flex items-center gap-2 text-xs font-mono">
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600">
                IVSS Trab: <?= e((string)$configLaboral['porcentaje_sso_empleado']) ?>%
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600">
                RPE: <?= e((string)$configLaboral['porcentaje_rpe_empleado']) ?>%
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600">
                FAOV: <?= e((string)$configLaboral['porcentaje_faov_empleado']) ?>%
            </span>
        </div>
    </div>

    <!-- Parámetros del Período -->
    <div class="p-6 border-b border-slate-200 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Período Fiscal</label>
            <input type="month" id="nom_periodo" value="<?= e($periodoActual) ?>"
                   class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tipo de Nómina</label>
            <select id="nom_frecuencia" onchange="window.JurisNomina?.recalcularPorFrecuencia()"
                    class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                <option value="PRIMERA_QUINCENA">1ra Quincena (15 días)</option>
                <option value="SEGUNDA_QUINCENA">2da Quincena (15 días)</option>
                <option value="SEMANAL">Semanal (7 días)</option>
                <option value="MENSUAL">Mensual Completa (30 días)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha Inicio</label>
            <input type="date" id="nom_fecha_desde" value="<?= e($periodoActual . '-01') ?>"
                   class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha Fin</label>
            <input type="date" id="nom_fecha_hasta" value="<?= e($periodoActual . '-15') ?>"
                   class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Lunes del Mes (IVSS)</label>
            <select id="nom_lunes_mes" onchange="window.JurisNomina?.recalcularTodasLasFilasNomina()"
                    class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                <option value="4">4 Lunes</option>
                <option value="5">5 Lunes</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Descripción del Lote</label>
            <input type="text" id="nom_descripcion" value="Nómina 1ra Quincena <?= e($periodoActual) ?>"
                   class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
        </div>
    </div>

    <!-- Tabla Editable de Pre-Nómina -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="tablaPreNomina">
            <thead>
                <tr class="bg-slate-100/80 border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    <th class="py-3 px-3">Trabajador / Cédula</th>
                    <th class="py-3 px-2 text-right">Sueldo Mensual</th>
                    <th class="py-3 px-2 text-right">Diario</th>
                    <th class="py-3 px-2 text-center bg-emerald-500/5">Días Háb.</th>
                    <th class="py-3 px-2 text-center bg-emerald-500/5">Descanso</th>
                    <th class="py-3 px-2 text-center bg-emerald-500/5">Feriados</th>
                    <th class="py-3 px-2 text-right bg-emerald-500/10">Sueldo Período</th>
                    <th class="py-3 px-2 text-right bg-emerald-500/5">H. Extras (Bs.)</th>
                    <th class="py-3 px-2 text-right bg-emerald-500/5">Bono Prod. (Bs.)</th>
                    <th class="py-3 px-2 text-right bg-rose-500/5">Ret. IVSS</th>
                    <th class="py-3 px-2 text-right bg-rose-500/5">Ret. RPE</th>
                    <th class="py-3 px-2 text-right bg-rose-500/5">Ret. FAOV</th>
                    <th class="py-3 px-2 text-right bg-rose-500/5">ISLR / Otras</th>
                    <th class="py-3 px-3 text-right bg-slate-200/60">Neto a Pagar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-xs">
                <?php if (empty($filasPrecalculo)): ?>
                    <tr>
                        <td colspan="14" class="py-8 text-center text-slate-500">
                            No hay trabajadores activos registrados en esta empresa. Regístrelos primero en el Directorio de Personal.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filasPrecalculo as $idx => $f): ?>
                        <tr class="fila-prenomina hover:bg-slate-50/70 transition"
                            data-trabajador-id="<?= (int)$f['trabajador_id'] ?>"
                            data-salario-mensual="<?= e((string)$f['salario_mensual']) ?>"
                            data-sueldo-diario="<?= e((string)$f['sueldo_diario']) ?>"
                            data-aplica-sso="<?= (int)$f['aplica_sso'] ?>"
                            data-aplica-rpe="<?= (int)$f['aplica_rpe'] ?>"
                            data-aplica-faov="<?= (int)$f['aplica_faov'] ?>"
                            data-porc-islr="<?= e((string)$f['porcentaje_islr']) ?>">
                            <td class="py-2.5 px-3">
                                <div class="font-semibold text-slate-900"><?= e((string)$f['nombre_completo']) ?></div>
                                <div class="text-[11px] font-mono text-slate-500">
                                    <?= e((string)$f['cedula']) ?> • <?= e((string)$f['cargo']) ?>
                                </div>
                            </td>
                            <td class="py-2.5 px-2 text-right font-mono text-slate-700">
                                <?= e(number_format((float)$f['salario_mensual'], 2, ',', '.')) ?>
                            </td>
                            <td class="py-2.5 px-2 text-right font-mono text-slate-500">
                                <?= e(number_format((float)$f['sueldo_diario'], 2, ',', '.')) ?>
                            </td>
                            <td class="py-2.5 px-1.5 text-center bg-emerald-500/5">
                                <input type="number" step="0.5" min="0" max="31"
                                       class="inp-dias-habiles w-14 px-1.5 py-1 rounded-lg text-center font-mono text-xs bg-white border border-slate-300 text-slate-900"
                                       value="<?= e((string)$f['dias_habiles']) ?>"
                                       oninput="window.JurisNomina?.recalcularFilaNomina(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-1.5 text-center bg-emerald-500/5">
                                <input type="number" step="0.5" min="0" max="15"
                                       class="inp-dias-descanso w-14 px-1.5 py-1 rounded-lg text-center font-mono text-xs bg-white border border-slate-300 text-slate-900"
                                       value="<?= e((string)$f['dias_descanso']) ?>"
                                       oninput="window.JurisNomina?.recalcularFilaNomina(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-1.5 text-center bg-emerald-500/5">
                                <input type="number" step="0.5" min="0" max="10"
                                       class="inp-dias-feriados w-14 px-1.5 py-1 rounded-lg text-center font-mono text-xs bg-white border border-slate-300 text-slate-900"
                                       value="<?= e((string)$f['dias_feriados']) ?>"
                                       oninput="window.JurisNomina?.recalcularFilaNomina(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-2 text-right font-mono font-semibold text-emerald-700 bg-emerald-500/10 lbl-sueldo-basico">
                                <?= e(number_format((float)$f['sueldo_basico_periodo'], 2, ',', '.')) ?>
                            </td>
                            <td class="py-2.5 px-1.5 text-right bg-emerald-500/5">
                                <input type="number" step="0.01" min="0"
                                       class="inp-horas-extras w-20 px-2 py-1 rounded-lg text-right font-mono text-xs bg-white border border-slate-300 text-slate-900"
                                       value="<?= e((string)$f['horas_extras']) ?>"
                                       oninput="window.JurisNomina?.recalcularFilaNomina(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-1.5 text-right bg-emerald-500/5">
                                <input type="number" step="0.01" min="0"
                                       class="inp-bonificaciones w-24 px-2 py-1 rounded-lg text-right font-mono text-xs bg-white border border-slate-300 text-slate-900"
                                       value="<?= e((string)$f['bonificaciones']) ?>"
                                       oninput="window.JurisNomina?.recalcularFilaNomina(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-1.5 text-right bg-rose-500/5">
                                <input type="number" step="0.01" min="0"
                                       class="inp-ret-sso w-20 px-2 py-1 rounded-lg text-right font-mono text-xs bg-white border border-slate-300 text-rose-600"
                                       value="<?= e((string)$f['retencion_sso']) ?>"
                                       oninput="window.JurisNomina?.recalcularNetoManualFila(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-1.5 text-right bg-rose-500/5">
                                <input type="number" step="0.01" min="0"
                                       class="inp-ret-rpe w-16 px-2 py-1 rounded-lg text-right font-mono text-xs bg-white border border-slate-300 text-rose-600"
                                       value="<?= e((string)$f['retencion_rpe']) ?>"
                                       oninput="window.JurisNomina?.recalcularNetoManualFila(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-1.5 text-right bg-rose-500/5">
                                <input type="number" step="0.01" min="0"
                                       class="inp-ret-faov w-18 px-2 py-1 rounded-lg text-right font-mono text-xs bg-white border border-slate-300 text-rose-600"
                                       value="<?= e((string)$f['retencion_faov']) ?>"
                                       oninput="window.JurisNomina?.recalcularNetoManualFila(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-1.5 text-right bg-rose-500/5">
                                <input type="number" step="0.01" min="0"
                                       class="inp-otras-ded w-20 px-2 py-1 rounded-lg text-right font-mono text-xs bg-white border border-slate-300 text-rose-600"
                                       value="<?= e((string)($f['retencion_islr'] + $f['otras_deducciones'])) ?>"
                                       oninput="window.JurisNomina?.recalcularNetoManualFila(this.closest('tr'))">
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-sm text-slate-900 bg-slate-100/80 lbl-neto-fila">
                                <?= e(formato_ves((float)$f['neto_pagar'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pie de Aprobación e Integración Contable -->
    <div class="p-6 bg-slate-50 border-t border-slate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                <input type="checkbox" id="nom_generar_asiento" checked class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                <div>
                    <span class="text-xs font-bold text-slate-900 block">
                        Generar asiento contable de nómina automáticamente en el Libro Diario
                    </span>
                    <span class="text-[11px] text-slate-500">
                        Carga a Sueldos/Salarios y Aportes Patronales (`6.1.01.*`) con abono a Retenciones IVSS/FAOV/RPE (`2.3.02.*`) y Sueldos por Pagar (`2.4.01.004`).
                    </span>
                </div>
            </label>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <div class="text-right font-mono">
                <span class="text-[10px] uppercase tracking-wider text-slate-500 block">Total Asignaciones</span>
                <span id="lblTotalAsignacionesLote" class="text-sm font-bold text-emerald-600">Bs. 0,00</span>
            </div>
            <div class="text-right font-mono">
                <span class="text-[10px] uppercase tracking-wider text-slate-500 block">Total Deducciones</span>
                <span id="lblTotalDeduccionesLote" class="text-sm font-bold text-rose-600">Bs. 0,00</span>
            </div>
            <div class="text-right font-mono px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30">
                <span class="text-[10px] uppercase tracking-wider text-emerald-700 block">Neto Total Lote</span>
                <span id="lblTotalNetoLote" class="text-base font-bold text-emerald-700">Bs. 0,00</span>
            </div>

            <button type="button"
                    onclick="window.JurisNomina?.procesarYGuardarNomina()"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Procesar y Aprobar Nómina
            </button>
        </div>
    </div>
</div>

<!-- SECCIÓN 2: HISTORIAL DE LOTES PROCESADOS Y SÁBANA DE NÓMINA GENERAL -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Lista lateral de lotes procesados -->
    <div class="lg:col-span-1 bg-white border border-slate-200 rounded-2xl p-4 shadow-sm no-print">
        <h3 class="text-sm font-bold text-slate-900 mb-3">Lotes de Nómina Procesados</h3>
        <?php if (empty($lotesNomina)): ?>
            <p class="text-xs text-slate-500 py-6 text-center">Aún no se han procesado lotes de nómina.</p>
        <?php else: ?>
            <div class="space-y-2.5">
                <?php foreach ($lotesNomina as $lote): ?>
                    <?php $esActivo = isset($loteSeleccionado['id']) && (int)$loteSeleccionado['id'] === (int)$lote['id']; ?>
                    <a href="<?= e(route_url('nomina', 'index', ['lote_id' => $lote['id']])) ?>"
                       class="block p-3 rounded-xl border transition <?= $esActivo
                           ? 'bg-emerald-500/10 border-emerald-500/40'
                           : 'bg-slate-50 border-slate-200 hover:border-slate-400' ?>">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-xs text-emerald-600"><?= e((string)$lote['codigo_nomina']) ?></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $lote['estado'] === 'ANULADA'
                                ? 'bg-rose-500/15 text-rose-500'
                                : 'bg-emerald-500/15 text-emerald-600' ?>">
                                <?= e((string)$lote['estado']) ?>
                            </span>
                        </div>
                        <div class="text-xs font-semibold text-slate-800 mt-1">
                            <?= e((string)($lote['descripcion'] ?: $lote['tipo_frecuencia'])) ?>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-[11px] font-mono text-slate-500">
                            <span><?= (int)$lote['cantidad_trabajadores'] ?> trab.</span>
                            <span class="font-bold text-slate-900"><?= e(formato_ves((float)$lote['total_neto_pagar'])) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sábana Horizontal de Nómina del Lote Seleccionado -->
    <div class="lg:col-span-3 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm overflow-hidden">
        <?php if (!$loteSeleccionado): ?>
            <div class="py-12 text-center text-slate-500 text-sm">
                Seleccione un lote de nómina procesado para visualizar su sábana de sueldos y salarios.
            </div>
        <?php else: ?>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 mb-4 border-b border-slate-200">
                <div>
                    <div class="text-xs font-mono font-bold text-emerald-600">
                        <?= e((string)$empresaActiva['razon_social']) ?> • RIF: <?= e((string)$empresaActiva['rif']) ?>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">
                        NÓMINA DE SUELDOS Y SALARIOS — <?= e((string)$loteSeleccionado['codigo_nomina']) ?>
                    </h3>
                    <p class="text-xs text-slate-500">
                        Período: <strong><?= e(date('d/m/Y', strtotime((string)$loteSeleccionado['fecha_desde']))) ?></strong>
                        al <strong><?= e(date('d/m/Y', strtotime((string)$loteSeleccionado['fecha_hasta']))) ?></strong>
                        • Lunes del mes: <?= (int)$loteSeleccionado['lunes_del_mes'] ?>
                        <?php if (!empty($loteSeleccionado['numero_asiento'])): ?>
                            • Asiento Contable:
                            <a href="<?= e(route_url('contabilidad', 'diario')) ?>" class="font-mono font-bold text-emerald-600 underline">
                                <?= e((string)$loteSeleccionado['numero_asiento']) ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 no-print">
                    <a href="<?= e(route_url('nomina', 'exportarNominaCsv', ['nomina_id' => $loteSeleccionado['id']])) ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 border border-emerald-500/30 transition">
                        Exportar Excel (CSV)
                    </a>
                    <select id="filtroSabanaIndividual"
                            onchange="document.querySelectorAll('.fila-sabana').forEach(r => r.classList.toggle('hidden', this.value !== '' && r.dataset.detId !== this.value))"
                            class="px-3 py-2 rounded-xl text-xs bg-white border border-slate-300 text-slate-800">
                        <option value="">Sábana General (Todos)</option>
                        <?php foreach ($loteSeleccionado['detalles'] as $detOpt): ?>
                            <option value="<?= (int)$detOpt['id'] ?>">Individual: <?= e((string)$detOpt['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button"
                            onclick="window.open('<?= e(route_url('nomina', 'sabanaPrint', ['nomina_id' => $loteSeleccionado['id']])) ?>&detalle_id=' + encodeURIComponent(document.getElementById('filtroSabanaIndividual')?.value || ''), '_blank')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Imprimir Sábana
                    </button>
                    <a href="<?= e(route_url('nomina', 'reciboPrint', ['nomina_id' => $loteSeleccionado['id']])) ?>"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow transition">
                        Imprimir Todos los Recibos
                    </a>
                    <?php if ($loteSeleccionado['estado'] !== 'ANULADA'): ?>
                        <button type="button"
                                onclick="window.JurisNomina?.anularLoteNomina(<?= (int)$loteSeleccionado['id'] ?>, '<?= e((string)$loteSeleccionado['codigo_nomina']) ?>')"
                                class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-semibold bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 border border-rose-500/30 transition">
                            Anular
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $tituloReporte    = 'SÁBANA DE NÓMINA — ' . (string)$loteSeleccionado['codigo_nomina'];
            $subtituloPeriodo = 'Del ' . date('d/m/Y', strtotime((string)$loteSeleccionado['fecha_desde'])) . ' al ' . date('d/m/Y', strtotime((string)$loteSeleccionado['fecha_hasta']));
            $claseContenedor  = 'hidden print:block mb-4';
            require ROOT_PATH . '/views/layouts/membrete_reporte.php';
            ?>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-300 text-[10px] font-bold uppercase text-slate-600">
                            <th class="py-2.5 px-2">N°</th>
                            <th class="py-2.5 px-2">Cédula / Trabajador</th>
                            <th class="py-2.5 px-2 text-right">Sueldo Mensual</th>
                            <th class="py-2.5 px-2 text-center">Días (H/D/F)</th>
                            <th class="py-2.5 px-2 text-right">Sueldo Básico</th>
                            <th class="py-2.5 px-2 text-right">H.Ext / Bonos</th>
                            <th class="py-2.5 px-2 text-right">Ret. IVSS</th>
                            <th class="py-2.5 px-2 text-right">Ret. RPE</th>
                            <th class="py-2.5 px-2 text-right">Ret. FAOV</th>
                            <th class="py-2.5 px-2 text-right">Total Desc.</th>
                            <th class="py-2.5 px-2 text-right">Neto a Cancelar</th>
                            <th class="py-2.5 px-3 text-center">Firma Recibido</th>
                            <th class="py-2.5 px-2 text-right no-print">Recibo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($loteSeleccionado['detalles'] as $idx => $det): ?>
                            <tr class="fila-sabana" data-det-id="<?= (int)$det['id'] ?>">
                                <td class="py-2.5 px-2 font-mono"><?= $idx + 1 ?></td>
                                <td class="py-2.5 px-2">
                                    <div class="font-bold text-slate-900"><?= e((string)$det['nombre_completo']) ?></div>
                                    <div class="font-mono text-[10px] text-slate-500"><?= e((string)$det['cedula']) ?> • <?= e((string)$det['cargo']) ?></div>
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono"><?= e(number_format((float)$det['salario_mensual'], 2, ',', '.')) ?></td>
                                <td class="py-2.5 px-2 text-center font-mono text-[11px]">
                                    <?= (float)$det['dias_habiles'] ?> / <?= (float)$det['dias_descanso'] ?> / <?= (float)$det['dias_feriados'] ?>
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono"><?= e(number_format((float)$det['sueldo_basico_periodo'], 2, ',', '.')) ?></td>
                                <td class="py-2.5 px-2 text-right font-mono"><?= e(number_format((float)$det['horas_extras'] + (float)$det['bonificaciones'], 2, ',', '.')) ?></td>
                                <td class="py-2.5 px-2 text-right font-mono text-rose-600"><?= e(number_format((float)$det['retencion_sso'], 2, ',', '.')) ?></td>
                                <td class="py-2.5 px-2 text-right font-mono text-rose-600"><?= e(number_format((float)$det['retencion_rpe'], 2, ',', '.')) ?></td>
                                <td class="py-2.5 px-2 text-right font-mono text-rose-600"><?= e(number_format((float)$det['retencion_faov'], 2, ',', '.')) ?></td>
                                <td class="py-2.5 px-2 text-right font-mono font-semibold text-rose-600"><?= e(number_format((float)$det['total_deducciones'], 2, ',', '.')) ?></td>
                                <td class="py-2.5 px-2 text-right font-mono font-bold text-emerald-600"><?= e(formato_ves((float)$det['neto_pagar'])) ?></td>
                                <td class="py-2.5 px-3 text-center">
                                    <div class="border-b border-slate-400 w-24 mx-auto h-5"></div>
                                </td>
                                <td class="py-2.5 px-2 text-right no-print">
                                    <a href="<?= e(route_url('nomina', 'reciboPrint', ['detalle_id' => $det['id']])) ?>"
                                       target="_blank"
                                       class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 transition">
                                        Recibo PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-mono font-bold text-xs border-t-2 border-slate-300">
                            <td colspan="4" class="py-3 px-2 text-right uppercase">Totales del Lote:</td>
                            <td class="py-3 px-2 text-right" colspan="2"><?= e(formato_ves((float)$loteSeleccionado['total_asignaciones'])) ?></td>
                            <td class="py-3 px-2 text-right text-rose-600" colspan="4"><?= e(formato_ves((float)$loteSeleccionado['total_deducciones'])) ?></td>
                            <td class="py-3 px-2 text-right text-emerald-600"><?= e(formato_ves((float)$loteSeleccionado['total_neto_pagar'])) ?></td>
                            <td colspan="2"></td>
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
