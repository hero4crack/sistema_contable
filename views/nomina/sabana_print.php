<?php
declare(strict_types=1);

$emp      = $empresa ?? ($empresaActiva ?? []);
$lote     = $loteSeleccionado ?? [];
$detalles = $lote['detalles'] ?? [];
$detId    = (int)($detalleId ?? 0);

if ($detId > 0) {
    $detalles = array_values(array_filter($detalles, static fn(array $d): bool => (int)$d['id'] === $detId));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sábana de Nómina <?= e((string)($lote['codigo_nomina'] ?? '')) ?> — <?= e((string)$emp['razon_social']) ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @page { size: letter landscape; margin: 0mm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            html, body { background: #ffffff !important; margin: 0 !important; padding: 7mm !important; }
            .hoja-sabana { box-shadow: none !important; border: none !important; max-width: 100% !important; padding: 0 !important; }
        }
        body { font-family: Arial, Helvetica, sans-serif; background: #e2e8f0; color: #0f172a; }
        .tabla-sabana { width: 100%; border-collapse: collapse; }
        .tabla-sabana th, .tabla-sabana td { border: 1px solid #334155; padding: 4px 5px; font-size: 9px; }
    </style>
</head>
<body class="p-4">

    <div class="max-w-full mx-auto mb-4 flex items-center justify-between bg-white border border-slate-300 px-5 py-3 shadow-sm no-print">
        <span class="text-sm font-bold text-slate-900">
            Impresión Oficial de Sábana de Nómina — <?= e((string)($lote['codigo_nomina'] ?? '')) ?>
        </span>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-4 py-1.5 text-xs font-bold bg-emerald-700 text-white">Imprimir / PDF</button>
            <button type="button" onclick="window.close()" class="px-3 py-1.5 text-xs font-semibold bg-slate-200 text-slate-800">Cerrar</button>
        </div>
    </div>

    <div class="hoja-sabana bg-white p-6 shadow-lg border border-slate-300 space-y-4">
        <?php
        $tituloReporte    = $detId > 0 ? 'COMPROBANTE INDIVIDUAL DE NÓMINA — ' . (string)($lote['codigo_nomina'] ?? '') : 'SÁBANA GENERAL DE NÓMINA — ' . (string)($lote['codigo_nomina'] ?? '');
        $subtituloPeriodo = 'Período: ' . date('d/m/Y', strtotime((string)$lote['fecha_desde'])) . ' al ' . date('d/m/Y', strtotime((string)$lote['fecha_hasta'])) . ' • ' . (string)($lote['tipo_frecuencia'] ?? '');
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <table class="tabla-sabana">
            <thead class="bg-slate-200 uppercase font-bold">
                <tr>
                    <th class="text-center">N°</th>
                    <th>Cédula / Trabajador</th>
                    <th class="text-right">Sueldo Mensual</th>
                    <th class="text-center">Días (H/D/F)</th>
                    <th class="text-right">Sueldo Período</th>
                    <th class="text-right">H.Ext / Bonos</th>
                    <th class="text-right">IVSS (4%)</th>
                    <th class="text-right">RPE (0.5%)</th>
                    <th class="text-right">FAOV (1%)</th>
                    <th class="text-right">Total Desc.</th>
                    <th class="text-right">Neto a Cancelar</th>
                    <th class="text-center" style="width:110px;">Firma Recibido</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $idx => $det): ?>
                    <tr>
                        <td class="text-center font-mono"><?= $idx + 1 ?></td>
                        <td>
                            <div class="font-bold uppercase"><?= e((string)$det['nombre_completo']) ?></div>
                            <div class="font-mono text-[8px] text-slate-600"><?= e((string)$det['cedula']) ?> • <?= e((string)$det['cargo']) ?></div>
                        </td>
                        <td class="text-right font-mono"><?= number_format((float)$det['salario_mensual'], 2, ',', '.') ?></td>
                        <td class="text-center font-mono"><?= (float)$det['dias_habiles'] ?> / <?= (float)$det['dias_descanso'] ?> / <?= (float)$det['dias_feriados'] ?></td>
                        <td class="text-right font-mono"><?= number_format((float)$det['sueldo_basico_periodo'], 2, ',', '.') ?></td>
                        <td class="text-right font-mono"><?= number_format((float)$det['horas_extras'] + (float)$det['bonificaciones'], 2, ',', '.') ?></td>
                        <td class="text-right font-mono"><?= number_format((float)$det['retencion_sso'], 2, ',', '.') ?></td>
                        <td class="text-right font-mono"><?= number_format((float)$det['retencion_rpe'], 2, ',', '.') ?></td>
                        <td class="text-right font-mono"><?= number_format((float)$det['retencion_faov'], 2, ',', '.') ?></td>
                        <td class="text-right font-mono"><?= number_format((float)$det['total_deducciones'], 2, ',', '.') ?></td>
                        <td class="text-right font-mono font-bold"><?= formato_ves((float)$det['neto_pagar']) ?></td>
                        <td style="height:26px;"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-slate-100 font-mono font-bold">
                <tr>
                    <td colspan="4" class="text-right uppercase font-sans">Totales del Lote:</td>
                    <td colspan="2" class="text-right"><?= formato_ves((float)$lote['total_asignaciones']) ?></td>
                    <td colspan="4" class="text-right"><?= formato_ves((float)$lote['total_deducciones']) ?></td>
                    <td class="text-right"><?= formato_ves((float)$lote['total_neto_pagar']) ?></td>
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
