<?php
declare(strict_types=1);

$esResumen = ($formato ?? 'detallado') === 'resumen';
$folioStr  = str_pad((string)($folio ?? 1), 4, '0', STR_PAD_LEFT);

$sumaTotalDebe  = 0.0;
$sumaTotalHaber = 0.0;

if ($esResumen) {
    foreach ($resumenCuentas as $rc) {
        $sumaTotalDebe  += (float)$rc['total_debe'];
        $sumaTotalHaber += (float)$rc['total_haber'];
    }
} else {
    foreach ($asientos as $asi) {
        $sumaTotalDebe  += (float)$asi['total_debe'];
        $sumaTotalHaber += (float)$asi['total_haber'];
    }
}
$diferencia = round(abs($sumaTotalDebe - $sumaTotalHaber), 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $esResumen ? 'Resumen de Diario' : 'Libro Diario Legal' ?> — <?= e((string)$empresa['razon_social']) ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            body { background: #ffffff !important; color: #000000 !important; padding: 0 !important; }
            .hoja-contable { box-shadow: none !important; border: none !important; margin: 0 !important; max-width: 100% !important; }
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #e2e8f0;
            color: #000000;
        }
        .doble-subrayado {
            border-bottom: 3px double #000000;
        }
        .linea-punteada {
            border-bottom: 1px dashed #334155;
        }
        .tabla-mercantil th, .tabla-mercantil td {
            border: 1px solid #1e293b;
            padding: 3px 6px;
            font-size: 10px;
        }
    </style>
</head>
<body class="py-6 px-4">

    <!-- Barra de Control Superior (No se imprime) -->
    <div class="max-w-5xl mx-auto mb-6 bg-white text-slate-900 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-lg no-print">
        <div>
            <span class="text-[11px] font-mono uppercase text-emerald-700 block">Formato Oficial Registro Mercantil (Hojas Foliadas)</span>
            <h1 class="text-sm font-bold">
                <?= $esResumen ? 'Modelo 1: Resumen de Diario por Cuenta (Estilo Clásico Foliado)' : 'Modelo 2: Libro Diario Legal Detallado (Estilo Cuadro Registro Mercantil)' ?>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= e(route_url('contabilidad', 'diarioPrint', ['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta, 'formato' => 'detallado', 'folio' => $folio])) ?>"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= !$esResumen ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-100 text-slate-800 hover:bg-slate-200' ?>">
                Formato Libro Diario (Tabla)
            </a>
            <a href="<?= e(route_url('contabilidad', 'diarioPrint', ['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta, 'formato' => 'resumen', 'folio' => $folio])) ?>"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= $esResumen ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-100 text-slate-800 hover:bg-slate-200' ?>">
                Formato Resumen de Diario (Folio)
            </a>
            <button type="button" onclick="window.print()"
                    class="px-4 py-1.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 transition">
                Imprimir / PDF
            </button>
            <button type="button" onclick="window.close()"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">
                Cerrar
            </button>
        </div>
    </div>

    <!-- Hoja de Impresión -->
    <div class="hoja-contable max-w-5xl mx-auto bg-white p-8 shadow-xl border border-slate-300">
        <?php
        $tituloReporte = $esResumen ? 'RESUMEN DE DIARIO POR CUENTA' : 'LIBRO DIARIO LEGAL DETALLADO';
        $subtituloPeriodo = 'Del ' . date('d/m/Y', strtotime((string)$fechaDesde)) . ' al ' . date('d/m/Y', strtotime((string)$fechaHasta)) . ' • Folio N° ' . $folioStr;
        require ROOT_PATH . '/views/layouts/membrete_reporte.php';
        ?>

        <?php if ($esResumen): ?>
            <!-- ========================================================================= -->
            <!-- FORMATO 1: RESUMEN DE DIARIO CLÁSICO (IDÉNTICO A AUTO-ELÉCTRICO ÍTALO)    -->
            <!-- ========================================================================= -->
            <div class="relative mb-6">
                <div class="text-right font-mono text-xs mb-1">
                    <span class="text-rose-700 font-bold text-base">N° <?= e($folioStr) ?></span>
                    <span class="block text-[11px] text-slate-700">Pág. 1</span>
                </div>
                <div class="text-center">
                    <h2 class="text-sm font-bold uppercase tracking-wide">
                        <?= e((string)$empresa['razon_social']) ?> - RIF <?= e((string)$empresa['rif']) ?>
                    </h2>
                    <h3 class="text-xs font-bold mt-0.5">Resumen de Diario</h3>
                    <p class="text-[11px] font-semibold mt-0.5">
                        Del <?= e(date('d/m/Y', strtotime($fechaDesde))) ?> Al <?= e(date('d/m/Y', strtotime($fechaHasta))) ?>
                    </p>
                </div>
            </div>

            <table class="w-full text-left border-collapse text-xs font-mono">
                <thead>
                    <tr class="border-b border-black text-[11px] font-bold">
                        <th class="py-1.5 pr-3 w-28">CÓDIGO</th>
                        <th class="py-1.5 pr-4">NOMBRE DE LA CUENTA</th>
                        <th class="py-1.5 px-3 text-right w-36">DEBE</th>
                        <th class="py-1.5 pl-3 text-right w-36">HABER</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($resumenCuentas)): ?>
                        <tr>
                            <td colspan="4" class="py-8 text-center font-sans text-slate-500">
                                No existen asientos publicados en el rango de fechas seleccionado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($resumenCuentas as $rc): ?>
                            <tr>
                                <td class="py-1 pr-3"><?= e((string)$rc['codigo']) ?></td>
                                <td class="py-1 pr-4 uppercase font-sans"><?= e((string)$rc['nombre']) ?></td>
                                <td class="py-1 px-3 text-right"><?= number_format((float)$rc['total_debe'], 2, ',', '.') ?></td>
                                <td class="py-1 pl-3 text-right"><?= number_format((float)$rc['total_haber'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="linea-punteada">
                        <td colspan="4" class="pt-2"></td>
                    </tr>
                    <tr class="font-bold text-xs">
                        <td colspan="2" class="py-1.5 pr-6 text-right tracking-widest">TOTALES . . . . . . . . .</td>
                        <td class="py-1.5 px-3 text-right doble-subrayado"><?= number_format($sumaTotalDebe, 2, ',', '.') ?></td>
                        <td class="py-1.5 pl-3 text-right doble-subrayado"><?= number_format($sumaTotalHaber, 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>

        <?php else: ?>
            <!-- ========================================================================= -->
            <!-- FORMATO 2: LIBRO DIARIO DETALLADO (IDÉNTICO A INVERSIONES CHARQ'CARNES)   -->
            <!-- ========================================================================= -->
            <div class="flex items-start justify-between mb-3">
                <h2 class="text-lg font-extrabold uppercase tracking-wide">LIBRO DIARIO</h2>
                <div class="text-right font-mono text-xs">
                    <span class="font-bold text-sm"><?= (int)$folio ?></span>
                </div>
            </div>

            <!-- Cajas Superiores de Identificación Fiscal y Cuadre del Debe y Haber -->
            <div class="grid grid-cols-12 gap-4 mb-4 items-end">
                <div class="col-span-7">
                    <table class="w-full tabla-mercantil">
                        <tr>
                            <td class="font-bold bg-slate-100 w-44">NOMBRE DE LA EMPRESA</td>
                            <td class="font-bold uppercase"><?= e((string)$empresa['razon_social']) ?></td>
                        </tr>
                        <tr>
                            <td class="font-bold bg-slate-100">R.I.F</td>
                            <td class="font-mono font-bold"><?= e((string)$empresa['rif']) ?></td>
                        </tr>
                        <tr>
                            <td class="font-bold bg-slate-100">DIRECCIÓN FISCAL</td>
                            <td><?= e((string)$empresa['direccion_fiscal']) ?></td>
                        </tr>
                    </table>

                    <table class="w-72 tabla-mercantil mt-2">
                        <tr>
                            <td colspan="2" class="font-bold text-center bg-slate-100">PERÍODO FISCAL A CONTABILIZAR</td>
                        </tr>
                        <tr>
                            <td class="font-bold bg-slate-50 w-24">INICIO</td>
                            <td class="font-mono text-center font-bold"><?= e(date('d/m/Y', strtotime($fechaDesde))) ?></td>
                        </tr>
                        <tr>
                            <td class="font-bold bg-slate-50">CIERRE</td>
                            <td class="font-mono text-center font-bold"><?= e(date('d/m/Y', strtotime($fechaHasta))) ?></td>
                        </tr>
                    </table>
                </div>

                <div class="col-span-5">
                    <table class="w-full tabla-mercantil font-mono">
                        <tr>
                            <td class="font-sans font-bold bg-slate-100">CUADRE DEL DEBE Y HABER</td>
                            <td class="text-right font-bold"><?= number_format($sumaTotalDebe, 2, ',', '.') ?></td>
                            <td class="text-right font-bold"><?= number_format($sumaTotalHaber, 2, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td class="font-sans font-bold bg-slate-200">DIFERENCIA</td>
                            <td colspan="2" class="text-center font-bold"><?= number_format($diferencia, 2, ',', '.') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Tabla Principal del Libro Diario -->
            <table class="w-full tabla-mercantil border-collapse">
                <thead>
                    <tr class="bg-slate-200 text-center font-bold uppercase">
                        <th class="w-20">NÚMERO DE LA OPERACIÓN</th>
                        <th class="w-24">FECHA DE LA OPERACIÓN</th>
                        <th class="w-28">CÓDIGO DE LA CUENTA</th>
                        <th>DESCRIPCIÓN DE LA CUENTA</th>
                        <th>DETALLE DE LA OPERACIÓN</th>
                        <th class="w-28 text-right">DEBE</th>
                        <th class="w-28 text-right">HABER</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($asientos)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">
                                No hay asientos publicados en el período seleccionado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($asientos as $idxOp => $asi): ?>
                            <?php $numOperacion = $idxOp + 1; ?>
                            <?php foreach ($asi['detalles'] as $det): ?>
                                <tr>
                                    <td class="text-center font-mono"><?= $numOperacion ?></td>
                                    <td class="text-center font-mono"><?= e(date('d/m/Y', strtotime((string)$asi['fecha']))) ?></td>
                                    <td class="text-center font-mono"><?= e((string)$det['cuenta_codigo']) ?></td>
                                    <td class="uppercase font-semibold"><?= e((string)$det['cuenta_nombre']) ?></td>
                                    <td class="uppercase"><?= e((string)($det['descripcion'] ?: $asi['concepto'])) ?></td>
                                    <td class="text-right font-mono">
                                        <?= (float)$det['debe'] > 0 ? number_format((float)$det['debe'], 2, ',', '.') : '' ?>
                                    </td>
                                    <td class="text-right font-mono">
                                        <?= (float)$det['haber'] > 0 ? number_format((float)$det['haber'], 2, ',', '.') : '' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-mono font-bold">
                        <td colspan="5" class="text-right uppercase font-sans">TOTALES DEL LIBRO DIARIO:</td>
                        <td class="text-right"><?= number_format($sumaTotalDebe, 2, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($sumaTotalHaber, 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Pie de Página Oficial -->
            <div class="mt-6 text-center text-xs font-bold uppercase">
                <div><?= e((string)$empresa['razon_social']) ?></div>
                <div class="font-mono"><?= e((string)$empresa['rif']) ?></div>
            </div>
        <?php endif; ?>

        <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
    </div>
</body>
</html>
