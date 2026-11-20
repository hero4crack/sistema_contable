<?php
declare(strict_types=1);

$emp       = $empresa ?? ($empresaActiva ?? []);
$simbolo   = ($cuenta['moneda'] ?? 'VES') === 'USD' ? '$ ' : 'Bs. ';
$formato   = $formato ?? 'estandar';
$esExcel   = $formato === 'excel';

$depTrans  = (float)($conciliacion['depositos_en_transito'] ?? 0);
$cheqTrans = (float)($conciliacion['cheques_en_transito'] ?? 0);
$ndNoReg   = (float)($conciliacion['notas_debito_no_registradas'] ?? 0);
$ncNoReg   = (float)($conciliacion['notas_credito_no_registradas'] ?? 0);

$bancoConc  = round((float)$saldoBanco + $depTrans - $cheqTrans, 2);
$librosConc = round((float)$saldoLibros + $ncNoReg - $ndNoReg, 2);
$diferencia = round($bancoConc - $librosConc, 2);
$transito   = round($depTrans - $cheqTrans, 2);

$pendientesBanco = array_values(array_filter($movimientosBanco ?? [], static fn(array $m): bool => (int)$m['conciliado'] === 0));
$movsCronologicos = array_reverse($movimientosBanco ?? []);
$fechaCierreMes   = date('d/m/Y', strtotime(date('Y-m-t', strtotime($periodo . '-01'))));
$fechaCierreAnt   = date('d/m/Y', strtotime($periodo . '-01 -1 day'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conciliación Bancaria <?= e($periodo) ?> — <?= e((string)$emp['razon_social']) ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @page { size: letter portrait; margin: 0mm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            html, body { background: #ffffff !important; color: #000000 !important; padding: 8mm !important; margin: 0 !important; }
            .hoja-conciliacion { box-shadow: none !important; border: none !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        }
        body { font-family: Arial, Helvetica, sans-serif; background: #e2e8f0; color: #0f172a; }
        .tabla-excel { width: 100%; border-collapse: collapse; }
        .tabla-excel th, .tabla-excel td { border: 1px solid #334155; padding: 3px 5px; font-size: 9.5px; }
    </style>
</head>
<body class="p-6">

    <!-- Barra de acciones y selector de formato (no se imprime) -->
    <div class="max-w-5xl mx-auto mb-5 flex flex-wrap items-center justify-between gap-3 bg-white border border-slate-300 px-5 py-3 shadow-sm no-print">
        <div>
            <span class="text-[11px] font-mono font-bold uppercase text-emerald-700 block">Reporte Oficial de Conciliación Bancaria</span>
            <span class="text-sm font-bold text-slate-900">
                <?= e($cuenta['banco_nombre']) ?> (•••• <?= e(substr((string)$cuenta['numero_cuenta'], -4)) ?>) — Período <?= e($periodo) ?>
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= e(route_url('bancos', 'conciliacionPrint', ['cuenta_id' => $cuenta['id'], 'periodo' => $periodo, 'formato' => 'estandar'])) ?>"
               class="px-3 py-1.5 text-xs font-semibold border <?= !$esExcel ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-300' ?>">
                Formato Contable Estándar
            </a>
            <a href="<?= e(route_url('bancos', 'conciliacionPrint', ['cuenta_id' => $cuenta['id'], 'periodo' => $periodo, 'formato' => 'excel'])) ?>"
               class="px-3 py-1.5 text-xs font-semibold border <?= $esExcel ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-300' ?>">
                Formato Auxiliar (Estilo Excel)
            </a>
            <button type="button" onclick="window.print()"
                    class="px-4 py-1.5 text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white">
                Imprimir / PDF
            </button>
            <button type="button" onclick="window.close()"
                    class="px-3 py-1.5 text-xs font-semibold bg-slate-200 hover:bg-slate-300 text-slate-800">
                Cerrar
            </button>
        </div>
    </div>

    <div class="hoja-conciliacion max-w-5xl mx-auto bg-white border border-slate-300 shadow-lg p-7 text-xs space-y-5">
        <?php
        $tituloReporte    = $esExcel ? ('CONCILIACIÓN BANCARIA AL ' . $fechaCierreMes) : 'CONCILIACIÓN BANCARIA MENSUAL';
        $subtituloPeriodo = 'Banco: ' . (string)$cuenta['banco_nombre'] . ' • Cuenta: ' . (string)$cuenta['numero_cuenta'];
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <?php if ($esExcel): ?>
            <!-- ================================================================= -->
            <!-- FORMATO 2: ESTILO EXCEL (TABLA CORRIDA CON SALDO ACUMULADO)       -->
            <!-- ================================================================= -->
            <table class="tabla-excel">
                <thead class="bg-slate-200 uppercase font-bold">
                    <tr>
                        <th class="text-center" style="width:32px;">Nro°</th>
                        <th style="width:72px;">Fecha</th>
                        <th>Beneficiario</th>
                        <th style="width:85px;">Ref.</th>
                        <th>Descripción</th>
                        <th class="text-right" style="width:85px;">Debe</th>
                        <th class="text-right" style="width:85px;">Haber</th>
                        <th class="text-right" style="width:95px;">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $saldoCorrido = (float)($saldoAnteriorPeriodo ?? 0); ?>
                    <tr class="bg-slate-50 font-semibold">
                        <td class="text-center font-mono">-</td>
                        <td class="font-mono"></td>
                        <td></td>
                        <td></td>
                        <td>Saldo al <?= e($fechaCierreAnt) ?></td>
                        <td class="text-right font-mono">0,00</td>
                        <td class="text-right font-mono">0,00</td>
                        <td class="text-right font-mono font-bold"><?= number_format($saldoCorrido, 2, ',', '.') ?></td>
                    </tr>
                    <?php foreach ($movsCronologicos as $idx => $m): ?>
                        <?php
                        $debe  = (float)$m['monto_debe'];
                        $haber = (float)$m['monto_haber'];
                        $saldoCorrido = round($saldoCorrido + $debe - $haber, 2);
                        ?>
                        <tr>
                            <td class="text-center font-mono"><?= $idx + 1 ?></td>
                            <td class="font-mono"><?= e(date('d/m/Y', strtotime((string)$m['fecha_operacion']))) ?></td>
                            <td><?= e($m['beneficiario'] ?: '—') ?></td>
                            <td class="font-mono"><?= e($m['referencia']) ?></td>
                            <td><?= e($m['descripcion']) ?></td>
                            <td class="text-right font-mono"><?= $debe > 0 ? number_format($debe, 2, ',', '.') : '' ?></td>
                            <td class="text-right font-mono"><?= $haber > 0 ? number_format($haber, 2, ',', '.') : '' ?></td>
                            <td class="text-right font-mono font-bold"><?= number_format($saldoCorrido, 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-100 font-mono font-bold">
                    <tr>
                        <td colspan="7" class="text-right uppercase font-sans">Saldo Final del Período:</td>
                        <td class="text-right"><?= e($simbolo . number_format($saldoCorrido, 2, ',', '.')) ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Cuadro Resumen Estilo Excel: Libro Banco / Banco / Tránsito -->
            <div class="flex justify-end pt-2">
                <table class="border-collapse border border-slate-400 text-xs w-72">
                    <tr class="border-b border-slate-300">
                        <td class="py-1.5 px-3 font-mono font-bold text-right"><?= e($simbolo . number_format((float)$saldoLibros, 2, ',', '.')) ?></td>
                        <td class="py-1.5 px-3 font-bold bg-slate-100">Libro Banco</td>
                    </tr>
                    <tr class="border-b border-slate-300">
                        <td class="py-1.5 px-3 font-mono font-bold text-right"><?= e($simbolo . number_format((float)$saldoBanco, 2, ',', '.')) ?></td>
                        <td class="py-1.5 px-3 font-bold bg-slate-100">Banco</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 px-3 font-mono font-bold text-right"><?= e($simbolo . number_format($transito, 2, ',', '.')) ?></td>
                        <td class="py-1.5 px-3 font-bold bg-slate-100">Tránsito</td>
                    </tr>
                </table>
            </div>

        <?php else: ?>
            <!-- ================================================================= -->
            <!-- FORMATO 1: ESTÁNDAR (CUADRO COMPARATIVO + PARTIDAS PENDIENTES)    -->
            <!-- ================================================================= -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 border border-slate-200 p-3">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Banco / SUDEBAN</span>
                    <strong class="text-slate-900"><?= e($cuenta['banco_nombre']) ?> (<?= e($cuenta['codigo_sudeban']) ?>)</strong>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">N° de Cuenta</span>
                    <strong class="font-mono text-slate-900"><?= e($cuenta['numero_cuenta']) ?></strong>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Tipo / Moneda</span>
                    <strong class="text-slate-900"><?= e($cuenta['tipo_cuenta']) ?> (<?= e($cuenta['moneda']) ?>)</strong>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Estatus</span>
                    <strong class="text-emerald-800 uppercase"><?= e((string)($conciliacion['estatus'] ?? 'Borrador')) ?></strong>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <table class="w-full border-collapse border border-slate-300">
                    <thead>
                        <tr class="bg-emerald-700 text-white">
                            <th colspan="2" class="py-2 px-3 text-left uppercase text-[11px]">Saldo según Banco</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr>
                            <td class="py-2 px-3">Saldo según Estado de Cuenta al cierre</td>
                            <td class="py-2 px-3 text-right font-mono font-semibold"><?= e($simbolo . number_format((float)$saldoBanco, 2, ',', '.')) ?></td>
                        </tr>
                        <tr>
                            <td class="py-2 px-3 text-emerald-800">(+) Depósitos en Tránsito</td>
                            <td class="py-2 px-3 text-right font-mono text-emerald-800"><?= e($simbolo . number_format($depTrans, 2, ',', '.')) ?></td>
                        </tr>
                        <tr>
                            <td class="py-2 px-3 text-rose-800">(-) Cheques / Pagos en Tránsito</td>
                            <td class="py-2 px-3 text-right font-mono text-rose-800"><?= e($simbolo . number_format($cheqTrans, 2, ',', '.')) ?></td>
                        </tr>
                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-400">
                            <td class="py-2.5 px-3 uppercase">Saldo Bancario Conciliado</td>
                            <td class="py-2.5 px-3 text-right font-mono text-sm"><?= e($simbolo . number_format($bancoConc, 2, ',', '.')) ?></td>
                        </tr>
                    </tbody>
                </table>

                <table class="w-full border-collapse border border-slate-300">
                    <thead>
                        <tr class="bg-emerald-700 text-white">
                            <th colspan="2" class="py-2 px-3 text-left uppercase text-[11px]">Saldo según Libros Contables</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr>
                            <td class="py-2 px-3">Saldo según Libro Mayor al cierre</td>
                            <td class="py-2 px-3 text-right font-mono font-semibold"><?= e($simbolo . number_format((float)$saldoLibros, 2, ',', '.')) ?></td>
                        </tr>
                        <tr>
                            <td class="py-2 px-3 text-emerald-800">(+) Notas de Crédito no registradas</td>
                            <td class="py-2 px-3 text-right font-mono text-emerald-800"><?= e($simbolo . number_format($ncNoReg, 2, ',', '.')) ?></td>
                        </tr>
                        <tr>
                            <td class="py-2 px-3 text-rose-800">(-) Notas de Débito no registradas</td>
                            <td class="py-2 px-3 text-right font-mono text-rose-800"><?= e($simbolo . number_format($ndNoReg, 2, ',', '.')) ?></td>
                        </tr>
                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-400">
                            <td class="py-2.5 px-3 uppercase">Saldo en Libros Conciliado</td>
                            <td class="py-2.5 px-3 text-right font-mono text-sm"><?= e($simbolo . number_format($librosConc, 2, ',', '.')) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 border <?= abs($diferencia) < 0.01 ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-amber-50 border-amber-300 text-amber-900' ?> flex items-center justify-between font-bold">
                <span class="uppercase">Diferencia de Conciliación:</span>
                <span class="font-mono text-base"><?= e($simbolo . number_format($diferencia, 2, ',', '.')) ?></span>
            </div>

            <div>
                <h3 class="text-xs font-bold uppercase text-slate-800 mb-2">
                    Detalle de Partidas Bancarias Pendientes del Período (<?= count($pendientesBanco) ?>)
                </h3>
                <table class="tabla-excel">
                    <thead class="bg-slate-100 text-slate-700 uppercase">
                        <tr>
                            <th>Fecha</th>
                            <th>Beneficiario</th>
                            <th>Referencia</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th class="text-right">Entrada</th>
                            <th class="text-right">Salida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pendientesBanco)): ?>
                            <?php foreach ($pendientesBanco as $pb): ?>
                                <tr>
                                    <td class="font-mono"><?= e(date('d/m/Y', strtotime((string)$pb['fecha_operacion']))) ?></td>
                                    <td><?= e($pb['beneficiario'] ?: '—') ?></td>
                                    <td class="font-mono"><?= e($pb['referencia']) ?></td>
                                    <td><?= e($pb['tipo_movimiento']) ?></td>
                                    <td><?= e($pb['descripcion']) ?></td>
                                    <td class="text-right font-mono"><?= (float)$pb['monto_debe'] > 0 ? number_format((float)$pb['monto_debe'], 2, ',', '.') : '—' ?></td>
                                    <td class="text-right font-mono"><?= (float)$pb['monto_haber'] > 0 ? number_format((float)$pb['monto_haber'], 2, ',', '.') : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-3 text-center text-slate-500">Todas las partidas bancarias del período se encuentran conciliadas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($conciliacion['observaciones'])): ?>
            <div class="p-3 bg-slate-50 border border-slate-200">
                <strong class="uppercase text-[10px] text-slate-500 block">Observaciones:</strong>
                <p class="text-slate-800 mt-0.5"><?= e((string)$conciliacion['observaciones']) ?></p>
            </div>
        <?php endif; ?>

        <!-- 3 Firmas -->
        <div class="grid grid-cols-3 gap-8 pt-10 text-center">
            <div class="border-t border-slate-700 pt-2">
                <p class="font-bold uppercase text-[11px]">ELABORADO POR:</p>
                <p class="text-[10px] text-slate-500">Tesorería / Administración</p>
            </div>
            <div class="border-t border-slate-700 pt-2">
                <p class="font-bold uppercase text-[11px]">CONTABILIZADO POR:</p>
                <p class="text-[10px] text-slate-500">Contabilidad General</p>
            </div>
            <div class="border-t border-slate-700 pt-2">
                <p class="font-bold uppercase text-[11px]">REVISADO Y APROBADO POR:</p>
                <p class="text-[10px] text-slate-500"><?= e((string)$emp['razon_social']) ?></p>
            </div>
        </div>

        <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
    </div>

</body>
</html>
