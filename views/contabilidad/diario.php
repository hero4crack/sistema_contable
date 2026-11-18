<?php
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

$emp = $empresaActiva ?? [];
$usuarioRol  = (string)Session::get('usuario_rol', 'contador');
$puedeEditar = in_array($usuarioRol, ['admin', 'contador'], true);

$sumaTotalDebeListado  = 0.0;
$sumaTotalHaberListado = 0.0;
foreach (($asientos ?? []) as $aItem) {
    if ($aItem['estado'] === 'PUBLICADO') {
        $sumaTotalDebeListado  += (float)$aItem['total_debe'];
        $sumaTotalHaberListado += (float)$aItem['total_haber'];
    }
}
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO DEL MÓDULO LIBRO DIARIO -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 no-print">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-400 uppercase tracking-wider">
                <span>Módulo 2 • Contabilidad General y Libros Legales</span>
                <span>•</span>
                <span><?= e($emp['rif'] ?? '') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight mt-0.5">
                Libro Diario y Registro de Asientos Contables
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Partida doble estricta (<code class="font-mono text-emerald-400">SUM(debe) === SUM(haber)</code>) bajo el Código de Comercio Venezolano.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="<?= e(route_url('contabilidad', 'diario', [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'q'           => $filtroBuscar,
                'estado'      => $filtroEstado,
                'export'      => 'csv',
            ])) ?>"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exportar Excel / CSV
            </a>

            <a href="<?= e(route_url('contabilidad', 'diarioPrint', [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'formato'     => 'detallado',
            ])) ?>"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Libro Diario (Registro Mercantil)
            </a>

            <a href="<?= e(route_url('contabilidad', 'diarioPrint', [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'formato'     => 'resumen',
            ])) ?>"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Resumen de Diario (Folio)
            </a>

            <?php if ($puedeEditar): ?>
                <button type="button" id="btnTogglePanelAsiento"
                        onclick="window.JurisContabilidad.togglePanelNuevoAsiento()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-950/50 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo Asiento Contable
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($puedeEditar): ?>
        <!-- PANEL FORMULARIO DINÁMICO DE REGISTRO DE ASIENTO (PARTIDA DOBLE) -->
        <div id="panelNuevoAsiento" class="rounded-2xl bg-slate-900 border border-emerald-500/40 shadow-2xl overflow-hidden no-print">
            <div class="px-6 py-4 bg-slate-950/70 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                        Próximo: <?= e($proximoNumero ?? 'ASI-0001') ?>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-white">Comprobante de Diario (Partida Doble)</h2>
                        <p class="text-[11px] text-slate-400">Solo se listan cuentas de detalle/movimiento activas de <?= e($emp['razon_social'] ?? '') ?>.</p>
                    </div>
                </div>

                <!-- Indicador en Vivo de Cuadre Contable -->
                <div id="badgeEstadoCuadre" class="px-3.5 py-1.5 rounded-xl text-xs font-mono font-bold bg-rose-500/15 text-rose-300 border border-rose-500/30">
                    ESPERANDO MOVIMIENTOS (Bs. 0,00)
                </div>
            </div>

            <form id="formNuevoAsiento" class="p-6 space-y-5">
                <?= Csrf::field() ?>

                <!-- Cabecera del Asiento -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-3">
                        <label for="asi_fecha" class="block text-xs font-medium text-slate-300 mb-1">
                            Fecha Contable <span class="text-rose-400">*</span>
                        </label>
                        <input type="date" id="asi_fecha" name="fecha" required
                               value="<?= e(($emp['periodo_fiscal_activo'] ?? date('Y-m')) . '-' . date('d')) ?>"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                    </div>

                    <div class="sm:col-span-3">
                        <label for="asi_referencia" class="block text-xs font-medium text-slate-300 mb-1">
                            Documento / Referencia
                        </label>
                        <input type="text" id="asi_referencia" name="referencia" maxlength="80"
                               placeholder="Ej: FAC-00189 / TRANSF-4412"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs font-mono text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
                    </div>

                    <div class="sm:col-span-6">
                        <label for="asi_concepto" class="block text-xs font-medium text-slate-300 mb-1">
                            Glosa o Concepto General del Asiento <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" id="asi_concepto" name="concepto" required maxlength="500"
                               placeholder="Ej: P/R Cobro de servicios de administración y depósito bancario..."
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <!-- Tabla Dinámica de Renglones del Asiento -->
                <div class="rounded-xl border border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-950 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="py-2.5 px-3 font-semibold w-10 text-center">#</th>
                                    <th class="py-2.5 px-3 font-semibold min-w-[280px]">Cuenta Contable (Solo Movimiento)</th>
                                    <th class="py-2.5 px-3 font-semibold min-w-[220px]">Descripción de la Línea</th>
                                    <th class="py-2.5 px-3 font-semibold w-40 text-right">Debe (Bs.)</th>
                                    <th class="py-2.5 px-3 font-semibold w-40 text-right">Haber (Bs.)</th>
                                    <th class="py-2.5 px-3 font-semibold w-16 text-center">Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyRenglonesAsiento" class="divide-y divide-slate-800/70 text-xs bg-slate-900/60">
                                <!-- Las filas se inyectan dinámicamente desde JS -->
                            </tbody>
                            <tfoot class="bg-slate-950/90 border-t border-slate-800 text-xs font-mono">
                                <tr>
                                    <td colspan="3" class="py-3 px-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button"
                                                    onclick="window.JurisContabilidad.agregarFilaAsiento()"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-sans font-semibold bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-300 border border-emerald-500/30 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Agregar Línea
                                            </button>

                                            <button type="button"
                                                    onclick="window.JurisContabilidad.cuadrarUltimaFila()"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-sans font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors">
                                                Autocuadrar Diferencia en Última Línea
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-sm text-emerald-400" id="lblTotalDebeAsiento">
                                        Bs. 0,00
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-sm text-emerald-400" id="lblTotalHaberAsiento">
                                        Bs. 0,00
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Barra Inferior de Validación y Guardado -->
                <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                    <div class="flex items-center gap-3 text-xs font-mono">
                        <span class="text-slate-400">Diferencia Debe vs Haber:</span>
                        <span id="lblDiferenciaAsiento" class="font-bold text-slate-200">Bs. 0,00</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button"
                                onclick="window.JurisContabilidad.reiniciarFormularioAsiento()"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors">
                            Limpiar Líneas
                        </button>

                        <button type="submit" id="btnGuardarAsiento" disabled
                                class="px-5 py-2.5 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:opacity-40 disabled:cursor-not-allowed shadow-lg shadow-emerald-950/50 transition-all">
                            Guardar Asiento Contable (Partida Doble)
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Template JSON de Cuentas Imputables para el selector dinámico de JS -->
        <script id="jsonCuentasImputables" type="application/json">
            <?= json_encode($cuentasImputables ?? [], JSON_UNESCAPED_UNICODE) ?>
        </script>
    <?php endif; ?>

    <!-- BARRA DE FILTROS DEL LIBRO DIARIO -->
    <form method="POST" action="<?= e(route_url('contabilidad', 'diario')) ?>" class="rounded-2xl bg-slate-900 border border-slate-800 p-4 flex flex-wrap items-end justify-between gap-3 no-print">
        <?= \Core\Csrf::field() ?>

        <div class="flex flex-wrap items-end gap-3 flex-1">
            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Desde Fecha</label>
                <input type="date" name="fecha_desde" value="<?= e($fechaDesde) ?>"
                       class="bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Hasta Fecha</label>
                <input type="date" name="fecha_hasta" value="<?= e($fechaHasta) ?>"
                       class="bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none">
            </div>

            <div class="flex-1 min-w-[220px]">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Buscar Comprobante / Glosa / Ref.</label>
                <input type="text" name="q" value="<?= e($filtroBuscar ?? '') ?>"
                       placeholder="Ej: ASI-202611, honorarios, FAC-000412..."
                       class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Estado</label>
                <select name="estado"
                        class="bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                    <option value="" <?= ($filtroEstado ?? '') === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="PUBLICADO" <?= ($filtroEstado ?? '') === 'PUBLICADO' ? 'selected' : '' ?>>Publicados</option>
                    <option value="ANULADO" <?= ($filtroEstado ?? '') === 'ANULADO' ? 'selected' : '' ?>>Anulados</option>
                </select>
            </div>

            <button type="submit"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition-colors">
                Consultar Libro Diario
            </button>
        </div>

        <div class="text-right font-mono text-xs">
            <span class="text-slate-400 block">Suma Publicada en Rango:</span>
            <strong class="text-emerald-400"><?= formato_ves($sumaTotalDebeListado) ?></strong>
        </div>
    </form>

    <!-- MEMBRETE OFICIAL PARA IMPRESIÓN -->
    <div class="hidden print-only border-b-2 border-slate-400 pb-3 mb-4">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-lg font-bold"><?= e($emp['razon_social'] ?? '') ?></h2>
                <p class="text-xs font-mono">RIF: <?= e($emp['rif'] ?? '') ?> | Contribuyente <?= e($emp['tipo_contribuyente'] ?? '') ?></p>
                <p class="text-xs"><?= e($emp['direccion_fiscal'] ?? '') ?></p>
            </div>
            <div class="text-right text-xs font-mono">
                <p><strong>LIBRO DIARIO LEGAL</strong></p>
                <p>Desde: <?= e($fechaDesde) ?> Hasta: <?= e($fechaHasta) ?></p>
            </div>
        </div>
    </div>

    <!-- LISTADO CRONOLÓGICO DEL LIBRO DIARIO -->
    <div class="space-y-4 print-container">
        <?php if (!empty($asientos)): ?>
            <?php foreach ($asientos as $asi): ?>
                <?php
                $idAsi     = (int)$asi['id'];
                $esAnulado = ($asi['estado'] === 'ANULADO');
                ?>
                <div class="rounded-2xl bg-slate-900 border <?= $esAnulado ? 'border-rose-500/30 opacity-70' : 'border-slate-800' ?> shadow-lg overflow-hidden">
                    <!-- Cabecera de cada Asiento -->
                    <div class="px-5 py-3 bg-slate-950/70 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="font-mono font-bold text-xs px-2.5 py-1 rounded-lg bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                <?= e($asi['numero_asiento']) ?>
                            </span>
                            <span class="font-mono text-xs text-slate-300">
                                Fecha: <strong class="text-white"><?= e($asi['fecha']) ?></strong>
                            </span>
                            <?php if (!empty($asi['referencia'])): ?>
                                <span class="font-mono text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">
                                    Ref: <?= e($asi['referencia']) ?>
                                </span>
                            <?php endif; ?>
                            <span class="font-mono text-[11px] px-2 py-0.5 rounded bg-slate-800 text-slate-400">
                                Origen: <?= e($asi['origen']) ?>
                            </span>

                            <?php if ($esAnulado): ?>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                    ANULADO
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                    PUBLICADO
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2 no-print">
                            <button type="button"
                                    onclick="window.JurisContabilidad.verDetalleAsientoModal(<?= $idAsi ?>)"
                                    class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                                Ver Comprobante
                            </button>

                            <?php if ($puedeEditar && !$esAnulado): ?>
                                <button type="button"
                                        onclick="window.JurisContabilidad.anularAsiento(<?= $idAsi ?>, <?= e(json_encode($asi['numero_asiento'])) ?>)"
                                        class="px-2.5 py-1 rounded-lg text-xs font-medium bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/20 transition-colors">
                                    Anular
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Glosa / Concepto -->
                    <div class="px-5 py-2.5 bg-slate-900/80 border-b border-slate-800/60 text-xs text-slate-300">
                        <strong class="text-slate-400 uppercase text-[10px] tracking-wider mr-1.5">Concepto / Glosa:</strong>
                        <?= e($asi['concepto']) ?>
                    </div>

                    <!-- Renglones de Partida Doble -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-950/40 text-[10px] uppercase tracking-wider text-slate-400 border-b border-slate-800/60">
                                <tr>
                                    <th class="py-2 px-5 font-semibold w-36">Código Cuenta</th>
                                    <th class="py-2 px-4 font-semibold">Nombre de la Cuenta</th>
                                    <th class="py-2 px-4 font-semibold">Descripción del Movimiento</th>
                                    <th class="py-2 px-4 font-semibold text-right w-36">Debe (Bs.)</th>
                                    <th class="py-2 px-5 font-semibold text-right w-36">Haber (Bs.)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/50">
                                <?php foreach (($asi['detalles'] ?? []) as $det): ?>
                                    <?php
                                    $debeVal  = (float)$det['debe'];
                                    $haberVal = (float)$det['haber'];
                                    ?>
                                    <tr class="hover:bg-slate-800/30">
                                        <td class="py-2.5 px-5 font-mono font-semibold text-emerald-400"><?= e($det['cuenta_codigo']) ?></td>
                                        <td class="py-2.5 px-4 font-medium text-slate-200 <?= $haberVal > 0 ? 'pl-8' : '' ?>">
                                            <?= e($det['cuenta_nombre']) ?>
                                        </td>
                                        <td class="py-2.5 px-4 text-slate-400"><?= e($det['descripcion']) ?></td>
                                        <td class="py-2.5 px-4 font-mono text-right <?= $debeVal > 0 ? 'text-slate-100 font-semibold' : 'text-slate-600' ?>">
                                            <?= $debeVal > 0 ? formato_ves($debeVal, false) : '0,00' ?>
                                        </td>
                                        <td class="py-2.5 px-5 font-mono text-right <?= $haberVal > 0 ? 'text-slate-100 font-semibold' : 'text-slate-600' ?>">
                                            <?= $haberVal > 0 ? formato_ves($haberVal, false) : '0,00' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-slate-950/60 border-t border-slate-800 font-mono text-xs">
                                <tr>
                                    <td colspan="3" class="py-2.5 px-5 text-right font-semibold text-slate-400">
                                        TOTALES DEL COMPROBANTE <?= e($asi['numero_asiento']) ?>:
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-bold text-emerald-400">
                                        <?= formato_ves($asi['total_debe']) ?>
                                    </td>
                                    <td class="py-2.5 px-5 text-right font-bold text-emerald-400">
                                        <?= formato_ves($asi['total_haber']) ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Totales Generales del Libro Diario -->
            <div class="rounded-2xl bg-slate-900 border border-emerald-500/30 p-4 flex flex-wrap items-center justify-between gap-4 font-mono text-xs">
                <span class="font-bold text-white uppercase">
                    SUMAS IGUALES DEL LIBRO DIARIO (ASIENTOS PUBLICADOS EN EL PERÍODO):
                </span>
                <div class="flex items-center gap-6">
                    <span>Total Debe: <strong class="text-emerald-400 text-sm"><?= formato_ves($sumaTotalDebeListado) ?></strong></span>
                    <span>Total Haber: <strong class="text-emerald-400 text-sm"><?= formato_ves($sumaTotalHaberListado) ?></strong></span>
                </div>
            </div>
        <?php else: ?>
            <div class="rounded-2xl bg-slate-900 border border-slate-800 p-12 text-center text-slate-500">
                No se encontraron asientos contables en el rango de fechas seleccionado para esta empresa.
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL VER DETALLE DEL COMPROBANTE CONTABLE -->
<div id="modalDetalleAsiento" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 no-print">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden">
        <div class="px-6 py-4 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 id="modalAsiNumero" class="text-base font-mono font-bold text-emerald-400">Comprobante</h3>
                <p id="modalAsiFecha" class="text-xs text-slate-400 mt-0.5"></p>
            </div>
            <button type="button" onclick="window.JurisContabilidad.cerrarModalDetalleAsiento()"
                    class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div class="rounded-xl bg-slate-950 border border-slate-800 p-3.5 text-xs">
                <span class="text-slate-400 block font-semibold mb-1">Glosa / Concepto:</span>
                <p id="modalAsiConcepto" class="text-slate-100"></p>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-950 text-[11px] uppercase text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-2.5 px-4">Código</th>
                            <th class="py-2.5 px-4">Cuenta</th>
                            <th class="py-2.5 px-4">Descripción</th>
                            <th class="py-2.5 px-4 text-right">Debe</th>
                            <th class="py-2.5 px-4 text-right">Haber</th>
                        </tr>
                    </thead>
                    <tbody id="modalAsiTbody" class="divide-y divide-slate-800"></tbody>
                    <tfoot class="bg-slate-950 font-mono font-bold border-t border-slate-800">
                        <tr>
                            <td colspan="3" class="py-2.5 px-4 text-right text-slate-400">TOTALES IGUALES:</td>
                            <td id="modalAsiTotalDebe" class="py-2.5 px-4 text-right text-emerald-400"></td>
                            <td id="modalAsiTotalHaber" class="py-2.5 px-4 text-right text-emerald-400"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
