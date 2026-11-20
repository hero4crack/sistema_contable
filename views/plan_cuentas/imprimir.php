<?php
declare(strict_types=1);

$emp = $empresa ?? ($empresaActiva ?? []);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan de Cuentas — <?= e((string)$emp['razon_social']) ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @page { size: letter portrait; margin: 0mm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            html, body { background: #ffffff !important; margin: 0 !important; padding: 7mm !important; }
            .hoja-plan { box-shadow: none !important; border: none !important; max-width: 100% !important; padding: 0 !important; }
        }
        body { font-family: Arial, Helvetica, sans-serif; background: #e2e8f0; color: #0f172a; }
        .tabla-plan { width: 100%; border-collapse: collapse; }
        .tabla-plan th, .tabla-plan td { border: 1px solid #cbd5e1; padding: 3px 6px; font-size: 9.5px; }
    </style>
</head>
<body class="p-4">

    <div class="max-w-5xl mx-auto mb-4 flex items-center justify-between bg-white border border-slate-300 px-5 py-3 shadow-sm no-print">
        <span class="text-sm font-bold text-slate-900">Catálogo Oficial del Plan de Cuentas — <?= e((string)$emp['razon_social']) ?></span>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-4 py-1.5 text-xs font-bold bg-emerald-700 text-white">Imprimir / PDF</button>
            <button type="button" onclick="window.close()" class="px-3 py-1.5 text-xs font-semibold bg-slate-200 text-slate-800">Cerrar</button>
        </div>
    </div>

    <div class="hoja-plan max-w-5xl mx-auto bg-white p-6 shadow-lg border border-slate-300 space-y-4">
        <?php
        $tituloReporte    = 'CATÁLOGO GENERAL DE CUENTAS CONTABLES';
        $subtituloPeriodo = 'Total de Cuentas: ' . count($cuentas ?? []);
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <table class="tabla-plan">
            <thead class="bg-slate-200 uppercase font-bold">
                <tr>
                    <th style="width:110px;">Código</th>
                    <th>Descripción de la Cuenta</th>
                    <th style="width:85px;">Rubro</th>
                    <th style="width:85px;">Naturaleza</th>
                    <th style="width:50px;" class="text-center">Nivel</th>
                    <th style="width:80px;" class="text-center">Tipo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($cuentas ?? []) as $c): ?>
                    <?php
                    $esMov = (int)$c['acepta_movimiento'] === 1;
                    $nivel = (int)($c['nivel'] ?? 1);
                    ?>
                    <tr class="<?= !$esMov ? 'bg-slate-100 font-bold' : '' ?>">
                        <td class="font-mono"><?= e((string)$c['codigo']) ?></td>
                        <td style="padding-left: <?= 6 + (($nivel - 1) * 12) ?>px;"><?= e((string)$c['nombre']) ?></td>
                        <td class="font-mono"><?= e((string)$c['tipo']) ?></td>
                        <td class="font-mono"><?= e((string)$c['naturaleza']) ?></td>
                        <td class="text-center font-mono">N<?= $nivel ?></td>
                        <td class="text-center"><?= $esMov ? 'Imputable' : 'Grupo' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
    </div>

</body>
</html>
