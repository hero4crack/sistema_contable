<?php
declare(strict_types=1);

$emp = $empresaActiva ?? [];
$mayorList = $libroMayor ?? [];
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO DEL LIBRO MAYOR ANALÍTICO -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 no-print">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-400 uppercase tracking-wider">
                <span>Módulo 2 • Contabilidad General y Libros Legales</span>
                <span>•</span>
                <span><?= e($emp['rif'] ?? '') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight mt-0.5">
                Libro Mayor Analítico
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Cálculo de Saldo Anterior, movimientos cronológicos y Saldo Acumulado según naturaleza contable para <strong class="text-slate-200"><?= e($emp['razon_social'] ?? '') ?></strong>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="<?= e(route_url('contabilidad', 'mayor', [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'cuenta_id'   => $cuentaSeleccionada ?: '',
                'export'      => 'csv',
            ])) ?>"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exportar Excel / CSV
            </a>

            <a href="<?= e(route_url('contabilidad', 'mayorPrint', [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'cuenta_id'   => $cuentaSeleccionada ?: '',
                'formato'     => 'clasico',
                'agrupar_mes' => '1',
            ])) ?>"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/40 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Mayor Analítico (Formato Folio)
            </a>

            <a href="<?= e(route_url('contabilidad', 'mayorPrint', [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'cuenta_id'   => $cuentaSeleccionada ?: '',
                'formato'     => 'tabla',
            ])) ?>"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Libro Mayor (Tabla Mercantil)
            </a>
        </div>
    </div>

    <!-- BARRA DE FILTROS: SELECTOR DE CUENTA Y RANGO DE FECHAS -->
    <form method="GET" action="<?= e(BASE_URL . '/index.php') ?>" class="rounded-2xl bg-slate-900 border border-slate-800 p-4 flex flex-wrap items-end justify-between gap-4 no-print">
        <input type="hidden" name="c" value="contabilidad">
        <input type="hidden" name="a" value="mayor">

        <div class="flex flex-wrap items-end gap-3 flex-1">
            <div class="flex-1 min-w-[280px]">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">
                    Cuenta Contable a Analizar
                </label>
                <select name="cuenta_id"
                        class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none">
                    <option value="0">-- Todas las cuentas con movimiento o saldo --</option>
                    <?php foreach (($cuentasImputables ?? []) as $cItem): ?>
                        <option value="<?= (int)$cItem['id'] ?>" <?= (int)($cuentaSeleccionada ?? 0) === (int)$cItem['id'] ? 'selected' : '' ?>>
                            <?= e($cItem['codigo']) ?> — <?= e($cItem['nombre']) ?> (<?= e($cItem['naturaleza']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Fecha Inicial</label>
                <input type="date" name="fecha_desde" value="<?= e($fechaDesde) ?>"
                       class="bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Fecha Final</label>
                <input type="date" name="fecha_hasta" value="<?= e($fechaHasta) ?>"
                       class="bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none">
            </div>

            <button type="submit"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition-colors">
                Generar Mayor Analítico
            </button>
        </div>

        <div class="text-xs font-mono text-slate-400">
            Cuentas listadas: <strong class="text-white"><?= count($mayorList) ?></strong>
        </div>
    </form>

    <!-- MEMBRETE OFICIAL DE LA EMPRESA ACTIVA -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print-container">
        <div>
            <h2 class="text-base font-bold text-white uppercase"><?= e($emp['razon_social'] ?? '') ?></h2>
            <p class="text-xs font-mono text-emerald-400">
                RIF: <?= e($emp['rif'] ?? '') ?> • Contribuyente <?= e($emp['tipo_contribuyente'] ?? '') ?>
            </p>
            <p class="text-xs text-slate-400 mt-0.5"><?= e($emp['direccion_fiscal'] ?? '') ?></p>
        </div>
        <div class="text-left sm:text-right font-mono text-xs">
            <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-200 font-bold block">
                LIBRO MAYOR ANALÍTICO
            </span>
            <span class="text-slate-400 block mt-1">
                Período: <?= e($fechaDesde) ?> al <?= e($fechaHasta) ?>
            </span>
        </div>
    </div>

    <!-- LISTADO ANALÍTICO POR CUENTA -->
    <?php if (!empty($mayorList)): ?>
        <div class="space-y-6 print-container">
            <?php foreach ($mayorList as $bloque): ?>
                <?php
                $cta          = $bloque['cuenta'];
                $esDeudora    = ($cta['naturaleza'] === 'DEUDORA');
                $saldoAnt     = (float)$bloque['saldo_anterior'];
                $saldoFinal   = (float)$bloque['saldo_final'];
                $totDebePer   = (float)$bloque['total_debe_periodo'];
                $totHaberPer  = (float)$bloque['total_haber_periodo'];
                $movimientos  = $bloque['movimientos'] ?? [];
                ?>
                <div class="rounded-2xl bg-slate-900 border border-slate-800 shadow-lg overflow-hidden">
                    <!-- Cabecera de la Cuenta -->
                    <div class="px-5 py-3.5 bg-slate-950/80 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                <?= e($cta['codigo']) ?>
                            </span>
                            <h3 class="text-sm font-bold text-white"><?= e($cta['nombre']) ?></h3>
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-slate-800 text-slate-300 border border-slate-700">
                                <?= e($cta['tipo']) ?>
                            </span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono <?= $esDeudora ? 'bg-sky-500/15 text-sky-300 border border-sky-500/30' : 'bg-amber-500/15 text-amber-300 border border-amber-500/30' ?>">
                                Naturaleza <?= e($cta['naturaleza']) ?> (<?= $esDeudora ? '+Debe / -Haber' : '+Haber / -Debe' ?>)
                            </span>
                        </div>

                        <div class="flex items-center gap-4 font-mono text-xs">
                            <div>
                                <span class="text-slate-400">Saldo Anterior:</span>
                                <strong class="text-slate-200 ml-1"><?= formato_ves($saldoAnt) ?></strong>
                            </div>
                            <div class="pl-3 border-l border-slate-800">
                                <span class="text-slate-400">Saldo Final:</span>
                                <strong class="text-emerald-400 ml-1"><?= formato_ves($saldoFinal) ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Movimientos Cronológicos y Saldo Acumulado -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-950/50 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="py-2.5 px-5 font-semibold w-28">Fecha</th>
                                    <th class="py-2.5 px-3 font-semibold w-36">N° Comprobante</th>
                                    <th class="py-2.5 px-3 font-semibold w-28">Referencia</th>
                                    <th class="py-2.5 px-4 font-semibold">Descripción del Asiento / Glosa</th>
                                    <th class="py-2.5 px-4 font-semibold text-right w-36">Debe (Bs.)</th>
                                    <th class="py-2.5 px-4 font-semibold text-right w-36">Haber (Bs.)</th>
                                    <th class="py-2.5 px-5 font-semibold text-right w-40">Saldo Acumulado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <!-- Fila de Saldo Anterior -->
                                <tr class="bg-slate-950/30 font-mono text-slate-300">
                                    <td class="py-2.5 px-5"><?= e($fechaDesde) ?></td>
                                    <td class="py-2.5 px-3 text-slate-400">SALDO-ANT</td>
                                    <td class="py-2.5 px-3 text-slate-500">—</td>
                                    <td class="py-2.5 px-4 font-sans font-medium text-slate-300">
                                        Saldo Anterior acumulado antes del <?= e($fechaDesde) ?>
                                    </td>
                                    <td class="py-2.5 px-4 text-right text-slate-500">0,00</td>
                                    <td class="py-2.5 px-4 text-right text-slate-500">0,00</td>
                                    <td class="py-2.5 px-5 text-right font-bold text-white">
                                        <?= formato_ves($saldoAnt, false) ?>
                                    </td>
                                </tr>

                                <?php if (!empty($movimientos)): ?>
                                    <?php foreach ($movimientos as $mov): ?>
                                        <?php
                                        $debeMov  = (float)$mov['debe'];
                                        $haberMov = (float)$mov['haber'];
                                        $saldoAcum= (float)$mov['saldo_acumulado'];
                                        ?>
                                        <tr class="hover:bg-slate-800/40 transition-colors">
                                            <td class="py-2.5 px-5 font-mono text-slate-300 whitespace-nowrap"><?= e($mov['fecha']) ?></td>
                                            <td class="py-2.5 px-3 font-mono font-semibold text-emerald-400 whitespace-nowrap">
                                                <?= e($mov['numero_asiento']) ?>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono text-slate-400 whitespace-nowrap">
                                                <?= e($mov['referencia'] ?: '—') ?>
                                            </td>
                                            <td class="py-2.5 px-4 text-slate-200">
                                                <?= e($mov['descripcion_linea'] ?: $mov['concepto']) ?>
                                            </td>
                                            <td class="py-2.5 px-4 font-mono text-right <?= $debeMov > 0 ? 'text-slate-100 font-semibold' : 'text-slate-600' ?>">
                                                <?= $debeMov > 0 ? formato_ves($debeMov, false) : '0,00' ?>
                                            </td>
                                            <td class="py-2.5 px-4 font-mono text-right <?= $haberMov > 0 ? 'text-slate-100 font-semibold' : 'text-slate-600' ?>">
                                                <?= $haberMov > 0 ? formato_ves($haberMov, false) : '0,00' ?>
                                            </td>
                                            <td class="py-2.5 px-5 font-mono text-right font-bold <?= $saldoAcum >= 0 ? 'text-emerald-300' : 'text-rose-400' ?>">
                                                <?= formato_ves($saldoAcum, false) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="py-4 px-5 text-center text-slate-500">
                                            Sin movimientos durante el rango <?= e($fechaDesde) ?> al <?= e($fechaHasta) ?>.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="bg-slate-950/80 border-t border-slate-800 font-mono text-xs">
                                <tr>
                                    <td colspan="4" class="py-3 px-5 text-right font-semibold text-slate-400">
                                        TOTALES DEL PERÍODO Y SALDO FINAL (<?= e($cta['codigo']) ?>):
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-200">
                                        <?= formato_ves($totDebePer, false) ?>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-200">
                                        <?= formato_ves($totHaberPer, false) ?>
                                    </td>
                                    <td class="py-3 px-5 text-right font-bold text-emerald-400">
                                        <?= formato_ves($saldoFinal) ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="rounded-2xl bg-slate-900 border border-slate-800 p-12 text-center text-slate-500">
            No existen cuentas con saldo ni movimientos registrados para los filtros seleccionados.
        </div>
    <?php endif; ?>

</div>
