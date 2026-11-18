<?php
declare(strict_types=1);

$emp  = $empresaActiva ?? [];
$comp = $comprobante ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Retención ISLR N° <?= e($comp['numero_comprobante'] ?? '') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { size: landscape; margin: 12mm; }
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; padding: 0 !important; }
            .hoja-comprobante { box-shadow: none !important; border: none !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-6">

    <!-- Barra de acciones superior -->
    <div class="max-w-5xl mx-auto mb-5 flex items-center justify-between bg-slate-900 text-white px-5 py-3 rounded-xl shadow no-print">
        <div class="flex items-center gap-3">
            <span class="font-mono text-xs px-2.5 py-1 rounded bg-sky-500/20 text-sky-300 border border-sky-500/30">
                DECRETO 1.808 • ART. 24
            </span>
            <span class="text-sm font-semibold">Comprobante ISLR N° <?= e($comp['numero_comprobante'] ?? '') ?></span>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="window.print()"
                    class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white">
                Imprimir / Guardar PDF
            </button>
            <button type="button" onclick="window.close()"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300">
                Cerrar Ventana
            </button>
        </div>
    </div>

    <!-- HOJA OFICIAL DEL COMPROBANTE DE RETENCIÓN DE ISLR -->
    <div class="hoja-comprobante max-w-5xl mx-auto bg-white border border-slate-300 rounded-xl shadow-xl p-8 text-xs">

        <!-- TÍTULO Y BASE LEGAL DECRETO 1.808 -->
        <div class="text-center border-b-2 border-slate-800 pb-4 mb-5">
            <h1 class="text-base font-bold uppercase tracking-wide">
                COMPROBANTE DE RETENCIÓN DE IMPUESTO SOBRE LA RENTA (I.S.L.R.)
            </h1>
            <p class="text-[11px] text-slate-600 mt-1">
                (Para dar cumplimiento a la normativa establecida en el Artículo 24 del Decreto 1.808 en materia de
                retenciones de ISLR, publicado en Gaceta Oficial N° 36.203 de fecha 12 de mayo de 1997)
            </p>
        </div>

        <div class="flex justify-between items-center mb-4 font-mono text-xs">
            <div>
                <span class="text-slate-500 uppercase font-bold">Comprobante N°:</span>
                <strong class="text-sm ml-1"><?= e($comp['numero_comprobante'] ?? '') ?></strong>
            </div>
            <div>
                <span class="text-slate-500 uppercase font-bold">Fecha de Emisión:</span>
                <strong class="text-sm ml-1"><?= e(date('d/m/Y', strtotime((string)$comp['fecha_emision']))) ?></strong>
            </div>
        </div>

        <!-- DATOS DEL AGENTE DE RETENCIÓN -->
        <div class="border border-slate-400 rounded-lg p-3.5 mb-4">
            <h2 class="text-[11px] font-bold uppercase text-slate-600 border-b border-slate-200 pb-1.5 mb-2">
                DATOS DEL AGENTE DE RETENCIÓN:
            </h2>
            <div class="grid grid-cols-12 gap-2">
                <div class="col-span-8">
                    <span class="text-slate-500 font-semibold">EMPRESA / RAZÓN SOCIAL:</span>
                    <strong class="ml-1 uppercase"><?= e($emp['razon_social'] ?? '') ?></strong>
                </div>
                <div class="col-span-4 text-right font-mono">
                    <span class="text-slate-500 font-semibold">R.I.F.:</span>
                    <strong class="ml-1"><?= e($emp['rif'] ?? '') ?></strong>
                </div>
                <div class="col-span-12">
                    <span class="text-slate-500 font-semibold">DIRECCIÓN FISCAL:</span>
                    <span class="ml-1 uppercase"><?= e($emp['direccion_fiscal'] ?? '') ?></span>
                </div>
            </div>
        </div>

        <!-- DATOS DEL SUJETO PASIVO (BENEFICIARIO) -->
        <div class="border border-slate-400 rounded-lg p-3.5 mb-4">
            <h2 class="text-[11px] font-bold uppercase text-slate-600 border-b border-slate-200 pb-1.5 mb-2">
                DATOS DEL SUJETO PASIVO (BENEFICIARIO RETENIDO):
            </h2>
            <div class="grid grid-cols-12 gap-2">
                <div class="col-span-8">
                    <span class="text-slate-500 font-semibold">NOMBRE Y APELLIDO / RAZÓN SOCIAL:</span>
                    <strong class="ml-1 uppercase"><?= e($comp['nombre_beneficiario'] ?? '') ?></strong>
                </div>
                <div class="col-span-4 text-right font-mono">
                    <span class="text-slate-500 font-semibold">R.I.F.:</span>
                    <strong class="ml-1"><?= e($comp['rif_beneficiario'] ?? '') ?></strong>
                </div>
                <div class="col-span-12">
                    <span class="text-slate-500 font-semibold">DIRECCIÓN FISCAL:</span>
                    <span class="ml-1 uppercase"><?= e($comp['direccion_beneficiario'] ?: 'DOMICILIO FISCAL REGISTRADO EN FACTURA') ?></span>
                </div>
            </div>
        </div>

        <!-- CONCEPTO Y CÓDIGO DE RETENCIÓN -->
        <div class="grid grid-cols-12 gap-3 mb-5 bg-slate-50 border border-slate-300 rounded-lg p-3">
            <div class="col-span-6">
                <span class="block text-[10px] font-bold uppercase text-slate-500">Tipo / Concepto de Retención:</span>
                <p class="font-bold mt-0.5"><?= e($comp['descripcion_concepto'] ?? '') ?></p>
            </div>
            <div class="col-span-3">
                <span class="block text-[10px] font-bold uppercase text-slate-500">Código de Retención:</span>
                <p class="font-mono font-bold mt-0.5"><?= e($comp['codigo_concepto'] ?? '') ?> (<?= e($comp['tipo_persona'] ?? 'PJD') ?>)</p>
            </div>
            <div class="col-span-3 text-right">
                <span class="block text-[10px] font-bold uppercase text-slate-500">Porcentaje de Retención:</span>
                <p class="font-mono font-bold text-sm mt-0.5"><?= number_format((float)($comp['porcentaje_islr'] ?? 0), 2, ',', '.') ?>%</p>
            </div>
        </div>

        <!-- TABLA DE CÁLCULO DE RETENCIÓN ISLR -->
        <table class="w-full border-collapse border border-slate-800 text-xs mb-6">
            <thead class="bg-slate-100 uppercase text-[10px]">
                <tr>
                    <th class="border border-slate-400 py-2.5 px-3 text-center">Fecha de Pago o Abono en Cuenta</th>
                    <th class="border border-slate-400 py-2.5 px-3 text-center">Número de Factura</th>
                    <th class="border border-slate-400 py-2.5 px-3 text-center">N° Control</th>
                    <th class="border border-slate-400 py-2.5 px-3 text-right">Monto Total</th>
                    <th class="border border-slate-400 py-2.5 px-3 text-right">Monto Exento</th>
                    <th class="border border-slate-400 py-2.5 px-3 text-right">Monto Sujeto de Retención (Base)</th>
                    <th class="border border-slate-400 py-2.5 px-2 text-center">Pct. Retención</th>
                    <th class="border border-slate-400 py-2.5 px-3 text-right">Sustraendo</th>
                    <th class="border border-slate-400 py-2.5 px-3 text-right">Monto Retenido</th>
                </tr>
            </thead>
            <tbody class="font-mono">
                <tr>
                    <td class="border border-slate-400 py-3 px-3 text-center">
                        <?= e(date('d/m/Y', strtotime((string)$comp['fecha_emision']))) ?>
                    </td>
                    <td class="border border-slate-400 py-3 px-3 text-center font-bold">
                        <?= e($comp['numero_factura'] ?: 'S/N') ?>
                    </td>
                    <td class="border border-slate-400 py-3 px-3 text-center">
                        <?= e($comp['numero_control'] ?: 'S/C') ?>
                    </td>
                    <td class="border border-slate-400 py-3 px-3 text-right">
                        <?= formato_ves($comp['monto_total_factura'] ?? $comp['base_imponible'], false) ?>
                    </td>
                    <td class="border border-slate-400 py-3 px-3 text-right">
                        <?= formato_ves($comp['monto_exento'] ?? 0, false) ?>
                    </td>
                    <td class="border border-slate-400 py-3 px-3 text-right font-bold">
                        <?= formato_ves($comp['base_imponible'], false) ?>
                    </td>
                    <td class="border border-slate-400 py-3 px-2 text-center">
                        <?= number_format((float)$comp['porcentaje_islr'], 2, ',', '.') ?>%
                    </td>
                    <td class="border border-slate-400 py-3 px-3 text-right">
                        <?= formato_ves($comp['sustraendo'], false) ?>
                    </td>
                    <td class="border border-slate-400 py-3 px-3 text-right font-bold text-sm">
                        <?= formato_ves($comp['monto_retenido'], false) ?>
                    </td>
                </tr>
            </tbody>
            <tfoot class="bg-slate-100 font-mono font-bold">
                <tr>
                    <td colspan="8" class="border border-slate-400 py-2.5 px-3 text-right uppercase">
                        TOTAL IMPUESTO SOBRE LA RENTA (I.S.L.R.) RETENIDO:
                    </td>
                    <td class="border border-slate-400 py-2.5 px-3 text-right text-sm">
                        <?= formato_ves($comp['monto_retenido']) ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- FIRMAS Y SELLOS -->
        <div class="grid grid-cols-2 gap-12 pt-12 mt-6 text-center">
            <div class="border-t border-slate-700 pt-2">
                <p class="font-bold uppercase text-xs">ELABORADO POR (AGENTE DE RETENCIÓN):</p>
                <p class="text-[11px] text-slate-600 mt-0.5"><?= e($emp['razon_social'] ?? '') ?> — RIF: <?= e($emp['rif'] ?? '') ?></p>
            </div>

            <div class="border-t border-slate-700 pt-2">
                <p class="font-bold uppercase text-xs">RECIBIDO CONFORME POR (SUJETO PASIVO):</p>
                <p class="text-[11px] text-slate-600 mt-0.5"><?= e($comp['nombre_beneficiario'] ?? '') ?> — RIF: <?= e($comp['rif_beneficiario'] ?? '') ?></p>
            </div>
        </div>

    </div>

</body>
</html>
