<?php
declare(strict_types=1);

$emp      = $empresa ?? ($empresaActiva ?? []);
$lote     = $loteSeleccionado ?? [];
$detalles = $lote['detalles'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Relación Cestaticket <?= e((string)($lote['codigo_lote'] ?? '')) ?> — <?= e((string)$emp['razon_social']) ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @page { size: letter landscape; margin: 0mm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            html, body { background: #ffffff !important; margin: 0 !important; padding: 7mm !important; }
            .hoja-cesta { box-shadow: none !important; border: none !important; max-width: 100% !important; padding: 0 !important; }
        }
        body { font-family: Arial, Helvetica, sans-serif; background: #e2e8f0; color: #0f172a; }
        .tabla-cesta { width: 100%; border-collapse: collapse; }
        .tabla-cesta th, .tabla-cesta td { border: 1px solid #334155; padding: 4px 6px; font-size: 9.5px; }
    </style>
</head>
<body class="p-4">

    <div class="max-w-full mx-auto mb-4 flex items-center justify-between bg-white border border-slate-300 px-5 py-3 shadow-sm no-print">
        <span class="text-sm font-bold text-slate-900">
            Relación Oficial de Bono de Alimentación — <?= e((string)($lote['codigo_lote'] ?? '')) ?>
        </span>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-4 py-1.5 text-xs font-bold bg-emerald-700 text-white">Imprimir / PDF</button>
            <button type="button" onclick="window.close()" class="px-3 py-1.5 text-xs font-semibold bg-slate-200 text-slate-800">Cerrar</button>
        </div>
    </div>

    <div class="hoja-cesta bg-white p-6 shadow-lg border border-slate-300 space-y-4">
        <?php
        $tituloReporte    = 'RELACIÓN DE BONO DE ALIMENTACIÓN (CESTATICKET) — ' . (string)($lote['codigo_lote'] ?? '');
        $subtituloPeriodo = 'Período: ' . (string)($lote['periodo'] ?? '') . ' • Emisión: ' . date('d/m/Y', strtotime((string)($lote['fecha_emision'] ?? date('Y-m-d'))));
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <table class="tabla-cesta">
            <thead class="bg-slate-200 uppercase font-bold">
                <tr>
                    <th class="text-center">N°</th>
                    <th>Cédula / Trabajador</th>
                    <th class="text-center">Días Hábiles</th>
                    <th class="text-center">Sáb/Dom</th>
                    <th class="text-center">Feriados</th>
                    <th class="text-center">Inasist.</th>
                    <th class="text-center">Días Efectivos</th>
                    <th class="text-right">Valor Diario</th>
                    <th class="text-right">Total a Pagar</th>
                    <th class="text-center" style="width:120px;">Firma Recibido</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $idx => $det): ?>
                    <tr>
                        <td class="text-center font-mono"><?= $idx + 1 ?></td>
                        <td>
                            <div class="font-bold uppercase"><?= e((string)$det['nombre_completo']) ?></div>
                            <div class="font-mono text-[8.5px] text-slate-600"><?= e((string)$det['cedula']) ?> • <?= e((string)$det['cargo']) ?></div>
                        </td>
                        <td class="text-center font-mono"><?= (float)$det['dias_habiles'] ?></td>
                        <td class="text-center font-mono"><?= (float)$det['dias_descanso'] ?></td>
                        <td class="text-center font-mono"><?= (float)$det['dias_feriados'] ?></td>
                        <td class="text-center font-mono"><?= (float)$det['dias_inasistencia'] ?></td>
                        <td class="text-center font-mono font-bold"><?= (float)$det['dias_efectivos'] ?></td>
                        <td class="text-right font-mono"><?= number_format((float)$det['valor_diario'], 2, ',', '.') ?></td>
                        <td class="text-right font-mono font-bold"><?= formato_ves((float)$det['total_cestaticket']) ?></td>
                        <td style="height:24px;"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-slate-100 font-mono font-bold">
                <tr>
                    <td colspan="8" class="text-right uppercase font-sans">Total Bono de Alimentación del Lote:</td>
                    <td class="text-right"><?= formato_ves((float)($lote['total_pagar'] ?? 0)) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="grid grid-cols-2 gap-16 pt-10 text-center text-[10px]">
            <div class="border-t border-slate-700 pt-1.5">
                <p class="font-bold uppercase">ELABORADO POR (NÓMINA / RRHH)</p>
                <p class="text-slate-500">Firma y Sello</p>
            </div>
            <div class="border-t border-slate-700 pt-1.5">
                <p class="font-bold uppercase">APROBADO POR (GERENCIA / ADMINISTRACIÓN)</p>
                <p class="text-slate-500"><?= e((string)$emp['razon_social']) ?></p>
            </div>
        </div>

        <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
    </div>

</body>
</html>
