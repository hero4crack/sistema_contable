<?php
declare(strict_types=1);

$emp = $empresa ?? ($empresaActiva ?? []);
$tab = $tabActiva ?? 'comprobacion';
$bc  = $estados['balance_comprobacion'] ?? ['filas' => [], 'totales' => []];
$er  = $estados['estado_resultados'] ?? ['ingresos' => [], 'costos' => [], 'gastos' => []];
$bg  = $estados['balance_general'] ?? ['activos' => [], 'pasivos' => [], 'patrimonio' => []];

$titulos = [
    'comprobacion' => 'BALANCE DE COMPROBACIÓN DE SUMAS Y SALDOS',
    'resultados'   => 'ESTADO DE RESULTADOS (GANANCIAS Y PÉRDIDAS)',
    'general'      => 'ESTADO DE SITUACIÓN FINANCIERA (BALANCE GENERAL)',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= e($titulos[$tab] ?? 'Estados Financieros') ?> — <?= e((string)$emp['razon_social']) ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @page { size: <?= $tab === 'comprobacion' ? 'letter landscape' : 'letter portrait' ?>; margin: 0mm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            html, body { background: #ffffff !important; padding: 8mm !important; margin: 0 !important; }
            .hoja-estado { box-shadow: none !important; border: none !important; max-width: 100% !important; padding: 0 !important; }
        }
        body { font-family: Arial, Helvetica, sans-serif; background: #e2e8f0; color: #0f172a; }
        .tabla-contable { width: 100%; border-collapse: collapse; }
        .tabla-contable th, .tabla-contable td { border: 1px solid #334155; padding: 4px 6px; font-size: 10px; }
        .doble-raya { border-bottom: 3px double #0f172a !important; }
    </style>
</head>
<body class="p-5">

    <div class="max-w-5xl mx-auto mb-4 flex items-center justify-between bg-white border border-slate-300 px-5 py-3 shadow-sm no-print">
        <div>
            <span class="text-[11px] font-bold uppercase text-emerald-800 block">Reporte Contable Oficial</span>
            <span class="text-sm font-bold text-slate-900"><?= e($titulos[$tab] ?? 'Estados Financieros') ?></span>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= e(route_url('contabilidad', 'estadosPrint', ['tab' => 'comprobacion', 'fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta])) ?>"
               class="px-3 py-1.5 text-xs font-semibold border <?= $tab === 'comprobacion' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-300' ?>">
                Comprobación
            </a>
            <a href="<?= e(route_url('contabilidad', 'estadosPrint', ['tab' => 'resultados', 'fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta])) ?>"
               class="px-3 py-1.5 text-xs font-semibold border <?= $tab === 'resultados' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-300' ?>">
                Estado de Resultados
            </a>
            <a href="<?= e(route_url('contabilidad', 'estadosPrint', ['tab' => 'general', 'fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta])) ?>"
               class="px-3 py-1.5 text-xs font-semibold border <?= $tab === 'general' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-300' ?>">
                Balance General
            </a>
            <button type="button" onclick="window.print()" class="px-4 py-1.5 text-xs font-bold bg-emerald-700 text-white">Imprimir / PDF</button>
            <button type="button" onclick="window.close()" class="px-3 py-1.5 text-xs font-semibold bg-slate-200 text-slate-800">Cerrar</button>
        </div>
    </div>

    <div class="hoja-estado max-w-5xl mx-auto bg-white p-7 shadow-lg border border-slate-300 space-y-5">
        <?php
        $tituloReporte    = $titulos[$tab] ?? 'ESTADOS FINANCIEROS';
        $subtituloPeriodo = 'Desde el ' . date('d/m/Y', strtotime($fechaDesde)) . ' hasta el ' . date('d/m/Y', strtotime($fechaHasta)) . ' • Expresado en Bolívares (Bs.)';
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <?php if ($tab === 'comprobacion'): ?>
            <table class="tabla-contable">
                <thead class="bg-slate-200 uppercase text-[9px] font-bold">
                    <tr>
                        <th rowspan="2" class="text-left">Código</th>
                        <th rowspan="2" class="text-left">Cuenta Contable</th>
                        <th colspan="2" class="text-center">Saldo Anterior</th>
                        <th colspan="2" class="text-center">Movimientos del Período</th>
                        <th colspan="2" class="text-center">Saldo Final</th>
                    </tr>
                    <tr>
                        <th class="text-right">Deudor</th>
                        <th class="text-right">Acreedor</th>
                        <th class="text-right">Debe</th>
                        <th class="text-right">Haber</th>
                        <th class="text-right">Deudor</th>
                        <th class="text-right">Acreedor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bc['filas'] as $f): ?>
                        <tr>
                            <td class="font-mono"><?= e($f['codigo']) ?></td>
                            <td><?= e($f['nombre']) ?></td>
                            <td class="text-right font-mono"><?= formato_ves($f['saldo_anterior_deudor'] ?? 0, false) ?></td>
                            <td class="text-right font-mono"><?= formato_ves($f['saldo_anterior_acreedor'] ?? 0, false) ?></td>
                            <td class="text-right font-mono"><?= formato_ves($f['debe_periodo'] ?? 0, false) ?></td>
                            <td class="text-right font-mono"><?= formato_ves($f['haber_periodo'] ?? 0, false) ?></td>
                            <td class="text-right font-mono font-bold"><?= formato_ves($f['saldo_final_deudor'] ?? 0, false) ?></td>
                            <td class="text-right font-mono font-bold"><?= formato_ves($f['saldo_final_acreedor'] ?? 0, false) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-100 font-mono font-bold">
                    <tr>
                        <td colspan="2" class="text-right uppercase font-sans">Sumas Iguales (Bs.):</td>
                        <td class="text-right"><?= formato_ves($bc['totales']['ant_deudor'] ?? 0, false) ?></td>
                        <td class="text-right"><?= formato_ves($bc['totales']['ant_acreedor'] ?? 0, false) ?></td>
                        <td class="text-right"><?= formato_ves($bc['totales']['debe_periodo'] ?? 0, false) ?></td>
                        <td class="text-right"><?= formato_ves($bc['totales']['haber_periodo'] ?? 0, false) ?></td>
                        <td class="text-right doble-raya"><?= formato_ves($bc['totales']['fin_deudor'] ?? 0, false) ?></td>
                        <td class="text-right doble-raya"><?= formato_ves($bc['totales']['fin_acreedor'] ?? 0, false) ?></td>
                    </tr>
                </tfoot>
            </table>

        <?php elseif ($tab === 'resultados'): ?>
            <table class="tabla-contable">
                <thead class="bg-slate-200 uppercase font-bold">
                    <tr>
                        <th class="text-left" style="width:110px;">Código</th>
                        <th class="text-left">Concepto / Partida</th>
                        <th class="text-right" style="width:140px;">Parcial (Bs.)</th>
                        <th class="text-right" style="width:140px;">Total (Bs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-slate-100 font-bold"><td colspan="4">INGRESOS OPERACIONALES</td></tr>
                    <?php foreach ($er['ingresos'] as $item): ?>
                        <tr>
                            <td class="font-mono"><?= e($item['codigo']) ?></td>
                            <td><?= e($item['nombre']) ?></td>
                            <td class="text-right font-mono"><?= formato_ves($item['monto'], false) ?></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="font-bold bg-slate-50">
                        <td colspan="3" class="text-right uppercase">Total Ingresos:</td>
                        <td class="text-right font-mono"><?= formato_ves($er['total_ingresos'] ?? 0, false) ?></td>
                    </tr>

                    <tr class="bg-slate-100 font-bold"><td colspan="4">COSTO DE VENTAS</td></tr>
                    <?php foreach ($er['costos'] as $item): ?>
                        <tr>
                            <td class="font-mono"><?= e($item['codigo']) ?></td>
                            <td><?= e($item['nombre']) ?></td>
                            <td class="text-right font-mono"><?= formato_ves($item['monto'], false) ?></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="font-bold bg-slate-50">
                        <td colspan="3" class="text-right uppercase">Total Costo de Ventas:</td>
                        <td class="text-right font-mono"><?= formato_ves($er['total_costos'] ?? 0, false) ?></td>
                    </tr>
                    <tr class="font-bold bg-emerald-50">
                        <td colspan="3" class="text-right uppercase">Utilidad Bruta en Ventas:</td>
                        <td class="text-right font-mono"><?= formato_ves($er['utilidad_bruta'] ?? 0, false) ?></td>
                    </tr>

                    <tr class="bg-slate-100 font-bold"><td colspan="4">GASTOS GENERALES Y DE ADMINISTRACIÓN</td></tr>
                    <?php foreach ($er['gastos'] as $item): ?>
                        <tr>
                            <td class="font-mono"><?= e($item['codigo']) ?></td>
                            <td><?= e($item['nombre']) ?></td>
                            <td class="text-right font-mono"><?= formato_ves($item['monto'], false) ?></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="font-bold bg-slate-50">
                        <td colspan="3" class="text-right uppercase">Total Gastos Operativos:</td>
                        <td class="text-right font-mono"><?= formato_ves($er['total_gastos'] ?? 0, false) ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-slate-200 font-bold text-xs">
                        <td colspan="3" class="text-right uppercase">Resultado Neto del Ejercicio (Antes de ISLR):</td>
                        <td class="text-right font-mono doble-raya"><?= formato_ves($er['resultado_neto'] ?? 0) ?></td>
                    </tr>
                </tfoot>
            </table>

        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <table class="tabla-contable">
                    <thead><tr class="bg-slate-200 font-bold uppercase"><th colspan="3">ACTIVOS</th></tr></thead>
                    <tbody>
                        <?php foreach ($bg['activos'] as $a): ?>
                            <tr>
                                <td class="font-mono" style="width:90px;"><?= e($a['codigo']) ?></td>
                                <td><?= e($a['nombre']) ?></td>
                                <td class="text-right font-mono" style="width:110px;"><?= formato_ves($a['monto'], false) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold">
                            <td colspan="2" class="text-right uppercase">Total Activos:</td>
                            <td class="text-right font-mono doble-raya"><?= formato_ves($bg['total_activos'] ?? 0) ?></td>
                        </tr>
                    </tfoot>
                </table>

                <table class="tabla-contable">
                    <thead><tr class="bg-slate-200 font-bold uppercase"><th colspan="3">PASIVO Y PATRIMONIO</th></tr></thead>
                    <tbody>
                        <tr class="bg-slate-50 font-bold"><td colspan="3">PASIVOS</td></tr>
                        <?php foreach ($bg['pasivos'] as $p): ?>
                            <tr>
                                <td class="font-mono" style="width:90px;"><?= e($p['codigo']) ?></td>
                                <td><?= e($p['nombre']) ?></td>
                                <td class="text-right font-mono" style="width:110px;"><?= formato_ves($p['monto'], false) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="font-bold bg-slate-50">
                            <td colspan="2" class="text-right uppercase">Total Pasivos:</td>
                            <td class="text-right font-mono"><?= formato_ves($bg['total_pasivos'] ?? 0, false) ?></td>
                        </tr>

                        <tr class="bg-slate-50 font-bold"><td colspan="3">PATRIMONIO</td></tr>
                        <?php foreach ($bg['patrimonio'] as $pt): ?>
                            <tr>
                                <td class="font-mono"><?= e($pt['codigo']) ?></td>
                                <td><?= e($pt['nombre']) ?></td>
                                <td class="text-right font-mono"><?= formato_ves($pt['monto'], false) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td class="font-mono">—</td>
                            <td>Resultado del Ejercicio</td>
                            <td class="text-right font-mono"><?= formato_ves($bg['resultado_ejercicio'] ?? 0, false) ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold">
                            <td colspan="2" class="text-right uppercase">Total Pasivo + Patrimonio:</td>
                            <td class="text-right font-mono doble-raya"><?= formato_ves($bg['total_pasivo_patrimonio'] ?? 0) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>

        <!-- Firmas -->
        <div class="grid grid-cols-2 gap-16 pt-10 text-center text-[10px]">
            <div class="border-t border-slate-700 pt-1.5">
                <p class="font-bold uppercase">CONTADOR PÚBLICO COLEGIADO</p>
                <p class="text-slate-500">Elaborado / Certificado</p>
            </div>
            <div class="border-t border-slate-700 pt-1.5">
                <p class="font-bold uppercase">REPRESENTANTE LEGAL</p>
                <p class="text-slate-500"><?= e((string)$emp['razon_social']) ?></p>
            </div>
        </div>

        <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
    </div>

</body>
</html>
