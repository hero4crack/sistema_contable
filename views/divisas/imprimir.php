<?php
declare(strict_types=1);

$emp      = $empresa ?? ($empresaActiva ?? []);
$movs     = $relacion['movimientos'] ?? [];
$tot      = $relacion['totales'] ?? [];
$anterior = $relacion['saldo_anterior'] ?? ['saldo_usd' => 0.0, 'saldo_bs' => 0.0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Relación en Divisas <?= e($periodo) ?> — <?= e((string)$emp['razon_social']) ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @page { size: landscape; margin: 12mm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            body { background: #ffffff !important; padding: 0 !important; }
            .hoja-divisas { box-shadow: none !important; border: none !important; max-width: 100% !important; margin: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-6">

    <div class="max-w-6xl mx-auto mb-5 flex items-center justify-between bg-white border border-slate-200 px-5 py-3 rounded-xl shadow-sm no-print">
        <span class="text-sm font-bold text-slate-900">
            Relación en Divisas — <?= $tipoCaja === 'CAJA' ? 'Caja Efectivo USD' : 'Banco USD' ?> (<?= e($periodo) ?>)
        </span>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">
                Imprimir / PDF
            </button>
            <button type="button" onclick="window.close()" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300">
                Cerrar
            </button>
        </div>
    </div>

    <div class="hoja-divisas max-w-6xl mx-auto bg-white border border-slate-300 rounded-xl shadow-lg p-8 text-xs space-y-5">
        <?php
        $tituloReporte    = $tipoCaja === 'CAJA' ? 'RELACIÓN EN DIVISAS — CAJA EFECTIVO' : 'RELACIÓN EN DIVISAS — BANCO';
        $subtituloPeriodo = 'Período Fiscal: ' . $periodo . ' • Moneda Extranjera (USD) / Tasa BCV';
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <table class="w-full border-collapse border border-slate-300 text-[11px]">
            <thead class="bg-slate-100 text-slate-800 uppercase">
                <tr>
                    <th rowspan="2" class="border border-slate-300 py-2 px-2">Fecha Operación</th>
                    <th colspan="3" class="border border-slate-300 py-1.5 px-2 text-center bg-emerald-50">Moneda Extranjera (USD)</th>
                    <th rowspan="2" class="border border-slate-300 py-2 px-2 text-right">Tasa BCV</th>
                    <th colspan="3" class="border border-slate-300 py-1.5 px-2 text-center bg-slate-100">Moneda Extranjera (Expresión en Bolívares)</th>
                    <th colspan="2" class="border border-slate-300 py-1.5 px-2 text-center bg-emerald-50">Diferencia en Cambio</th>
                    <th rowspan="2" class="border border-slate-300 py-2 px-2">Descripción</th>
                </tr>
                <tr>
                    <th class="border border-slate-300 py-1 px-2 text-right">Debe</th>
                    <th class="border border-slate-300 py-1 px-2 text-right">Haber</th>
                    <th class="border border-slate-300 py-1 px-2 text-right">Saldo</th>
                    <th class="border border-slate-300 py-1 px-2 text-right">Debe</th>
                    <th class="border border-slate-300 py-1 px-2 text-right">Haber</th>
                    <th class="border border-slate-300 py-1 px-2 text-right">Saldo</th>
                    <th class="border border-slate-300 py-1 px-2 text-right">Realizada</th>
                    <th class="border border-slate-300 py-1 px-2 text-right">No Realizada</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-slate-50 font-semibold">
                    <td class="border border-slate-300 py-1.5 px-2 font-mono"><?= e($periodo . '-01') ?></td>
                    <td class="border border-slate-300 py-1.5 px-2 text-right font-mono">0,00</td>
                    <td class="border border-slate-300 py-1.5 px-2 text-right font-mono">0,00</td>
                    <td class="border border-slate-300 py-1.5 px-2 text-right font-mono"><?= number_format((float)$anterior['saldo_usd'], 2, ',', '.') ?></td>
                    <td class="border border-slate-300 py-1.5 px-2 text-right font-mono">—</td>
                    <td class="border border-slate-300 py-1.5 px-2 text-right font-mono">0,00</td>
                    <td class="border border-slate-300 py-1.5 px-2 text-right font-mono">0,00</td>
                    <td class="border border-slate-300 py-1.5 px-2 text-right font-mono"><?= number_format((float)$anterior['saldo_bs'], 2, ',', '.') ?></td>
                    <td class="border border-slate-300 py-1.5 px-2 text-right font-mono">0,00</td>
                    <td class="border border-slate-300 py-1.5 px-2 text-right font-mono">0,00</td>
                    <td class="border border-slate-300 py-1.5 px-2">SALDO INICIAL</td>
                </tr>
                <?php foreach ($movs as $m): ?>
                    <tr class="<?= (int)$m['es_ajuste_cambiario'] === 1 ? 'bg-emerald-50 font-semibold' : '' ?>">
                        <td class="border border-slate-300 py-1.5 px-2 font-mono"><?= e($m['fecha_operacion']) ?></td>
                        <td class="border border-slate-300 py-1.5 px-2 text-right font-mono"><?= $m['debe_usd'] > 0 ? number_format($m['debe_usd'], 2, ',', '.') : '' ?></td>
                        <td class="border border-slate-300 py-1.5 px-2 text-right font-mono"><?= $m['haber_usd'] > 0 ? number_format($m['haber_usd'], 2, ',', '.') : '' ?></td>
                        <td class="border border-slate-300 py-1.5 px-2 text-right font-mono font-bold"><?= number_format($m['saldo_usd'], 2, ',', '.') ?></td>
                        <td class="border border-slate-300 py-1.5 px-2 text-right font-mono"><?= number_format($m['tasa_bcv'], 4, ',', '.') ?></td>
                        <td class="border border-slate-300 py-1.5 px-2 text-right font-mono"><?= $m['debe_bs'] > 0 ? number_format($m['debe_bs'], 2, ',', '.') : '' ?></td>
                        <td class="border border-slate-300 py-1.5 px-2 text-right font-mono"><?= $m['haber_bs'] > 0 ? number_format($m['haber_bs'], 2, ',', '.') : '' ?></td>
                        <td class="border border-slate-300 py-1.5 px-2 text-right font-mono font-bold"><?= number_format($m['saldo_bs'], 2, ',', '.') ?></td>
                        <td class="border border-slate-300 py-1.5 px-2 text-right font-mono"><?= $m['diferencial_realizado'] != 0 ? number_format($m['diferencial_realizado'], 2, ',', '.') : '' ?></td>
                        <td class="border border-slate-300 py-1.5 px-2 text-right font-mono"><?= $m['diferencial_no_realizado'] != 0 ? number_format($m['diferencial_no_realizado'], 2, ',', '.') : '' ?></td>
                        <td class="border border-slate-300 py-1.5 px-2"><?= e($m['descripcion']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="bg-slate-100 font-mono font-bold">
                    <td class="border border-slate-300 py-2 px-2 font-sans uppercase">Totales / Saldo al cierre (Exigible)</td>
                    <td class="border border-slate-300 py-2 px-2 text-right"><?= number_format((float)($tot['debe_usd'] ?? 0), 2, ',', '.') ?></td>
                    <td class="border border-slate-300 py-2 px-2 text-right"><?= number_format((float)($tot['haber_usd'] ?? 0), 2, ',', '.') ?></td>
                    <td class="border border-slate-300 py-2 px-2 text-right text-emerald-800">$ <?= number_format((float)($tot['saldo_final_usd'] ?? 0), 2, ',', '.') ?></td>
                    <td class="border border-slate-300 py-2 px-2"></td>
                    <td class="border border-slate-300 py-2 px-2 text-right"><?= number_format((float)($tot['debe_bs'] ?? 0), 2, ',', '.') ?></td>
                    <td class="border border-slate-300 py-2 px-2 text-right"><?= number_format((float)($tot['haber_bs'] ?? 0), 2, ',', '.') ?></td>
                    <td class="border border-slate-300 py-2 px-2 text-right text-emerald-800">Bs. <?= number_format((float)($tot['saldo_final_bs'] ?? 0), 2, ',', '.') ?></td>
                    <td class="border border-slate-300 py-2 px-2 text-right"><?= number_format((float)($tot['diferencial_realizado'] ?? 0), 2, ',', '.') ?></td>
                    <td class="border border-slate-300 py-2 px-2 text-right"><?= number_format((float)($tot['diferencial_no_realizado'] ?? 0), 2, ',', '.') ?></td>
                    <td class="border border-slate-300 py-2 px-2 font-sans">Saldo actualizado al cierre</td>
                </tr>
            </tfoot>
        </table>

        <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
    </div>

</body>
</html>
