<?php
declare(strict_types=1);

$emp         = $empresa ?? ($empresaActiva ?? []);
$simbolo     = ($cuenta['moneda'] ?? 'VES') === 'USD' ? '$ ' : 'Bs. ';
$depTrans    = (float)($conciliacion['depositos_en_transito'] ?? 0);
$cheqTrans   = (float)($conciliacion['cheques_en_transito'] ?? 0);
$ndNoReg     = (float)($conciliacion['notas_debito_no_registradas'] ?? 0);
$ncNoReg     = (float)($conciliacion['notas_credito_no_registradas'] ?? 0);

$bancoConc   = round((float)$saldoBanco + $depTrans - $cheqTrans, 2);
$librosConc  = round((float)$saldoLibros + $ncNoReg - $ndNoReg, 2);
$diferencia  = round($bancoConc - $librosConc, 2);

$pendientesBanco = array_values(array_filter($movimientosBanco ?? [], static fn(array $m): bool => (int)$m['conciliado'] === 0));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conciliación Bancaria <?= e($periodo) ?> — <?= e((string)$emp['razon_social']) ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            body { background: #ffffff !important; color: #000000 !important; padding: 0 !important; }
            .hoja-conciliacion { box-shadow: none !important; border: none !important; max-width: 100% !important; margin: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-6">

    <!-- Barra de acciones superior (no se imprime) -->
    <div class="max-w-5xl mx-auto mb-5 flex items-center justify-between bg-white border border-slate-200 px-5 py-3 rounded-xl shadow-sm no-print">
        <div>
            <span class="text-[11px] font-mono font-bold uppercase text-emerald-700 block">Reporte Oficial de Conciliación Bancaria</span>
            <span class="text-sm font-bold text-slate-900">
                <?= e($cuenta['banco_nombre']) ?> (•••• <?= e(substr((string)$cuenta['numero_cuenta'], -4)) ?>) — Período <?= e($periodo) ?>
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()"
                    class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">
                Imprimir / Guardar PDF
            </button>
            <button type="button" onclick="window.close()"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300">
                Cerrar
            </button>
        </div>
    </div>

    <div class="hoja-conciliacion max-w-5xl mx-auto bg-white border border-slate-300 rounded-xl shadow-lg p-8 text-xs space-y-6">
        <?php
        $tituloReporte    = 'CONCILIACIÓN BANCARIA MENSUAL';
        $subtituloPeriodo = 'Período: ' . $periodo . ' • Cuenta: ' . (string)$cuenta['banco_nombre'] . ' (' . (string)$cuenta['numero_cuenta'] . ')';
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <!-- Datos de la Cuenta -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 border border-slate-200 rounded-lg p-3">
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

        <!-- Cuadro Resumen Cuadrado -->
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

        <!-- Resultado Diferencia -->
        <div class="p-3 rounded-lg border <?= abs($diferencia) < 0.01 ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-amber-50 border-amber-300 text-amber-900' ?> flex items-center justify-between font-bold">
            <span class="uppercase">Diferencia de Conciliación:</span>
            <span class="font-mono text-base"><?= e($simbolo . number_format($diferencia, 2, ',', '.')) ?></span>
        </div>

        <!-- Detalle de Partidas Bancarias Pendientes de Conciliar -->
        <div>
            <h3 class="text-xs font-bold uppercase text-slate-800 mb-2">
                Detalle de Partidas Bancarias Pendientes del Período (<?= count($pendientesBanco) ?>)
            </h3>
            <table class="w-full border-collapse border border-slate-300 text-[11px]">
                <thead class="bg-slate-100 text-slate-700 uppercase">
                    <tr>
                        <th class="border border-slate-300 py-1.5 px-2">Fecha</th>
                        <th class="border border-slate-300 py-1.5 px-2">Beneficiario</th>
                        <th class="border border-slate-300 py-1.5 px-2">Referencia</th>
                        <th class="border border-slate-300 py-1.5 px-2">Tipo</th>
                        <th class="border border-slate-300 py-1.5 px-2">Descripción</th>
                        <th class="border border-slate-300 py-1.5 px-2 text-right">Entrada</th>
                        <th class="border border-slate-300 py-1.5 px-2 text-right">Salida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pendientesBanco)): ?>
                        <?php foreach ($pendientesBanco as $pb): ?>
                            <tr>
                                <td class="border border-slate-300 py-1.5 px-2 font-mono"><?= e(date('d/m/Y', strtotime((string)$pb['fecha_operacion']))) ?></td>
                                <td class="border border-slate-300 py-1.5 px-2"><?= e($pb['beneficiario'] ?: '—') ?></td>
                                <td class="border border-slate-300 py-1.5 px-2 font-mono"><?= e($pb['referencia']) ?></td>
                                <td class="border border-slate-300 py-1.5 px-2"><?= e($pb['tipo_movimiento']) ?></td>
                                <td class="border border-slate-300 py-1.5 px-2"><?= e($pb['descripcion']) ?></td>
                                <td class="border border-slate-300 py-1.5 px-2 text-right font-mono">
                                    <?= (float)$pb['monto_debe'] > 0 ? number_format((float)$pb['monto_debe'], 2, ',', '.') : '—' ?>
                                </td>
                                <td class="border border-slate-300 py-1.5 px-2 text-right font-mono">
                                    <?= (float)$pb['monto_haber'] > 0 ? number_format((float)$pb['monto_haber'], 2, ',', '.') : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="border border-slate-300 py-3 text-center text-slate-500">
                                Todas las partidas bancarias del período se encuentran conciliadas.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($conciliacion['observaciones'])): ?>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                <strong class="uppercase text-[10px] text-slate-500 block">Observaciones:</strong>
                <p class="text-slate-800 mt-0.5"><?= e((string)$conciliacion['observaciones']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Firmas -->
        <div class="grid grid-cols-3 gap-8 pt-10 text-center">
            <div class="border-t border-slate-400 pt-2">
                <p class="font-bold uppercase text-[11px]">ELABORADO POR:</p>
                <p class="text-[10px] text-slate-500">Tesorería / Administración</p>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <p class="font-bold uppercase text-[11px]">CONTABILIZADO POR:</p>
                <p class="text-[10px] text-slate-500">Contabilidad General</p>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <p class="font-bold uppercase text-[11px]">REVISADO Y APROBADO POR:</p>
                <p class="text-[10px] text-slate-500"><?= e((string)$emp['razon_social']) ?></p>
            </div>
        </div>

        <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
    </div>

</body>
</html>
