<?php
declare(strict_types=1);

$emp       = $empresa ?? ($empresaActiva ?? []);
$tabActual = $tab ?? 'matriz';

$etiquetas = [
    'VIGENTE'       => 'VIGENTE',
    'POR_VERIFICAR' => 'POR VERIFICAR',
    'PRONTO_VENCER' => 'PRONTO A VENCER',
    'VENCIDO'       => 'VENCIDO',
    'NO_APLICA'     => 'NO APLICA',
    'SIN_SOPORTE'   => 'SIN SOPORTE',
];
$colores = [
    'VIGENTE'       => 'background:#dcfce7;color:#166534;',
    'POR_VERIFICAR' => 'background:#fef3c7;color:#92400e;',
    'PRONTO_VENCER' => 'background:#ffedd5;color:#9a3412;',
    'VENCIDO'       => 'background:#fee2e2;color:#991b1b;font-weight:700;',
    'NO_APLICA'     => 'background:#f1f5f9;color:#475569;',
    'SIN_SOPORTE'   => 'background:#f1f5f9;color:#475569;',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Deberes Formales — Juris Contable</title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @page { size: legal landscape; margin: 0mm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            html, body { background: #ffffff !important; margin: 0 !important; padding: 6mm !important; }
            .hoja-reporte { box-shadow: none !important; border: none !important; max-width: 100% !important; padding: 0 !important; }
        }
        body { font-family: Arial, Helvetica, sans-serif; background: #e2e8f0; color: #0f172a; }
        .tabla-formal { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .tabla-formal th, .tabla-formal td { border: 1px solid #334155; padding: 3px 4px; word-wrap: break-word; }
    </style>
</head>
<body class="p-4">

    <div class="max-w-full mx-auto mb-4 flex items-center justify-between bg-white border border-slate-300 px-5 py-3 shadow-sm no-print">
        <div>
            <span class="text-xs font-bold uppercase text-emerald-800">Vista de Impresión Oficial (Oficio Horizontal)</span>
            <p class="text-xs text-slate-600">Control de Deberes Formales — <?= $tabActual === 'matriz' ? 'Matriz Global de Clientes Activos' : e((string)$emp['razon_social']) ?></p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-4 py-1.5 text-xs font-bold bg-emerald-700 text-white">Imprimir / PDF</button>
            <button type="button" onclick="window.close()" class="px-3 py-1.5 text-xs font-semibold bg-slate-200 text-slate-800">Cerrar</button>
        </div>
    </div>

    <div class="hoja-reporte bg-white p-5 shadow-lg">
        <?php
        $tituloReporte    = $tabActual === 'matriz' ? 'CONTROL DE CLIENTES ACTIVOS — DEBERES FORMALES' : 'EXPEDIENTE DE DEBERES FORMALES';
        $subtituloPeriodo = 'Última actualización: ' . date('d/m/Y H:i');
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <!-- Leyenda -->
        <div class="flex flex-wrap items-center gap-4 text-[10px] mb-3 border border-slate-300 px-3 py-1.5 bg-slate-50">
            <span class="font-bold uppercase">Leyenda:</span>
            <span class="px-2 py-0.5 border border-slate-400" style="background:#dcfce7;color:#166534;">VIGENTE / RENOVADO</span>
            <span class="px-2 py-0.5 border border-slate-400" style="background:#ffedd5;color:#9a3412;">PRONTO A VENCER (≤ 30 días)</span>
            <span class="px-2 py-0.5 border border-slate-400" style="background:#fee2e2;color:#991b1b;">VENCIDO</span>
            <span class="px-2 py-0.5 border border-slate-400" style="background:#fef3c7;color:#92400e;">POR VERIFICAR</span>
            <span class="px-2 py-0.5 border border-slate-400" style="background:#f1f5f9;color:#475569;">NO APLICA / SIN SOPORTE</span>
        </div>

        <?php if ($tabActual === 'matriz'): ?>
            <table class="tabla-formal text-[8px] leading-tight">
                <thead>
                    <tr class="bg-slate-200 uppercase text-center font-bold">
                        <th style="width:24px;">N°</th>
                        <th style="width:125px;" class="text-left">Cliente / RIF</th>
                        <?php foreach ($obligacionesBase as $cod => $info): ?>
                            <th>
                                <div><?= e($info['nombre']) ?></div>
                                <div class="font-normal text-[7px] text-slate-600"><?= e($info['periodicidad']) ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matrizGlobal as $idx => $fila): ?>
                        <tr>
                            <td class="text-center font-mono"><?= $idx + 1 ?></td>
                            <td>
                                <div class="font-bold uppercase"><?= e($fila['empresa']['razon_social']) ?></div>
                                <div class="font-mono text-[7.5px]"><?= e($fila['empresa']['rif']) ?></div>
                            </td>
                            <?php foreach ($obligacionesBase as $cod => $info): ?>
                                <?php
                                $item  = $fila['obligaciones'][$cod] ?? null;
                                $est   = $item['estatus_efectivo'] ?? 'POR_VERIFICAR';
                                $style = $colores[$est] ?? $colores['POR_VERIFICAR'];
                                $fv    = !empty($item['fecha_vencimiento']) ? date('d/m/Y', strtotime((string)$item['fecha_vencimiento'])) : '';
                                $txt   = $fv !== '' ? ('VENCE ' . $fv) : ($etiquetas[$est] ?? $est);
                                ?>
                                <td class="text-center font-mono" style="<?= e($style) ?>">
                                    <?= e($txt) ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table class="tabla-formal text-[10px]">
                <thead>
                    <tr class="bg-slate-200 uppercase font-bold">
                        <th style="width:28px;" class="text-center">N°</th>
                        <th style="width:220px;">Deber Formal / Obligación</th>
                        <th style="width:100px;" class="text-center">Periodicidad</th>
                        <th style="width:110px;" class="text-center">Estatus</th>
                        <th style="width:85px;" class="text-center">Renovación</th>
                        <th style="width:85px;" class="text-center">Vencimiento</th>
                        <th>Observación / Acción Requerida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($obligaciones as $idx => $ob): ?>
                        <?php
                        $est   = $ob['estatus_efectivo'] ?? 'POR_VERIFICAR';
                        $style = $colores[$est] ?? $colores['POR_VERIFICAR'];
                        ?>
                        <tr>
                            <td class="text-center font-mono"><?= $idx + 1 ?></td>
                            <td class="font-bold"><?= e($ob['nombre_obligacion']) ?></td>
                            <td class="text-center font-mono"><?= e($ob['periodicidad']) ?></td>
                            <td class="text-center font-bold" style="<?= e($style) ?>"><?= e($etiquetas[$est] ?? $est) ?></td>
                            <td class="text-center font-mono"><?= !empty($ob['fecha_renovacion']) ? e(date('d/m/Y', strtotime((string)$ob['fecha_renovacion']))) : '—' ?></td>
                            <td class="text-center font-mono font-bold"><?= !empty($ob['fecha_vencimiento']) ? e(date('d/m/Y', strtotime((string)$ob['fecha_vencimiento']))) : '—' ?></td>
                            <td><?= e($ob['observacion'] ?: ($ob['accion_requerida'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
    </div>

</body>
</html>
