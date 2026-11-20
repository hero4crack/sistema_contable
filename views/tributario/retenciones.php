<?php
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

$emp     = $empresaActiva ?? [];
$cfg     = $configFiscal ?? [];
$retIva  = $retencionesIva ?? [];
$retIslr = $retencionesIslr ?? [];
$comprasPend = $comprasPendientesRet ?? [];
$conceptos   = $conceptosIslr ?? [];
$tab         = $tabActiva ?? 'iva';

$usuarioRol  = (string)Session::get('usuario_rol', 'contador');
$puedeEditar = in_array($usuarioRol, ['admin', 'contador'], true);
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO DEL MÓDULO DE RETENCIONES -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 no-print">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-700 uppercase tracking-wider">
                <span>Módulo 3 • Tributario SENIAT</span>
                <span>•</span>
                <span><?= e($emp['rif'] ?? '') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-0.5">
                Comprobantes de Retención de IVA e ISLR
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Providencia SNAT/2015/0049 (SNAT/2025/000054) para IVA y Decreto 1.808 para ISLR con sustraendo automático según Unidad Tributaria.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-mono">
                UT Vigente: <strong class="text-emerald-700">Bs. <?= number_format((float)($cfg['valor_unidad_tributaria'] ?? 9), 2, ',', '.') ?></strong>
                <span class="mx-1.5 text-slate-600">|</span>
                Factor: <strong class="text-amber-800"><?= number_format((float)($cfg['factor_sustraendo_islr'] ?? 83.3334), 4, ',', '.') ?></strong>
            </div>

            <a href="<?= e(route_url('tributario', 'libros', ['periodo' => $periodo])) ?>"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors">
                Ir a Libros de Compras y Ventas →
            </a>
        </div>
    </div>

    <!-- BARRA DE PESTAÑAS Y FILTRO DE PERÍODO -->
    <div class="rounded-2xl bg-white border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    data-tab="iva"
                    onclick="window.JurisTributario.cambiarTabRetenciones('iva')"
                    class="btn-tab-ret px-4 py-2 rounded-xl text-xs font-semibold transition-all border <?= $tab === 'iva' ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
                A) Comprobantes de Retención de IVA (<?= count($retIva) ?>)
            </button>

            <button type="button"
                    data-tab="islr"
                    onclick="window.JurisTributario.cambiarTabRetenciones('islr')"
                    class="btn-tab-ret px-4 py-2 rounded-xl text-xs font-semibold transition-all border <?= $tab === 'islr' ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
                B) Comprobantes de Retención de ISLR — Decreto 1.808 (<?= count($retIslr) ?>)
            </button>
        </div>

        <form method="POST" action="<?= e(route_url('tributario', 'retenciones')) ?>" class="flex items-center gap-2.5">
            <?= \Core\Csrf::field() ?>
            <input type="hidden" id="inputTabActivaRet" name="tab" value="<?= e($tab) ?>">

            <label class="text-xs font-medium text-slate-400">Período Fiscal:</label>
            <input type="month" name="periodo" value="<?= e($periodo) ?>"
                   class="bg-slate-50 border border-slate-300 focus:border-emerald-500 rounded-xl px-3 py-1.5 text-xs font-mono text-slate-900">
            <button type="submit"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-300">
                Consultar
            </button>
        </form>
    </div>

    <!-- =======================================================================
         SECCIÓN A: RETENCIONES DE IVA (CORRELATIVO 14 DÍGITOS AAAAMM00000001)
    ======================================================================== -->
    <section id="seccionRet_iva" class="seccion-ret <?= $tab === 'iva' ? '' : 'hidden' ?> space-y-6">

        <?php if ($puedeEditar): ?>
            <!-- Formulario de Emisión de Comprobante de Retención IVA desde Facturas del Libro de Compras -->
            <div class="rounded-2xl bg-white border border-amber-500/30 shadow-xl overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-amber-500/15 text-amber-800 border border-amber-500/30">
                                Próximo Correlativo (14 dígitos): <?= e($proximoCorrelativoIva ?? '') ?>
                            </span>
                            <h2 class="text-sm font-bold text-slate-900">Emitir Comprobante de Retención de IVA</h2>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Seleccione una o más facturas del mismo proveedor registradas en el Libro de Compras para agruparlas en el comprobante.
                        </p>
                    </div>

                    <a href="<?= e(route_url('tributario', 'libros', ['tab' => 'compras', 'periodo' => $periodo])) ?>"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-800 border border-emerald-500/30 transition-colors">
                        + Cargar Nueva Factura en Libro de Compras
                    </a>
                </div>

                <form id="formEmitirRetencionIva" class="p-6 space-y-5">
                    <?= Csrf::field() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Fecha de Emisión <span class="text-rose-400">*</span></label>
                            <input type="date" name="fecha_emision" required value="<?= e($periodo . '-' . date('d')) ?>"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-slate-900">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Período Fiscal</label>
                            <input type="month" name="periodo_fiscal" required value="<?= e($periodo) ?>"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-slate-900">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-700 mb-1">% de Retención IVA <span class="text-rose-400">*</span></label>
                            <select id="ret_iva_porcentaje" name="porcentaje_retencion"
                                    onchange="window.JurisTributario.recalcularSeleccionRetIva()"
                                    class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-slate-900">
                                <option value="75" <?= (int)($emp['porcentaje_retencion_iva'] ?? 75) !== 100 ? 'selected' : '' ?>>75% (Retención Ordinaria)</option>
                                <option value="100" <?= (int)($emp['porcentaje_retencion_iva'] ?? 75) === 100 ? 'selected' : '' ?>>100% (Retención Total)</option>
                            </select>
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Total IVA a Retener</label>
                            <input type="text" id="ret_iva_monto_preview" readonly value="Bs. 0,00"
                                   class="w-full rounded-xl bg-slate-50 border border-amber-500/40 px-3 py-2 text-xs font-mono font-bold text-right text-amber-800">
                        </div>

                        <div class="sm:col-span-12">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Dirección Fiscal del Sujeto Retenido (Opcional si ya está en la factura)</label>
                            <input type="text" id="ret_iva_direccion" name="direccion_proveedor" placeholder="Av. Principal, Edificio, Local, Ciudad, Estado..."
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-xs text-slate-900">
                        </div>
                    </div>

                    <!-- Lista de Facturas del Libro de Compras pendientes por retener -->
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-700">
                                Facturas en Libro de Compras sin Comprobante de Retención IVA (<?= count($comprasPend) ?> disponibles)
                            </span>
                            <span class="text-[11px] font-mono text-slate-400">Marque una o varias facturas del mismo RIF</span>
                        </div>

                        <div class="overflow-x-auto max-h-56">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-50 text-[11px] uppercase text-slate-400 border-b border-slate-200">
                                    <tr>
                                        <th class="py-2 px-4 w-10 text-center">Sel.</th>
                                        <th class="py-2 px-3">Fecha</th>
                                        <th class="py-2 px-3">RIF Proveedor</th>
                                        <th class="py-2 px-3">Razón Social</th>
                                        <th class="py-2 px-3">N° Factura / Control</th>
                                        <th class="py-2 px-3 text-right">Base Imponible</th>
                                        <th class="py-2 px-4 text-right">IVA Causado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200/60 font-mono">
                                    <?php if (!empty($comprasPend)): ?>
                                        <?php foreach ($comprasPend as $cp): ?>
                                            <tr class="hover:bg-slate-50">
                                                <td class="py-2.5 px-4 text-center">
                                                    <input type="checkbox" name="compras_ids[]" value="<?= (int)$cp['id'] ?>"
                                                           data-rif="<?= e($cp['rif_proveedor']) ?>"
                                                           data-iva="<?= (float)$cp['iva_causado_total'] ?>"
                                                           data-dir="<?= e($cp['direccion_proveedor'] ?? '') ?>"
                                                           onchange="window.JurisTributario.recalcularSeleccionRetIva()"
                                                           class="chk-factura-ret-iva rounded border-slate-300 text-emerald-500">
                                                </td>
                                                <td class="py-2.5 px-3 text-slate-700"><?= e($cp['fecha_factura']) ?></td>
                                                <td class="py-2.5 px-3 text-emerald-700 font-semibold"><?= e($cp['rif_proveedor']) ?></td>
                                                <td class="py-2.5 px-3 font-sans text-slate-900"><?= e($cp['nombre_proveedor']) ?></td>
                                                <td class="py-2.5 px-3 text-slate-700"><?= e($cp['numero_factura']) ?> / <?= e($cp['numero_control']) ?></td>
                                                <td class="py-2.5 px-3 text-right text-slate-800"><?= formato_ves($cp['base_imponible_total']) ?></td>
                                                <td class="py-2.5 px-4 text-right font-bold text-sky-800"><?= formato_ves($cp['iva_causado_total']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="py-6 text-center font-sans text-slate-500">
                                                Todas las facturas actuales del Libro de Compras ya cuentan con su comprobante de retención de IVA.
                                                Puede registrar una nueva factura desde el botón superior "+ Cargar Nueva Factura en Libro de Compras".
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" id="btnEmitirRetIva" <?= empty($comprasPend) ? 'disabled' : '' ?>
                                class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-900 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 disabled:opacity-40 shadow-lg transition-all">
                            Generar Comprobante Oficial de Retención de IVA
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Listado Histórico de Comprobantes de Retención de IVA Emitidos -->
        <div class="rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Comprobantes de Retención de IVA Emitidos (Período <?= e($periodo) ?>)</h3>
                <span class="text-xs font-mono text-slate-400">Formato 14 dígitos: AAAAMM00000001</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 text-[11px] uppercase text-slate-400 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">N° Comprobante (14 Dígitos)</th>
                            <th class="py-3 px-3">Fecha Emisión</th>
                            <th class="py-3 px-3">RIF Sujeto Retenido</th>
                            <th class="py-3 px-4">Nombre o Razón Social</th>
                            <th class="py-3 px-3 text-center">Facturas</th>
                            <th class="py-3 px-3 text-right">Monto Total</th>
                            <th class="py-3 px-3 text-right">IVA Causado</th>
                            <th class="py-3 px-3 text-center">% Ret.</th>
                            <th class="py-3 px-4 text-right">Monto IVA Retenido</th>
                            <th class="py-3 px-5 text-right">Acciones / Impresión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60 font-mono">
                        <?php if (!empty($retIva)): ?>
                            <?php foreach ($retIva as $ri): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="py-3 px-4 font-bold text-amber-800"><?= e($ri['numero_comprobante']) ?></td>
                                    <td class="py-3 px-3 text-slate-700"><?= e($ri['fecha_emision']) ?></td>
                                    <td class="py-3 px-3 text-emerald-700 font-semibold"><?= e($ri['rif_proveedor']) ?></td>
                                    <td class="py-3 px-4 font-sans font-medium text-slate-900"><?= e($ri['nombre_proveedor']) ?></td>
                                    <td class="py-3 px-3 text-center text-slate-700"><?= (int)($ri['cantidad_facturas'] ?: 1) ?></td>
                                    <td class="py-3 px-3 text-right text-slate-700"><?= formato_ves($ri['total_facturas'] ?? 0) ?></td>
                                    <td class="py-3 px-3 text-right text-slate-800"><?= formato_ves($ri['monto_iva_causado']) ?></td>
                                    <td class="py-3 px-3 text-center text-slate-700"><?= number_format((float)$ri['porcentaje_retencion'], 0) ?>%</td>
                                    <td class="py-3 px-4 text-right font-bold text-emerald-700"><?= formato_ves($ri['monto_iva_retenido']) ?></td>
                                    <td class="py-3 px-5 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <?php if ($puedeEditar): ?>
                                                <button type="button"
                                                        onclick='abrirEditarRetIva(<?= e(json_encode($ri)) ?>)'
                                                        class="px-2.5 py-1.5 rounded-lg text-xs font-sans font-medium bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 transition-colors">
                                                    Editar
                                                </button>
                                                <button type="button"
                                                        onclick="eliminarRetencion('iva', <?= (int)$ri['id'] ?>, <?= e(json_encode($ri['numero_comprobante'])) ?>)"
                                                        class="px-2.5 py-1.5 rounded-lg text-xs font-sans font-medium bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition-colors">
                                                    Eliminar
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?= e(route_url('tributario', 'comprobanteIva', ['id' => (int)$ri['id']])) ?>"
                                               target="_blank"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-sans font-semibold bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-800 border border-emerald-500/30 transition-colors">
                                                Imprimir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="py-8 text-center font-sans text-slate-500">
                                    No se han emitido comprobantes de retención de IVA en el período <?= e($periodo) ?>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </section>

    <!-- =======================================================================
         SECCIÓN B: RETENCIONES DE ISLR (DECRETO 1.808 CON SUSTRAENDO)
    ======================================================================== -->
    <section id="seccionRet_islr" class="seccion-ret <?= $tab === 'islr' ? '' : 'hidden' ?> space-y-6">

        <?php if ($puedeEditar): ?>
            <div class="rounded-2xl bg-white border border-sky-500/30 shadow-xl overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-sky-500/15 text-sky-800 border border-sky-500/30">
                                Próximo: <?= e($proximoCorrelativoIslr ?? '') ?>
                            </span>
                            <h2 class="text-sm font-bold text-slate-900">Emitir Comprobante de Retención de ISLR (Decreto 1.808)</h2>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Cálculo automático: <code class="font-mono text-emerald-700">Monto Retenido = (Base Imponible × % Retención) − Sustraendo</code>
                        </p>
                    </div>
                </div>

                <form id="formEmitirRetencionIslr" class="p-6 space-y-5"
                      data-ut="<?= (float)($cfg['valor_unidad_tributaria'] ?? 9.00) ?>"
                      data-factor="<?= (float)($cfg['factor_sustraendo_islr'] ?? 83.3334) ?>">
                    <?= Csrf::field() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Fecha de Retención <span class="text-rose-400">*</span></label>
                            <input type="date" name="fecha_emision" required value="<?= e($periodo . '-' . date('d')) ?>"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-slate-900">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-700 mb-1">RIF Sujeto Pasivo <span class="text-rose-400">*</span></label>
                            <input type="text" name="rif_beneficiario" required maxlength="15" placeholder="J-40206401-2 / V-12345678-9"
                                   class="input-rif-format w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono uppercase text-slate-900">
                        </div>

                        <div class="sm:col-span-6">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nombre y Apellido / Razón Social <span class="text-rose-400">*</span></label>
                            <input type="text" name="nombre_beneficiario" required maxlength="200" placeholder="ESCRITORIO JURÍDICO JURIS CONTABLE, C.A."
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-xs text-slate-900">
                        </div>

                        <div class="sm:col-span-6">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Dirección Fiscal del Sujeto Retenido</label>
                            <input type="text" name="direccion_beneficiario" maxlength="250" placeholder="Av. La Paz, CC Inversiones Pareca, Piso 1, Ofic. 1-01, Puerto Cabello..."
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-xs text-slate-900">
                        </div>

                        <div class="sm:col-span-4">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Correo Electrónico (SNAT/2026/00093) <span class="text-rose-400">*</span></label>
                            <input type="email" name="email_beneficiario" required maxlength="150" placeholder="proveedor@correo.com"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-xs text-slate-900">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Tasa BCV (Bs./USD) <span class="text-rose-400">*</span></label>
                            <input type="number" step="0.0001" min="0.01" name="tasa_bcv" required
                                   value="<?= e(number_format((float)($emp['tasa_bcv_actual'] ?? 45.00), 2, '.', '')) ?>"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-slate-900">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">N° Factura</label>
                            <input type="text" name="numero_factura" placeholder="005548"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-slate-900">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">N° Control</label>
                            <input type="text" name="numero_control" placeholder="00-005548"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-slate-900">
                        </div>

                        <!-- Selector de Concepto Decreto 1.808 -->
                        <div class="sm:col-span-8">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Concepto de Retención ISLR (Catálogo Decreto 1.808) <span class="text-rose-400">*</span></label>
                            <select id="islr_select_concepto"
                                    onchange="window.JurisTributario.seleccionarConceptoIslr()"
                                    class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-xs text-slate-900">
                                <?php foreach ($conceptos as $con): ?>
                                    <option value="<?= e($con['codigo']) ?>"
                                            data-nombre="<?= e($con['nombre']) ?>"
                                            data-persona="<?= e($con['tipo_persona']) ?>"
                                            data-tarifa="<?= (float)$con['tarifa_porcentaje'] ?>"
                                            data-sustraendo="<?= (int)$con['aplica_sustraendo'] ?>">
                                        [<?= e($con['codigo']) ?>] <?= e($con['nombre']) ?> — <?= number_format((float)$con['tarifa_porcentaje'], 0) ?>% <?= (int)$con['aplica_sustraendo'] === 1 ? '(Con Sustraendo PNR)' : '(Sin Sustraendo)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="islr_codigo_concepto" name="codigo_concepto" value="004-PJ">
                            <input type="hidden" id="islr_descripcion_concepto" name="descripcion_concepto" value="Honorarios Profesionales No Mercantiles (Persona Jurídica Domiciliada)">
                        </div>

                        <div class="sm:col-span-4">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Tipo de Persona</label>
                            <select id="islr_tipo_persona" name="tipo_persona"
                                    onchange="window.JurisTributario.recalcularIslr()"
                                    class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-slate-900">
                                <option value="PJD">PJD — Persona Jurídica Domiciliada</option>
                                <option value="PNR">PNR — Persona Natural Residente (Aplica Sustraendo)</option>
                                <option value="PNNR">PNNR — Persona Natural No Residente</option>
                                <option value="PJND">PJND — Persona Jurídica No Domiciliada</option>
                            </select>
                        </div>

                        <!-- Cifras del Cálculo ISLR -->
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Monto Total Factura (Bs.)</label>
                            <input type="number" step="0.01" min="0" id="islr_monto_total" name="monto_total_factura" value="0.00"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-right text-slate-900">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Base Imponible Sujeta a Ret. <span class="text-rose-400">*</span></label>
                            <input type="number" step="0.01" min="0" id="islr_base_imponible" name="base_imponible" required value="0.00"
                                   oninput="window.JurisTributario.recalcularIslr()"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-right text-slate-900">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">% Retención</label>
                            <input type="number" step="0.01" min="0" id="islr_porcentaje" name="porcentaje_islr" value="5.00"
                                   oninput="window.JurisTributario.recalcularIslr()"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3 py-2 text-xs font-mono text-right text-slate-900">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Sustraendo (UT)</label>
                            <input type="text" id="islr_sustraendo_vista" readonly value="0,00"
                                   class="w-full rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-xs font-mono text-right text-amber-800">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">ISLR Retenido</label>
                            <input type="text" id="islr_retenido_vista" readonly value="Bs. 0,00"
                                   class="w-full rounded-xl bg-slate-50 border border-emerald-500/40 px-3 py-2 text-xs font-mono font-bold text-right text-emerald-700">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" id="btnEmitirRetIslr"
                                class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-900 bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 shadow-lg transition-all">
                            Generar Comprobante Oficial de Retención de ISLR
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Listado Histórico de Comprobantes de Retención de ISLR Emitidos -->
        <div class="rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Comprobantes de Retención de ISLR Emitidos (Período <?= e($periodo) ?>)</h3>
                <span class="text-xs font-mono text-slate-400">Decreto 1.808 — Gaceta Oficial 36.203</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 text-[11px] uppercase text-slate-400 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">N° Comprobante</th>
                            <th class="py-3 px-3">Fecha</th>
                            <th class="py-3 px-3">RIF Beneficiario</th>
                            <th class="py-3 px-4">Nombre o Razón Social</th>
                            <th class="py-3 px-3">Factura / N° Control</th>
                            <th class="py-3 px-3">Concepto / Código</th>
                            <th class="py-3 px-3 text-right">Monto Total</th>
                            <th class="py-3 px-3 text-right">Base Imponible</th>
                            <th class="py-3 px-2 text-center">% Ret.</th>
                            <th class="py-3 px-3 text-right">Sustraendo</th>
                            <th class="py-3 px-4 text-right">ISLR Retenido</th>
                            <th class="py-3 px-5 text-right">Acciones / Impresión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60 font-mono">
                        <?php if (!empty($retIslr)): ?>
                            <?php foreach ($retIslr as $r): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="py-3 px-4 font-bold text-sky-800"><?= e($r['numero_comprobante']) ?></td>
                                    <td class="py-3 px-3 text-slate-700"><?= e($r['fecha_emision']) ?></td>
                                    <td class="py-3 px-3 text-emerald-700 font-semibold"><?= e($r['rif_beneficiario']) ?></td>
                                    <td class="py-3 px-4 font-sans font-medium text-slate-900"><?= e($r['nombre_beneficiario']) ?></td>
                                    <td class="py-3 px-3 text-slate-700 whitespace-nowrap">
                                        <?= e($r['numero_factura'] ?: 'S/N') ?>
                                        <span class="text-slate-400 block text-[10px]">Ctrl: <?= e($r['numero_control'] ?: 'S/C') ?></span>
                                    </td>
                                    <td class="py-3 px-3 font-sans text-slate-700">
                                        <span class="font-mono text-amber-800">[<?= e($r['codigo_concepto']) ?>]</span>
                                        <?= e($r['tipo_persona']) ?>
                                    </td>
                                    <td class="py-3 px-3 text-right text-slate-700"><?= formato_ves($r['monto_total_factura'] ?? $r['base_imponible']) ?></td>
                                    <td class="py-3 px-3 text-right text-slate-800"><?= formato_ves($r['base_imponible']) ?></td>
                                    <td class="py-3 px-2 text-center text-slate-700"><?= number_format((float)$r['porcentaje_islr'], 2, ',', '.') ?>%</td>
                                    <td class="py-3 px-3 text-right text-amber-800"><?= formato_ves($r['sustraendo'], false) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-emerald-700"><?= formato_ves($r['monto_retenido']) ?></td>
                                    <td class="py-3 px-5 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <?php if ($puedeEditar): ?>
                                                <button type="button"
                                                        onclick='abrirEditarRetIslr(<?= e(json_encode($r)) ?>)'
                                                        class="px-2.5 py-1.5 rounded-lg text-xs font-sans font-medium bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 transition-colors">
                                                    Editar
                                                </button>
                                                <button type="button"
                                                        onclick="eliminarRetencion('islr', <?= (int)$r['id'] ?>, <?= e(json_encode($r['numero_comprobante'])) ?>)"
                                                        class="px-2.5 py-1.5 rounded-lg text-xs font-sans font-medium bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition-colors">
                                                    Eliminar
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?= e(route_url('tributario', 'comprobanteIslr', ['id' => (int)$r['id']])) ?>"
                                               target="_blank"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-sans font-semibold bg-sky-500/15 hover:bg-sky-500/25 text-sky-800 border border-sky-500/30 transition-colors">
                                                Imprimir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="py-8 text-center font-sans text-slate-500">
                                    No se han emitido comprobantes de retención de ISLR en el período <?= e($periodo) ?>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </section>

</div>

<!-- MODAL EDITAR RETENCIÓN IVA -->
<div id="modalEditarRetIva" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Modificar Retención de IVA</h3>
            <button type="button" onclick="cerrarModalRet('modalEditarRetIva')" class="p-2 text-slate-500 hover:text-slate-900">✕</button>
        </div>
        <form id="formEditarRetIva" class="p-6 space-y-4">
            <input type="hidden" id="edit_iva_id" name="id">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">N° Comprobante (14 Dígitos)</label>
                <input type="text" id="edit_iva_num" name="numero_comprobante" required maxlength="14" pattern="\d{14}" class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Fecha de Emisión</label>
                    <input type="date" id="edit_iva_fecha" name="fecha_emision" required class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">% de Retención IVA</label>
                    <select id="edit_iva_porc" name="porcentaje_retencion" class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                        <option value="75">75%</option>
                        <option value="100">100%</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">RIF Proveedor</label>
                    <input type="text" id="edit_iva_rif" name="rif_proveedor" required class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono uppercase">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Razón Social</label>
                    <input type="text" id="edit_iva_nombre" name="nombre_proveedor" required class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Dirección del Proveedor</label>
                <input type="text" id="edit_iva_dir" name="direccion_proveedor" class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs">
            </div>
            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="cerrarModalRet('modalEditarRetIva')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-300 text-slate-700">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR RETENCIÓN ISLR -->
<div id="modalEditarRetIslr" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Modificar Retención de ISLR</h3>
            <button type="button" onclick="cerrarModalRet('modalEditarRetIslr')" class="p-2 text-slate-500 hover:text-slate-900">✕</button>
        </div>
        <form id="formEditarRetIslr" class="p-6 space-y-4">
            <input type="hidden" id="edit_islr_id" name="id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Fecha de Emisión</label>
                    <input type="date" id="edit_islr_fecha" name="fecha_emision" required class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">RIF Beneficiario</label>
                    <input type="text" id="edit_islr_rif" name="rif_beneficiario" required class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono uppercase">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nombre / Razón Social</label>
                    <input type="text" id="edit_islr_nombre" name="nombre_beneficiario" required class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Correo Electrónico (SNAT/2026/00093)</label>
                    <input type="email" id="edit_islr_email" name="email_beneficiario" required class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tasa BCV (Bs./USD)</label>
                    <input type="number" step="0.0001" min="0.01" id="edit_islr_tasa" name="tasa_bcv" required class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">N° Factura</label>
                    <input type="text" id="edit_islr_fac" name="numero_factura" class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">N° Control</label>
                    <input type="text" id="edit_islr_ctrl" name="numero_control" class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Monto Total Factura</label>
                    <input type="number" step="0.01" min="0" id="edit_islr_total" name="monto_total_factura" class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Base Imponible</label>
                    <input type="number" step="0.01" min="0.01" id="edit_islr_base" name="base_imponible" required class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">% Retención ISLR</label>
                    <input type="number" step="0.01" min="0" id="edit_islr_porc" name="porcentaje_islr" required class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tipo Persona</label>
                    <select id="edit_islr_tipo" name="tipo_persona" class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                        <option value="PJD">PJD</option>
                        <option value="PNR">PNR</option>
                        <option value="PJND">PJND</option>
                        <option value="PNNR">PNNR</option>
                    </select>
                </div>
            </div>
            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="cerrarModalRet('modalEditarRetIslr')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-300 text-slate-700">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const getMeta = (n) => document.querySelector(`meta[name="${n}"]`)?.getAttribute('content') || '';
    const BASE_URL = getMeta('base-url');
    const CSRF     = getMeta('csrf-token');

    window.cerrarModalRet = (id) => {
        const el = document.getElementById(id);
        el.classList.add('hidden');
        el.classList.remove('flex');
    };

    window.abrirEditarRetIva = (ri) => {
        document.getElementById('edit_iva_id').value = ri.id;
        document.getElementById('edit_iva_num').value = ri.numero_comprobante || '';
        document.getElementById('edit_iva_fecha').value = ri.fecha_emision || '';
        document.getElementById('edit_iva_porc').value = String(parseInt(ri.porcentaje_retencion || 75, 10));
        document.getElementById('edit_iva_rif').value = ri.rif_proveedor || '';
        document.getElementById('edit_iva_nombre').value = ri.nombre_proveedor || '';
        document.getElementById('edit_iva_dir').value = ri.direccion_proveedor || '';
        const m = document.getElementById('modalEditarRetIva');
        m.classList.remove('hidden'); m.classList.add('flex');
    };

    window.abrirEditarRetIslr = (r) => {
        document.getElementById('edit_islr_id').value = r.id;
        document.getElementById('edit_islr_fecha').value = r.fecha_emision || '';
        document.getElementById('edit_islr_rif').value = r.rif_beneficiario || '';
        document.getElementById('edit_islr_nombre').value = r.nombre_beneficiario || '';
        document.getElementById('edit_islr_email').value = r.email_beneficiario || '';
        document.getElementById('edit_islr_tasa').value = r.tasa_bcv || '1.0000';
        document.getElementById('edit_islr_fac').value = r.numero_factura || '';
        document.getElementById('edit_islr_ctrl').value = r.numero_control || '';
        document.getElementById('edit_islr_total').value = r.monto_total_factura || r.base_imponible || '0.00';
        document.getElementById('edit_islr_base').value = r.base_imponible || '0.00';
        document.getElementById('edit_islr_porc').value = r.porcentaje_islr || '5.00';
        document.getElementById('edit_islr_tipo').value = r.tipo_persona || 'PJD';
        const m = document.getElementById('modalEditarRetIslr');
        m.classList.remove('hidden'); m.classList.add('flex');
    };

    async function enviarEdicion(url, form) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify(Object.fromEntries(new FormData(form).entries()))
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || 'Error al actualizar.');
        return data;
    }

    document.getElementById('formEditarRetIva')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const res = await enviarEdicion(`${BASE_URL}/index.php?c=tributario&a=actualizarRetencionIva`, e.target);
            cerrarModalRet('modalEditarRetIva');
            await Swal.fire({ icon: 'success', title: 'Actualizado', text: res.message });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    });

    document.getElementById('formEditarRetIslr')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const res = await enviarEdicion(`${BASE_URL}/index.php?c=tributario&a=actualizarRetencionIslr`, e.target);
            cerrarModalRet('modalEditarRetIslr');
            await Swal.fire({ icon: 'success', title: 'Actualizado', text: res.message });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    });

    window.eliminarRetencion = async (tipo, id, numero) => {
        const conf = await Swal.fire({
            icon: 'warning',
            title: '¿Eliminar comprobante?',
            text: `Se eliminará el comprobante N° ${numero}. Esta acción no se puede deshacer.`,
            showCancelButton: true,
            confirmButtonColor: '#be123c',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });
        if (!conf.isConfirmed) return;

        const accion = tipo === 'iva' ? 'eliminarRetencionIva' : 'eliminarRetencionIslr';
        try {
            const res = await fetch(`${BASE_URL}/index.php?c=tributario&a=${accion}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'No se pudo eliminar.');
            await Swal.fire({ icon: 'success', title: 'Eliminado', text: data.message, timer: 1400, showConfirmButton: false });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    };
})();
</script>
