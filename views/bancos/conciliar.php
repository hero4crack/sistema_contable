<?php
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

$emp         = $empresaActiva ?? [];
$usuarioRol  = (string)Session::get('usuario_rol', 'contador');
$puedeEditar = in_array($usuarioRol, ['admin', 'contador'], true);

$simboloMoneda = ($cuentaSeleccionada['moneda'] ?? 'VES') === 'USD' ? '$ ' : 'Bs. ';
$saldoBancoVal  = $conciliacionActual ? (float)$conciliacionActual['saldo_segun_banco'] : (float)$saldoBanco;
$saldoLibrosVal = $conciliacionActual ? (float)$conciliacionActual['saldo_segun_libros'] : (float)$saldoLibros;
$depTransitoVal = $conciliacionActual ? (float)$conciliacionActual['depositos_en_transito'] : 0.00;
$cheqTransitoVal= $conciliacionActual ? (float)$conciliacionActual['cheques_en_transito'] : 0.00;
$ndNoRegVal     = $conciliacionActual ? (float)$conciliacionActual['notas_debito_no_registradas'] : 0.00;
$ncNoRegVal     = $conciliacionActual ? (float)$conciliacionActual['notas_credito_no_registradas'] : 0.00;
$observaciones  = (string)($conciliacionActual['observaciones'] ?? '');
$estatusConc    = (string)($conciliacionActual['estatus'] ?? 'Borrador');
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO Y SELECTOR DE CUENTA / PERÍODO -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-700 uppercase tracking-wider font-semibold">
                <span>Módulo 2 • Mesa de Conciliación Bancaria</span>
                <span>•</span>
                <span><?= e($emp['rif'] ?? '') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-0.5">
                Conciliación Bancaria Mensual
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Cruce interactivo entre el Estado de Cuenta Bancario y el Libro Mayor de <strong class="text-slate-800"><?= e($emp['razon_social'] ?? '') ?></strong>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= e(route_url('bancos', 'index', ['cuenta_id' => $cuentaSeleccionada['id'] ?? 0])) ?>"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 transition-colors">
                ← Volver a Cuentas y Auxiliar
            </a>
            <?php if ($cuentaSeleccionada): ?>
                <a href="<?= e(route_url('bancos', 'conciliacionPrint', ['cuenta_id' => $cuentaSeleccionada['id'], 'periodo' => $periodo])) ?>"
                   target="_blank"
                   class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white shadow-sm transition-colors">
                    Imprimir Reporte Oficial
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- BARRA DE SELECCIÓN DE CUENTA Y PERÍODO -->
    <form method="POST" action="<?= e(route_url('bancos', 'conciliar')) ?>" class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex flex-wrap items-end justify-between gap-4">
        <?= Csrf::field() ?>
        <div class="flex flex-wrap items-end gap-3 flex-1">
            <div class="min-w-[260px] flex-1">
                <label class="block text-xs font-medium text-slate-700 mb-1">Cuenta Bancaria</label>
                <select name="cuenta_id" class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900 focus:border-emerald-600 focus:outline-none">
                    <?php foreach ($cuentas as $cta): ?>
                        <option value="<?= (int)$cta['id'] ?>" <?= $cuentaSeleccionada && (int)$cuentaSeleccionada['id'] === (int)$cta['id'] ? 'selected' : '' ?>>
                            <?= e($cta['banco_nombre'] . ' •••• ' . substr((string)$cta['numero_cuenta'], -4) . ' (' . $cta['moneda'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Período a Conciliar</label>
                <input type="month" name="periodo" value="<?= e($periodo) ?>" required
                       class="rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs font-mono text-slate-900 focus:border-emerald-600 focus:outline-none">
            </div>

            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors">
                Cargar Período
            </button>
        </div>

        <?php if ($cuentaSeleccionada && $puedeEditar): ?>
            <button type="button" onclick="ejecutarAutoConciliacion()"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 transition-colors">
                ⚡ Auto-Conciliar por Referencia y Monto
            </button>
        <?php endif; ?>
    </form>

    <?php if (!$cuentaSeleccionada): ?>
        <div class="rounded-2xl bg-white border border-slate-200 p-10 text-center text-sm text-slate-500">
            Debe registrar al menos una cuenta bancaria para iniciar la conciliación.
        </div>
    <?php else: ?>

        <!-- MESA DE TRABAJO EN DOS COLUMNAS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- COLUMNA IZQUIERDA: ESTADO DE CUENTA BANCARIO -->
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">1. Estado de Cuenta Bancario (<?= e($periodo) ?>)</h2>
                        <p class="text-[11px] text-slate-500">Marque las partidas que ya coinciden con su contabilidad.</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <?= count($movimientosBanco) ?> movs.
                    </span>
                </div>

                <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase text-slate-600 sticky top-0">
                            <tr>
                                <th class="py-2.5 px-3 text-center">Conc.</th>
                                <th class="py-2.5 px-3">Fecha / Ref.</th>
                                <th class="py-2.5 px-3">Descripción</th>
                                <th class="py-2.5 px-3 text-right">Entrada</th>
                                <th class="py-2.5 px-3 text-right">Salida</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (!empty($movimientosBanco)): ?>
                                <?php foreach ($movimientosBanco as $mb): ?>
                                    <?php
                                    $chk = (int)$mb['conciliado'] === 1;
                                    $debeB  = (float)$mb['monto_debe'];
                                    $haberB = (float)$mb['monto_haber'];
                                    ?>
                                    <tr class="<?= $chk ? 'bg-emerald-50/60' : 'hover:bg-slate-50' ?> transition-colors">
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="checkbox"
                                                   class="chk-banco rounded border-slate-300 text-emerald-700 focus:ring-emerald-600"
                                                   data-id="<?= (int)$mb['id'] ?>"
                                                   data-debe="<?= $debeB ?>"
                                                   data-haber="<?= $haberB ?>"
                                                   <?= $chk ? 'checked' : '' ?>
                                                   <?= !$puedeEditar ? 'disabled' : '' ?>>
                                        </td>
                                        <td class="py-2.5 px-3 whitespace-nowrap">
                                            <span class="font-mono text-slate-700 block"><?= e(date('d/m/Y', strtotime((string)$mb['fecha_operacion']))) ?></span>
                                            <span class="font-mono text-[10px] text-slate-500">Ref: <?= e($mb['referencia']) ?></span>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-800">
                                            <span class="block line-clamp-2"><?= e($mb['descripcion']) ?></span>
                                            <?php if (empty($mb['asiento_id'])): ?>
                                                <span class="text-[10px] text-amber-700 font-semibold">No contabilizado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono text-emerald-700 whitespace-nowrap">
                                            <?= $debeB > 0 ? number_format($debeB, 2, ',', '.') : '—' ?>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono text-rose-700 whitespace-nowrap">
                                            <?= $haberB > 0 ? number_format($haberB, 2, ',', '.') : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-500">Sin movimientos bancarios en <?= e($periodo) ?>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- COLUMNA DERECHA: LIBRO MAYOR CONTABLE -->
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">2. Libro Mayor Contable (<?= e($periodo) ?>)</h2>
                        <p class="text-[11px] text-slate-500">
                            <?php if (!empty($cuentaSeleccionada['cuenta_contable_codigo'])): ?>
                                Cuenta: <strong class="font-mono"><?= e($cuentaSeleccionada['cuenta_contable_codigo']) ?></strong> — <?= e($cuentaSeleccionada['cuenta_contable_nombre']) ?>. Marque partidas <strong>en tránsito</strong>.
                            <?php else: ?>
                                <span class="text-amber-700">Esta cuenta bancaria no tiene cuenta contable enlazada aún.</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        <?= count($movimientosLibro) ?> regs.
                    </span>
                </div>

                <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase text-slate-600 sticky top-0">
                            <tr>
                                <th class="py-2.5 px-3 text-center" title="Marcar si está en tránsito (no cobrado/acreditado por el banco aún)">Tráns.</th>
                                <th class="py-2.5 px-3">Fecha / Asiento</th>
                                <th class="py-2.5 px-3">Concepto</th>
                                <th class="py-2.5 px-3 text-right">Debe</th>
                                <th class="py-2.5 px-3 text-right">Haber</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (!empty($movimientosLibro)): ?>
                                <?php foreach ($movimientosLibro as $ml): ?>
                                    <?php
                                    $debeL  = (float)$ml['debe'];
                                    $haberL = (float)$ml['haber'];
                                    ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="checkbox"
                                                   class="chk-transito rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                                   data-debe="<?= $debeL ?>"
                                                   data-haber="<?= $haberL ?>">
                                        </td>
                                        <td class="py-2.5 px-3 whitespace-nowrap">
                                            <span class="font-mono text-slate-700 block"><?= e(date('d/m/Y', strtotime((string)$ml['fecha']))) ?></span>
                                            <span class="font-mono text-[10px] text-emerald-700"><?= e($ml['numero_asiento']) ?></span>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-800">
                                            <span class="block line-clamp-2"><?= e($ml['descripcion_linea'] ?: $ml['concepto']) ?></span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono text-emerald-700 whitespace-nowrap">
                                            <?= $debeL > 0 ? number_format($debeL, 2, ',', '.') : '—' ?>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono text-rose-700 whitespace-nowrap">
                                            <?= $haberL > 0 ? number_format($haberL, 2, ',', '.') : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-500">Sin movimientos contables en <?= e($periodo) ?>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- CUADRO RESUMEN DE CONCILIACIÓN EN TIEMPO REAL -->
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Cuadro Resumen de Conciliación Bancaria (<?= e($periodo) ?>)</h2>
                    <p class="text-xs text-slate-500">Ajuste los saldos en tránsito o notas bancarias no registradas hasta obtener Diferencia = 0,00.</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $estatusConc === 'Conciliada' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-50 text-amber-800 border border-amber-200' ?>">
                    Estatus: <?= e($estatusConc) ?>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Columna Saldo Banco -->
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">A. Conciliación del Saldo Bancario</h3>
                    <div class="grid grid-cols-2 gap-3 items-center text-xs">
                        <label class="text-slate-600 font-medium">Saldo según Banco al cierre:</label>
                        <input type="number" step="0.01" id="conc_saldo_banco" value="<?= number_format($saldoBancoVal, 2, '.', '') ?>"
                               class="input-conc rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-right font-mono font-semibold text-slate-900">

                        <label class="text-slate-600 font-medium">(+) Depósitos en Tránsito:</label>
                        <input type="number" step="0.01" min="0" id="conc_dep_transito" value="<?= number_format($depTransitoVal, 2, '.', '') ?>"
                               class="input-conc rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-right font-mono text-emerald-700">

                        <label class="text-slate-600 font-medium">(-) Cheques / Pagos en Tránsito:</label>
                        <input type="number" step="0.01" min="0" id="conc_cheques_transito" value="<?= number_format($cheqTransitoVal, 2, '.', '') ?>"
                               class="input-conc rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-right font-mono text-rose-700">

                        <span class="font-bold text-slate-900 pt-2 border-t border-slate-300">Saldo Bancario Conciliado:</span>
                        <span id="lblBancoConciliado" class="font-mono font-bold text-sm text-right text-slate-900 pt-2 border-t border-slate-300">0,00</span>
                    </div>
                </div>

                <!-- Columna Saldo Libros -->
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">B. Conciliación del Saldo en Libros</h3>
                    <div class="grid grid-cols-2 gap-3 items-center text-xs">
                        <label class="text-slate-600 font-medium">Saldo según Libros (Mayor):</label>
                        <input type="number" step="0.01" id="conc_saldo_libros" value="<?= number_format($saldoLibrosVal, 2, '.', '') ?>"
                               class="input-conc rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-right font-mono font-semibold text-slate-900">

                        <label class="text-slate-600 font-medium">(+) Notas de Crédito no registradas:</label>
                        <input type="number" step="0.01" min="0" id="conc_nc_no_reg" value="<?= number_format($ncNoRegVal, 2, '.', '') ?>"
                               class="input-conc rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-right font-mono text-emerald-700">

                        <label class="text-slate-600 font-medium">(-) Notas de Débito no registradas:</label>
                        <input type="number" step="0.01" min="0" id="conc_nd_no_reg" value="<?= number_format($ndNoRegVal, 2, '.', '') ?>"
                               class="input-conc rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-right font-mono text-rose-700">

                        <span class="font-bold text-slate-900 pt-2 border-t border-slate-300">Saldo en Libros Conciliado:</span>
                        <span id="lblLibrosConciliado" class="font-mono font-bold text-sm text-right text-slate-900 pt-2 border-t border-slate-300">0,00</span>
                    </div>
                </div>
            </div>

            <!-- Indicador de Diferencia y Observaciones -->
            <div id="cajaDiferenciaConc" class="rounded-xl p-4 border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider block">Diferencia de Conciliación</span>
                    <span id="lblEstadoDiferencia" class="text-xs"></span>
                </div>
                <span id="lblDiferenciaTotal" class="text-2xl font-mono font-bold">0,00</span>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Observaciones / Notas de Cierre</label>
                <textarea id="conc_observaciones" rows="2" placeholder="Notas sobre partidas en tránsito o ajustes del período..."
                          class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900 focus:border-emerald-600 focus:outline-none"><?= e($observaciones) ?></textarea>
            </div>

            <?php if ($puedeEditar): ?>
                <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="recalcularDesdeChecks()"
                            class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300">
                        Recalcular Pendientes desde Checkboxes
                    </button>
                    <button type="button" onclick="guardarConciliacion('Borrador')"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300">
                        Guardar Borrador
                    </button>
                    <button type="button" onclick="guardarConciliacion('Conciliada')"
                            class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white shadow-sm">
                        Cerrar y Aprobar Conciliación
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- HISTORIAL DE CONCILIACIONES GUARDADAS (BORRADORES Y APROBADAS) -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Conciliaciones Guardadas (Borradores y Aprobadas)</h2>
                <p class="text-xs text-slate-500">Cada vez que guardas un borrador o apruebas una conciliación queda registrada aquí.</p>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <?= count($historialConciliaciones ?? []) ?> registros
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-4">Período</th>
                        <th class="py-3 px-4">Cuenta Bancaria</th>
                        <th class="py-3 px-4 text-right">Saldo Banco</th>
                        <th class="py-3 px-4 text-right">Saldo Libros</th>
                        <th class="py-3 px-4 text-right">Diferencia</th>
                        <th class="py-3 px-4 text-center">Estatus</th>
                        <th class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (!empty($historialConciliaciones)): ?>
                        <?php foreach ($historialConciliaciones as $hc): ?>
                            <?php $simHc = ($hc['moneda'] ?? 'VES') === 'USD' ? '$ ' : 'Bs. '; ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900"><?= e($hc['periodo']) ?></td>
                                <td class="py-3 px-4">
                                    <?= e($hc['banco_nombre']) ?>
                                    <span class="font-mono text-slate-500">(•••• <?= e(substr((string)$hc['numero_cuenta'], -4)) ?>)</span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono"><?= e($simHc . number_format((float)$hc['saldo_segun_banco'], 2, ',', '.')) ?></td>
                                <td class="py-3 px-4 text-right font-mono"><?= e($simHc . number_format((float)$hc['saldo_segun_libros'], 2, ',', '.')) ?></td>
                                <td class="py-3 px-4 text-right font-mono font-semibold <?= abs((float)$hc['diferencia']) < 0.01 ? 'text-emerald-700' : 'text-amber-700' ?>">
                                    <?= e($simHc . number_format((float)$hc['diferencia'], 2, ',', '.')) ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?= $hc['estatus'] === 'Conciliada' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-50 text-amber-800 border border-amber-200' ?>">
                                        <?= e($hc['estatus']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= e(route_url('bancos', 'conciliar', ['cuenta_id' => $hc['cuenta_bancaria_id'], 'periodo' => $hc['periodo']])) ?>"
                                           class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300">
                                            Cargar
                                        </a>
                                        <a href="<?= e(route_url('bancos', 'conciliacionPrint', ['cuenta_id' => $hc['cuenta_bancaria_id'], 'periodo' => $hc['periodo']])) ?>"
                                           target="_blank"
                                           class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Imprimir
                                        </a>
                                        <button type="button"
                                                onclick="eliminarBorradorConciliacion(<?= (int)$hc['id'] ?>, <?= e(json_encode($hc['periodo'] . ' — ' . $hc['banco_nombre'])) ?>)"
                                                class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 cursor-pointer">
                                            Borrar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">
                                Aún no hay conciliaciones guardadas para esta empresa.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php if ($cuentaSeleccionada): ?>
<script>
(() => {
    const getMeta   = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || '';
    const BASE_URL  = getMeta('base-url');
    const CSRF      = getMeta('csrf-token');
    const CUENTA_ID = <?= (int)$cuentaSeleccionada['id'] ?>;
    const PERIODO   = <?= json_encode($periodo) ?>;
    const SIMBOLO   = <?= json_encode($simboloMoneda) ?>;

    function fmt(num) {
        return SIMBOLO + Number(num || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || 'Error en la operación.');
        return data;
    }

    function actualizarResumen() {
        const saldoBanco  = parseFloat(document.getElementById('conc_saldo_banco').value || '0') || 0;
        const depTransito = parseFloat(document.getElementById('conc_dep_transito').value || '0') || 0;
        const cheqTrans   = parseFloat(document.getElementById('conc_cheques_transito').value || '0') || 0;

        const saldoLibros = parseFloat(document.getElementById('conc_saldo_libros').value || '0') || 0;
        const ncNoReg     = parseFloat(document.getElementById('conc_nc_no_reg').value || '0') || 0;
        const ndNoReg     = parseFloat(document.getElementById('conc_nd_no_reg').value || '0') || 0;

        const bancoConc  = Math.round((saldoBanco + depTransito - cheqTrans) * 100) / 100;
        const librosConc = Math.round((saldoLibros + ncNoReg - ndNoReg) * 100) / 100;
        const diff       = Math.round((bancoConc - librosConc) * 100) / 100;

        document.getElementById('lblBancoConciliado').textContent  = fmt(bancoConc);
        document.getElementById('lblLibrosConciliado').textContent = fmt(librosConc);
        document.getElementById('lblDiferenciaTotal').textContent  = fmt(diff);

        const caja   = document.getElementById('cajaDiferenciaConc');
        const estado = document.getElementById('lblEstadoDiferencia');
        if (Math.abs(diff) < 0.01) {
            caja.className = 'rounded-xl p-4 border bg-emerald-50 border-emerald-300 text-emerald-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3';
            estado.textContent = 'Conciliación cuadrada al 100% (Diferencia = 0,00). Lista para cerrar e imprimir.';
        } else {
            caja.className = 'rounded-xl p-4 border bg-amber-50 border-amber-300 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3';
            estado.textContent = 'Existe una diferencia pendiente por conciliar entre el saldo bancario y los libros contables.';
        }
    }

    window.recalcularDesdeChecks = () => {
        let ncNoReg = 0;
        let ndNoReg = 0;
        document.querySelectorAll('.chk-banco').forEach(chk => {
            if (!chk.checked) {
                ncNoReg += parseFloat(chk.dataset.debe || '0') || 0;
                ndNoReg += parseFloat(chk.dataset.haber || '0') || 0;
            }
        });

        let depTrans = 0;
        let cheqTrans = 0;
        document.querySelectorAll('.chk-transito').forEach(chk => {
            if (chk.checked) {
                depTrans  += parseFloat(chk.dataset.debe || '0') || 0;
                cheqTrans += parseFloat(chk.dataset.haber || '0') || 0;
            }
        });

        document.getElementById('conc_nc_no_reg').value       = ncNoReg.toFixed(2);
        document.getElementById('conc_nd_no_reg').value       = ndNoReg.toFixed(2);
        document.getElementById('conc_dep_transito').value    = depTrans.toFixed(2);
        document.getElementById('conc_cheques_transito').value = cheqTrans.toFixed(2);
        actualizarResumen();
    };

    document.querySelectorAll('.chk-banco').forEach(chk => {
        chk.addEventListener('change', async () => {
            try {
                await postJson(`${BASE_URL}/index.php?c=bancos&a=toggleConciliado`, {
                    movimiento_id: Number(chk.dataset.id),
                    conciliado: chk.checked ? 1 : 0
                });
                const row = chk.closest('tr');
                if (row) {
                    row.className = chk.checked ? 'bg-emerald-50/60 transition-colors' : 'hover:bg-slate-50 transition-colors';
                }
            } catch (err) {
                chk.checked = !chk.checked;
                Swal.fire({ icon: 'error', title: 'Error', text: err.message });
            }
        });
    });

    document.querySelectorAll('.chk-transito').forEach(chk => {
        chk.addEventListener('change', window.recalcularDesdeChecks);
    });

    document.querySelectorAll('.input-conc').forEach(inp => {
        inp.addEventListener('input', actualizarResumen);
    });

    window.ejecutarAutoConciliacion = async () => {
        try {
            const res = await postJson(`${BASE_URL}/index.php?c=bancos&a=autoConciliar`, {
                cuenta_bancaria_id: CUENTA_ID,
                periodo: PERIODO
            });
            await Swal.fire({ icon: 'success', title: 'Cruce Automático', text: res.message });
            window.location.href = `${BASE_URL}/index.php?c=bancos&a=conciliar&cuenta_id=${CUENTA_ID}&periodo=${encodeURIComponent(PERIODO)}`;
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    };

    window.guardarConciliacion = async (estatus) => {
        try {
            const res = await postJson(`${BASE_URL}/index.php?c=bancos&a=guardarConciliacion`, {
                cuenta_bancaria_id: CUENTA_ID,
                periodo: PERIODO,
                saldo_segun_banco: parseFloat(document.getElementById('conc_saldo_banco').value || '0'),
                saldo_segun_libros: parseFloat(document.getElementById('conc_saldo_libros').value || '0'),
                depositos_en_transito: parseFloat(document.getElementById('conc_dep_transito').value || '0'),
                cheques_en_transito: parseFloat(document.getElementById('conc_cheques_transito').value || '0'),
                notas_debito_no_registradas: parseFloat(document.getElementById('conc_nd_no_reg').value || '0'),
                notas_credito_no_registradas: parseFloat(document.getElementById('conc_nc_no_reg').value || '0'),
                estatus,
                observaciones: document.getElementById('conc_observaciones').value || ''
            });
            await Swal.fire({ icon: 'success', title: 'Conciliación Guardada', text: res.message });
            window.location.href = `${BASE_URL}/index.php?c=bancos&a=conciliar&cuenta_id=${CUENTA_ID}&periodo=${encodeURIComponent(PERIODO)}`;
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Validación', text: err.message });
        }
    };

    actualizarResumen();
})();
</script>
<?php endif; ?>

<script>
window.eliminarBorradorConciliacion = async (id, label) => {
    const confirm = await Swal.fire({
        icon: 'warning',
        title: '¿Eliminar conciliación?',
        text: `Se eliminará la conciliación bancaria (${label}). Esta acción no se puede deshacer.`,
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, borrar',
        cancelButtonText: 'Cancelar'
    });
    if (!confirm.isConfirmed) return;

    const getMeta = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || '';
    const BASE_URL = getMeta('base-url');
    const CSRF = getMeta('csrf-token');

    try {
        const res = await fetch(`${BASE_URL}/index.php?c=bancos&a=eliminarConciliacion`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (!res.ok || !data.ok) {
            throw new Error(data.message || 'No se pudo eliminar el borrador.');
        }
        await Swal.fire({ icon: 'success', title: 'Borrador Eliminado', text: data.message });
        <?php if ($cuentaSeleccionada): ?>
        window.location.href = `${BASE_URL}/index.php?c=bancos&a=conciliar&cuenta_id=<?= (int)$cuentaSeleccionada['id'] ?>&periodo=<?= urlencode($periodo) ?>`;
        <?php else: ?>
        window.location.reload();
        <?php endif; ?>
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    }
};
</script>
