<?php
declare(strict_types=1);

$emp     = $empresa ?? ($empresaActiva ?? []);
$tab     = $tab ?? 'compras';
$compras = $libroCompras ?? [];
$ventas  = $libroVentas ?? [];

$totC = [
    'total_con_iva' => 0.0, 'exentas' => 0.0,
    'base_gen' => 0.0, 'iva_gen' => 0.0,
    'base_otras' => 0.0, 'iva_otras' => 0.0,
    'monto_igtf' => 0.0, 'iva_retenido' => 0.0,
];
foreach ($compras as $cRow) {
    if (($cRow['estado'] ?? 'ACTIVO') === 'ACTIVO') {
        $totC['total_con_iva'] += (float)$cRow['total_compra_con_iva'];
        $totC['exentas']       += (float)$cRow['compras_exentas'];
        $totC['base_gen']      += (float)$cRow['base_imponible_general'];
        $totC['iva_gen']       += (float)$cRow['monto_iva_general'];
        $totC['base_otras']    += (float)$cRow['base_imponible_reducida'] + (float)($cRow['base_imponible_adicional'] ?? 0);
        $totC['iva_otras']     += (float)$cRow['monto_iva_reducida'] + (float)($cRow['monto_iva_adicional'] ?? 0);
        $totC['monto_igtf']    += (float)($cRow['monto_igtf'] ?? 0);
        $totC['iva_retenido']  += (float)($cRow['monto_iva_retenido'] ?? 0);
    }
}

$totV = [
    'total_con_iva' => 0.0, 'exentas' => 0.0,
    'base_gen' => 0.0, 'iva_gen' => 0.0,
    'monto_igtf' => 0.0, 'iva_retenido_clientes' => 0.0,
];
foreach ($ventas as $vRow) {
    if (($vRow['estado'] ?? 'ACTIVO') === 'ACTIVO') {
        $totV['total_con_iva']         += (float)$vRow['total_venta_con_iva'];
        $totV['exentas']               += (float)$vRow['ventas_exentas'];
        $totV['base_gen']              += (float)$vRow['base_imponible_general'];
        $totV['iva_gen']               += (float)$vRow['monto_iva_general'];
        $totV['monto_igtf']            += (float)($vRow['monto_igtf'] ?? 0);
        $totV['iva_retenido_clientes'] += (float)($vRow['iva_retenido_por_cliente'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libros Fiscales SENIAT <?= e($periodo) ?> — <?= e((string)$emp['razon_social']) ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @page { size: legal landscape; margin: 0mm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            html, body { background: #ffffff !important; padding: 6mm !important; margin: 0 !important; }
            .hoja-libro { box-shadow: none !important; border: none !important; max-width: 100% !important; padding: 0 !important; }
        }
        body { font-family: Arial, Helvetica, sans-serif; background: #e2e8f0; color: #0f172a; }
        .tabla-seniat { width: 100%; border-collapse: collapse; }
        .tabla-seniat th, .tabla-seniat td { border: 1px solid #334155; padding: 3px 4px; font-size: 8.5px; }
    </style>
</head>
<body class="p-4">

    <div class="max-w-full mx-auto mb-4 flex items-center justify-between bg-white border border-slate-300 px-5 py-3 shadow-sm no-print">
        <span class="text-sm font-bold text-slate-900">
            Impresión Oficial SENIAT (Oficio Horizontal) — Período <?= e($periodo) ?>
        </span>
        <div class="flex items-center gap-2">
            <a href="<?= e(route_url('tributario', 'librosPrint', ['tab' => 'compras', 'periodo' => $periodo])) ?>"
               class="px-3 py-1.5 text-xs font-semibold border <?= $tab === 'compras' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-300' ?>">
                Libro de Compras (<?= count($compras) ?>)
            </a>
            <a href="<?= e(route_url('tributario', 'librosPrint', ['tab' => 'ventas', 'periodo' => $periodo])) ?>"
               class="px-3 py-1.5 text-xs font-semibold border <?= $tab === 'ventas' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-300' ?>">
                Libro de Ventas (<?= count($ventas) ?>)
            </a>
            <button type="button" onclick="window.print()" class="px-4 py-1.5 text-xs font-bold bg-emerald-700 text-white">Imprimir / PDF</button>
            <button type="button" onclick="window.close()" class="px-3 py-1.5 text-xs font-semibold bg-slate-200 text-slate-800">Cerrar</button>
        </div>
    </div>

    <div class="hoja-libro bg-white p-5 shadow-lg border border-slate-300 space-y-4">
        <?php
        $tituloReporte    = $tab === 'ventas' ? 'LIBRO AUXILIAR DE VENTAS (SENIAT)' : 'LIBRO AUXILIAR DE COMPRAS NACIONALES (SENIAT)';
        $subtituloPeriodo = 'Período Fiscal: ' . $periodo . ' • Expresado en Bolívares (Bs.)';
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <?php if ($tab === 'ventas'): ?>
            <table class="tabla-seniat">
                <thead class="bg-slate-200 uppercase font-bold">
                    <tr>
                        <th class="text-center">N°</th>
                        <th>Fecha</th>
                        <th>RIF Cliente</th>
                        <th>Nombre / Razón Social</th>
                        <th>Condición</th>
                        <th>N° Factura</th>
                        <th>N° Control</th>
                        <th>Tipo</th>
                        <th class="text-right">Total c/IVA</th>
                        <th class="text-right">Exentas</th>
                        <th class="text-right">Base 16%</th>
                        <th class="text-right">IVA 16%</th>
                        <th class="text-right">IGTF 3%</th>
                        <th class="text-right">IVA Ret. Cliente</th>
                        <th>N° Comp. Cliente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ventas)): ?>
                        <?php foreach ($ventas as $idx => $v): ?>
                            <tr>
                                <td class="text-center font-mono"><?= $idx + 1 ?></td>
                                <td class="font-mono"><?= e($v['fecha_factura']) ?></td>
                                <td class="font-mono"><?= e($v['rif_cliente']) ?></td>
                                <td><?= e($v['nombre_cliente']) ?></td>
                                <td><?= ($v['tipo_contribuyente_cliente'] ?? 'Ordinario') === 'Especial' ? 'Especial' : 'No Especial' ?></td>
                                <td class="font-mono"><?= e($v['numero_factura']) ?></td>
                                <td class="font-mono"><?= e($v['numero_control']) ?></td>
                                <td class="font-mono"><?= e($v['tipo_transaccion'] ?? '01-Reg') ?></td>
                                <td class="text-right font-mono font-bold"><?= formato_ves($v['total_venta_con_iva'], false) ?></td>
                                <td class="text-right font-mono"><?= formato_ves($v['ventas_exentas'], false) ?></td>
                                <td class="text-right font-mono"><?= formato_ves($v['base_imponible_general'], false) ?></td>
                                <td class="text-right font-mono"><?= formato_ves($v['monto_iva_general'], false) ?></td>
                                <td class="text-right font-mono"><?= (float)($v['monto_igtf'] ?? 0) != 0.0 ? formato_ves($v['monto_igtf'], false) : '—' ?></td>
                                <td class="text-right font-mono"><?= formato_ves($v['iva_retenido_por_cliente'] ?? 0, false) ?></td>
                                <td class="font-mono"><?= e(($v['numero_comprobante_ret_cliente'] ?? '') ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="15" class="py-4 text-center">No hay ventas registradas en el período <?= e($periodo) ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-slate-100 font-mono font-bold">
                    <tr>
                        <td colspan="8" class="text-right uppercase font-sans">Totales Libro de Ventas (Bs.):</td>
                        <td class="text-right"><?= formato_ves($totV['total_con_iva'], false) ?></td>
                        <td class="text-right"><?= formato_ves($totV['exentas'], false) ?></td>
                        <td class="text-right"><?= formato_ves($totV['base_gen'], false) ?></td>
                        <td class="text-right"><?= formato_ves($totV['iva_gen'], false) ?></td>
                        <td class="text-right"><?= formato_ves($totV['monto_igtf'], false) ?></td>
                        <td class="text-right"><?= formato_ves($totV['iva_retenido_clientes'], false) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        <?php else: ?>
            <table class="tabla-seniat">
                <thead class="bg-slate-200 uppercase font-bold">
                    <tr>
                        <th class="text-center">N°</th>
                        <th>Fecha</th>
                        <th>RIF Prov.</th>
                        <th>Nombre / Razón Social</th>
                        <th>N° Factura</th>
                        <th>N° Control</th>
                        <th>N° N/C</th>
                        <th>N° N/D</th>
                        <th>Tipo</th>
                        <th class="text-right">Total c/IVA</th>
                        <th class="text-right">Exentas</th>
                        <th class="text-right">Base 16%</th>
                        <th class="text-right">IVA 16%</th>
                        <th class="text-right">IGTF 3%</th>
                        <th class="text-right">IVA Ret.</th>
                        <th>Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($compras)): ?>
                        <?php foreach ($compras as $idx => $c): ?>
                            <tr>
                                <td class="text-center font-mono"><?= $idx + 1 ?></td>
                                <td class="font-mono"><?= e($c['fecha_factura']) ?></td>
                                <td class="font-mono"><?= e($c['rif_proveedor']) ?></td>
                                <td><?= e($c['nombre_proveedor']) ?></td>
                                <td class="font-mono"><?= e($c['numero_factura']) ?></td>
                                <td class="font-mono"><?= e($c['numero_control']) ?></td>
                                <td class="font-mono"><?= e($c['numero_nota_credito'] ?: '—') ?></td>
                                <td class="font-mono"><?= e($c['numero_nota_debito'] ?: '—') ?></td>
                                <td class="font-mono"><?= e($c['tipo_transaccion'] ?? '01-Reg') ?></td>
                                <td class="text-right font-mono font-bold"><?= formato_ves($c['total_compra_con_iva'], false) ?></td>
                                <td class="text-right font-mono"><?= formato_ves($c['compras_exentas'], false) ?></td>
                                <td class="text-right font-mono"><?= formato_ves($c['base_imponible_general'], false) ?></td>
                                <td class="text-right font-mono"><?= formato_ves($c['monto_iva_general'], false) ?></td>
                                <td class="text-right font-mono"><?= (float)($c['monto_igtf'] ?? 0) != 0.0 ? formato_ves($c['monto_igtf'], false) : '—' ?></td>
                                <td class="text-right font-mono"><?= formato_ves($c['monto_iva_retenido'] ?? 0, false) ?></td>
                                <td class="font-mono"><?= e($c['comprobante_iva_numero'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="16" class="py-4 text-center">No hay compras registradas en el período <?= e($periodo) ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-slate-100 font-mono font-bold">
                    <tr>
                        <td colspan="9" class="text-right uppercase font-sans">Totales Libro de Compras (Bs.):</td>
                        <td class="text-right"><?= formato_ves($totC['total_con_iva'], false) ?></td>
                        <td class="text-right"><?= formato_ves($totC['exentas'], false) ?></td>
                        <td class="text-right"><?= formato_ves($totC['base_gen'], false) ?></td>
                        <td class="text-right"><?= formato_ves($totC['iva_gen'], false) ?></td>
                        <td class="text-right"><?= formato_ves($totC['monto_igtf'], false) ?></td>
                        <td class="text-right"><?= formato_ves($totC['iva_retenido'], false) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>

        <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
    </div>

</body>
</html>
