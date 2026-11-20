<?php
declare(strict_types=1);

$emp  = $empresaActiva ?? [];
$comp = $comprobante ?? [];
$facturas = $comp['facturas'] ?? [];

$partesPeriodo = explode('-', (string)($comp['periodo_fiscal'] ?? date('Y-m')));
$anioFiscal = $partesPeriodo[0] ?? date('Y');
$mesFiscal  = $partesPeriodo[1] ?? date('m');

$totFact  = 0.0;
$totExento= 0.0;
$totBase  = 0.0;
$totIva   = 0.0;
$totRet   = 0.0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Retención IVA N° <?= e($comp['numero_comprobante'] ?? '') ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @page { size: letter landscape; margin: 0mm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            html, body { background: #ffffff !important; padding: 5mm !important; margin: 0 !important; height: auto !important; }
            .hoja-comprobante {
                box-shadow: none !important;
                border: 1px solid #1e293b !important;
                border-radius: 0 !important;
                max-width: 100% !important;
                padding: 6px 10px !important;
                zoom: 0.78;
                page-break-inside: avoid !important;
            }
            .hoja-comprobante .membrete-oficial { padding-bottom: 4px !important; margin-bottom: 4px !important; }
            .hoja-comprobante .pie-reporte-oficial { margin-top: 4px !important; padding-top: 3px !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-6">

    <!-- Barra de acciones superior (No se imprime) -->
    <div class="max-w-6xl mx-auto mb-5 flex items-center justify-between bg-white text-slate-900 px-5 py-3 rounded-xl shadow no-print">
        <div class="flex items-center gap-3">
            <span class="font-mono text-xs px-2.5 py-1 rounded bg-amber-500/20 text-amber-800 border border-amber-500/30">
                SNAT/2015/0049 • SNAT/2025/000054
            </span>
            <span class="text-sm font-semibold">Comprobante N° <?= e($comp['numero_comprobante'] ?? '') ?></span>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="window.print()"
                    class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white">
                Imprimir / Guardar PDF
            </button>
            <button type="button" onclick="window.close()"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700">
                Cerrar Ventana
            </button>
        </div>
    </div>

    <!-- HOJA OFICIAL DEL COMPROBANTE DE RETENCIÓN DE IVA -->
    <div class="hoja-comprobante max-w-6xl mx-auto bg-white border border-slate-300 rounded-xl shadow-xl p-8 text-xs">
        <?php
        $tituloReporte = 'COMPROBANTE DE RETENCIÓN DE IVA';
        $subtituloPeriodo = 'Comprobante N° ' . (string)($comp['numero_comprobante'] ?? '') . ' • Período: ' . (string)($comp['periodo_fiscal'] ?? '');
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <!-- TÍTULO Y BASE LEGAL LEY DEL IVA ART. 11 -->
        <div class="text-center border-b-2 border-slate-200 pb-4 mb-5">
            <h1 class="text-base font-bold uppercase tracking-wide">
                COMPROBANTE DE RETENCIÓN DEL IMPUESTO AL VALOR AGREGADO
            </h1>
            <p class="text-[11px] text-slate-600 mt-1 max-w-3xl mx-auto leading-snug italic">
                Ley IVA - Art. 11: "La Administración Tributaria podrá designar como responsables del pago del impuesto,
                en calidad de agentes de retención, a quienes por sus funciones públicas o por razón de sus actividades
                privadas intervengan en operaciones gravadas con el impuesto establecido en esta Ley"
            </p>
        </div>

        <!-- RECUADROS DEL AGENTE DE RETENCIÓN Y NUMERACIÓN -->
        <div class="grid grid-cols-12 gap-3 mb-4">
            <div class="col-span-6 border border-slate-400 rounded p-2.5">
                <span class="block text-[10px] font-bold uppercase text-slate-500">
                    Nombre o Razón Social del Agente de Retención:
                </span>
                <p class="font-bold text-sm mt-0.5 uppercase"><?= e($emp['razon_social'] ?? '') ?></p>
            </div>

            <div class="col-span-3 border border-slate-400 rounded p-2.5">
                <span class="block text-[10px] font-bold uppercase text-slate-500">
                    Registro de Información Fiscal (RIF):
                </span>
                <p class="font-mono font-bold text-sm mt-0.5"><?= e($emp['rif'] ?? '') ?></p>
            </div>

            <div class="col-span-3 border-2 border-slate-200 rounded p-2.5 bg-slate-50">
                <span class="block text-[10px] font-bold uppercase text-slate-600">
                    Comprobante N° (14 Dígitos):
                </span>
                <p class="font-mono font-bold text-base text-slate-900 mt-0.5"><?= e($comp['numero_comprobante'] ?? '') ?></p>
            </div>

            <div class="col-span-9 border border-slate-400 rounded p-2.5">
                <span class="block text-[10px] font-bold uppercase text-slate-500">
                    Dirección Fiscal del Agente de Retención:
                </span>
                <p class="mt-0.5 uppercase"><?= e($emp['direccion_fiscal'] ?? '') ?></p>
            </div>

            <div class="col-span-3 border border-slate-400 rounded p-2.5">
                <span class="block text-[10px] font-bold uppercase text-slate-500">
                    Fecha de Emisión:
                </span>
                <p class="font-mono font-bold text-sm mt-0.5"><?= e(date('d/m/Y', strtotime((string)$comp['fecha_emision']))) ?></p>
            </div>
        </div>

        <!-- RECUADROS DEL SUJETO RETENIDO (PROVEEDOR) Y PERÍODO FISCAL -->
        <div class="grid grid-cols-12 gap-3 mb-5">
            <div class="col-span-6 border border-slate-400 rounded p-2.5">
                <span class="block text-[10px] font-bold uppercase text-slate-500">
                    Nombre o Razón Social del Sujeto Retenido:
                </span>
                <p class="font-bold text-sm mt-0.5 uppercase"><?= e($comp['nombre_proveedor'] ?? '') ?></p>
            </div>

            <div class="col-span-3 border border-slate-400 rounded p-2.5">
                <span class="block text-[10px] font-bold uppercase text-slate-500">
                    Registro de Información Fiscal (RIF) del Beneficiario:
                </span>
                <p class="font-mono font-bold text-sm mt-0.5"><?= e($comp['rif_proveedor'] ?? '') ?></p>
            </div>

            <div class="col-span-3 border border-slate-400 rounded p-2.5">
                <span class="block text-[10px] font-bold uppercase text-slate-500">
                    Período Fiscal:
                </span>
                <p class="font-mono font-bold text-sm mt-0.5">Año: <?= e($anioFiscal) ?> — Mes: <?= e($mesFiscal) ?></p>
            </div>

            <div class="col-span-12 border border-slate-400 rounded p-2.5">
                <span class="block text-[10px] font-bold uppercase text-slate-500">
                    Dirección Fiscal del Sujeto Retenido:
                </span>
                <p class="mt-0.5 uppercase"><?= e($comp['direccion_proveedor'] ?: 'DOMICILIO FISCAL REGISTRADO EN FACTURA') ?></p>
            </div>
        </div>

        <!-- TABLA REGLAMENTARIA DE DOCUMENTOS RETENIDOS -->
        <table class="w-full border-collapse border border-slate-200 text-[11px] mb-6">
            <thead class="bg-slate-100 uppercase text-[10px]">
                <tr>
                    <th class="border border-slate-400 py-2 px-1.5 text-center">N°</th>
                    <th class="border border-slate-400 py-2 px-2 text-center">Fecha de Docum.</th>
                    <th class="border border-slate-400 py-2 px-2 text-center">N° de Factura</th>
                    <th class="border border-slate-400 py-2 px-2 text-center">N° de Control</th>
                    <th class="border border-slate-400 py-2 px-2 text-center">N° Nota Crédito</th>
                    <th class="border border-slate-400 py-2 px-2 text-center">N° Nota Débito</th>
                    <th class="border border-slate-400 py-2 px-2 text-center">N° Factura Afectada</th>
                    <th class="border border-slate-400 py-2 px-1.5 text-center">Tipo Trans.</th>
                    <th class="border border-slate-400 py-2 px-2 text-right">Monto Total de Fact.</th>
                    <th class="border border-slate-400 py-2 px-2 text-right">Compras sin Derecho IVA</th>
                    <th class="border border-slate-400 py-2 px-2 text-right">Base Imponible</th>
                    <th class="border border-slate-400 py-2 px-1.5 text-center">% Alíc.</th>
                    <th class="border border-slate-400 py-2 px-2 text-right">Impuesto Causado</th>
                    <th class="border border-slate-400 py-2 px-1.5 text-center">% Ret.</th>
                    <th class="border border-slate-400 py-2 px-2 text-right">Monto Impuesto Retenido</th>
                </tr>
            </thead>
            <tbody class="font-mono">
                <?php if (!empty($facturas)): ?>
                    <?php foreach ($facturas as $i => $f): ?>
                        <?php
                        $baseF = (float)$f['base_imponible_general'] + (float)$f['base_imponible_reducida'] + (float)($f['base_imponible_adicional'] ?? 0);
                        $ivaF  = (float)$f['monto_iva_general'] + (float)$f['monto_iva_reducida'] + (float)($f['monto_iva_adicional'] ?? 0);
                        $retF  = round($ivaF * ((float)$comp['porcentaje_retencion'] / 100), 2);

                        $totFact   += (float)$f['total_compra_con_iva'];
                        $totExento += (float)$f['compras_exentas'];
                        $totBase   += $baseF;
                        $totIva    += $ivaF;
                        $totRet    += $retF;
                        ?>
                        <tr>
                            <td class="border border-slate-400 py-2 px-1.5 text-center"><?= $i + 1 ?></td>
                            <td class="border border-slate-400 py-2 px-2 text-center"><?= e(date('d/m/Y', strtotime((string)$f['fecha_factura']))) ?></td>
                            <td class="border border-slate-400 py-2 px-2 text-center font-bold"><?= e($f['numero_factura']) ?></td>
                            <td class="border border-slate-400 py-2 px-2 text-center"><?= e($f['numero_control']) ?></td>
                            <td class="border border-slate-400 py-2 px-2 text-center"><?= e($f['numero_nota_credito'] ?? '') ?></td>
                            <td class="border border-slate-400 py-2 px-2 text-center"><?= e($f['numero_nota_debito'] ?? '') ?></td>
                            <td class="border border-slate-400 py-2 px-2 text-center"><?= e($f['numero_factura_afectada'] ?? '') ?></td>
                            <td class="border border-slate-400 py-2 px-1.5 text-center"><?= e($f['tipo_transaccion'] ?? '01-Reg') ?></td>
                            <td class="border border-slate-400 py-2 px-2 text-right"><?= formato_ves($f['total_compra_con_iva'], false) ?></td>
                            <td class="border border-slate-400 py-2 px-2 text-right"><?= formato_ves($f['compras_exentas'], false) ?></td>
                            <td class="border border-slate-400 py-2 px-2 text-right"><?= formato_ves($baseF, false) ?></td>
                            <td class="border border-slate-400 py-2 px-1.5 text-center"><?= number_format((float)$f['alicuota_general'], 0) ?>%</td>
                            <td class="border border-slate-400 py-2 px-2 text-right"><?= formato_ves($ivaF, false) ?></td>
                            <td class="border border-slate-400 py-2 px-1.5 text-center"><?= number_format((float)$comp['porcentaje_retencion'], 0) ?>%</td>
                            <td class="border border-slate-400 py-2 px-2 text-right font-bold"><?= formato_ves($retF, false) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php
                    $totFact   = (float)($comp['total_facturas'] ?? 0);
                    $totExento = (float)($comp['total_exento'] ?? 0);
                    $totBase   = (float)($comp['total_base_imponible'] ?? 0);
                    $totIva    = (float)$comp['monto_iva_causado'];
                    $totRet    = (float)$comp['monto_iva_retenido'];
                    ?>
                    <tr>
                        <td class="border border-slate-400 py-2 px-1.5 text-center">1</td>
                        <td class="border border-slate-400 py-2 px-2 text-center"><?= e(date('d/m/Y', strtotime((string)$comp['fecha_emision']))) ?></td>
                        <td class="border border-slate-400 py-2 px-2 text-center" colspan="6">SEGÚN REGISTRO EN LIBRO DE COMPRAS</td>
                        <td class="border border-slate-400 py-2 px-2 text-right"><?= formato_ves($totFact, false) ?></td>
                        <td class="border border-slate-400 py-2 px-2 text-right"><?= formato_ves($totExento, false) ?></td>
                        <td class="border border-slate-400 py-2 px-2 text-right"><?= formato_ves($totBase, false) ?></td>
                        <td class="border border-slate-400 py-2 px-1.5 text-center">16%</td>
                        <td class="border border-slate-400 py-2 px-2 text-right"><?= formato_ves($totIva, false) ?></td>
                        <td class="border border-slate-400 py-2 px-1.5 text-center"><?= number_format((float)$comp['porcentaje_retencion'], 0) ?>%</td>
                        <td class="border border-slate-400 py-2 px-2 text-right font-bold"><?= formato_ves($totRet, false) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="bg-slate-100 font-mono font-bold">
                <tr>
                    <td colspan="8" class="border border-slate-400 py-2.5 px-3 text-right uppercase">TOTALES:</td>
                    <td class="border border-slate-400 py-2.5 px-2 text-right"><?= formato_ves($totFact, false) ?></td>
                    <td class="border border-slate-400 py-2.5 px-2 text-right"><?= formato_ves($totExento, false) ?></td>
                    <td class="border border-slate-400 py-2.5 px-2 text-right"><?= formato_ves($totBase, false) ?></td>
                    <td class="border border-slate-400 py-2.5 px-1.5"></td>
                    <td class="border border-slate-400 py-2.5 px-2 text-right"><?= formato_ves($totIva, false) ?></td>
                    <td class="border border-slate-400 py-2.5 px-1.5"></td>
                    <td class="border border-slate-400 py-2.5 px-2 text-right text-sm"><?= formato_ves($totRet) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- RECUADROS DE FIRMAS Y CONSTANCIA DE ENTREGA -->
        <div class="grid grid-cols-3 gap-8 pt-6 mt-3 text-center">
            <div class="border-t border-slate-700 pt-1.5">
                <p class="font-bold uppercase text-[11px]">RECIBÍ CONFORME (SUJETO RETENIDO)</p>
                <p class="text-[10px] text-slate-500"><?= e($comp['nombre_proveedor'] ?? '') ?></p>
            </div>

            <div class="border-t border-slate-700 pt-1.5">
                <p class="font-bold uppercase text-[11px]">FECHA DE ENTREGA: ____ / ____ / ________</p>
                <p class="text-[10px] text-slate-500">Constancia de recepción</p>
            </div>

            <div class="border-t border-slate-700 pt-1.5">
                <p class="font-bold uppercase text-[11px]">FIRMA Y SELLO DEL AGENTE DE RETENCIÓN</p>
                <p class="text-[10px] text-slate-500"><?= e($emp['razon_social'] ?? '') ?></p>
            </div>
        </div>

        <!-- PIE NORMATIVO SENIAT -->
        <p class="mt-3 text-center text-[10px] text-slate-500 border-t border-slate-200 pt-2">
            Este comprobante se emite según lo establecido en la Providencia Administrativa N° SNAT/2015/0049 y Artículo N° 16 de la Providencia Administrativa N° SNAT/2025/000054, publicada en Gaceta Oficial N° 43.171.
        </p>
        <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
    </div>

</body>
</html>
