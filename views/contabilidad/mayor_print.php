<?php
declare(strict_types=1);

$esTabla    = ($formato ?? 'clasico') === 'tabla';
$esResumido = (bool)($agruparMes ?? false);
$folioStr   = str_pad((string)($folio ?? 1), 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libro Mayor Oficial — <?= e((string)$empresa['razon_social']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
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
    <div class="max-w-5xl mx-auto mb-6 bg-slate-900 text-white rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-lg no-print">
        <div>
            <span class="text-[11px] font-mono uppercase text-emerald-400 block">Formato Oficial Registro Mercantil (Hojas Foliadas)</span>
            <h1 class="text-sm font-bold">
                <?= $esTabla
                    ? 'Modelo 2: Libro Mayor en Cuadro Fiscal (Estilo Inversiones Charq\'Carnes)'
                    : 'Modelo 1: Mayor Analítico con Punteado y Doble Subrayado (Estilo Auto-Eléctrico Ítalo)' ?>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= e(route_url('contabilidad', 'mayorPrint', [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'cuenta_id'   => $cuentaId ?? 0,
                'formato'     => 'clasico',
                'agrupar_mes' => '1',
                'folio'       => $folio,
            ])) ?>"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= (!$esTabla && $esResumido) ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-200 hover:bg-slate-700' ?>">
                Mayor Resumido por Mes (Folio)
            </a>

            <a href="<?= e(route_url('contabilidad', 'mayorPrint', [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'cuenta_id'   => $cuentaId ?? 0,
                'formato'     => 'clasico',
                'agrupar_mes' => '0',
                'folio'       => $folio,
            ])) ?>"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= (!$esTabla && !$esResumido) ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-200 hover:bg-slate-700' ?>">
                Mayor Analítico Detallado (Folio)
            </a>

            <a href="<?= e(route_url('contabilidad', 'mayorPrint', [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'cuenta_id'   => $cuentaId ?? 0,
                'formato'     => 'tabla',
                'folio'       => $folio,
            ])) ?>"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= $esTabla ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-200 hover:bg-slate-700' ?>">
                Libro Mayor (Tabla Mercantil)
            </a>

            <button type="button" onclick="window.print()"
                    class="px-4 py-1.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 transition">
                Imprimir / PDF
            </button>
            <button type="button" onclick="window.close()"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300">
                Cerrar
            </button>
        </div>
    </div>

    <!-- Hoja de Impresión -->
    <div class="hoja-contable max-w-5xl mx-auto bg-white p-8 shadow-xl border border-slate-300">

        <?php if (!$esTabla): ?>
            <!-- ========================================================================= -->
            <!-- MODELO 1: MAYOR ANALÍTICO CLÁSICO CON PUNTEADO Y DOBLE SUBRAYADO          -->
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
                    <h3 class="text-xs font-bold mt-0.5">
                        <?= $esResumido ? 'Mayor Analítico Resumido' : 'Mayor Analítico' ?>
                    </h3>
                    <p class="text-[11px] font-semibold mt-0.5">
                        Desde <?= e(date('d/m/Y', strtotime($fechaDesde))) ?> AL <?= e(date('d/m/Y', strtotime($fechaHasta))) ?>
                    </p>
                </div>
            </div>

            <?php if (empty($libroMayor)): ?>
                <div class="py-12 text-center text-xs text-slate-500">
                    No existen movimientos contables para las cuentas en el período seleccionado.
                </div>
            <?php else: ?>
                <div class="space-y-7">
                    <?php foreach ($libroMayor as $bloque): ?>
                        <?php
                        $cta = $bloque['cuenta'];
                        $esDeudora = ($cta['naturaleza'] ?? 'DEUDORA') === 'DEUDORA';
                        $saldoInicial = (float)$bloque['saldo_anterior'];

                        // Si se seleccionó el modo "Mayor Analítico Resumido" (igual a las fotos 3, 4 y 6),
                        // agrupamos los movimientos por fin de mes con concepto "MOVIMIENTO DEL MES".
                        $filasMostrar = [];
                        if ($esResumido) {
                            $porMes = [];
                            foreach ($bloque['movimientos'] as $m) {
                                $ym = substr((string)$m['fecha'], 0, 7);
                                if (!isset($porMes[$ym])) {
                                    $porMes[$ym] = [
                                        'fecha_cierre' => date('t/m/Y', strtotime($ym . '-01')),
                                        'debe'         => 0.0,
                                        'haber'        => 0.0,
                                    ];
                                }
                                $porMes[$ym]['debe']  += (float)$m['debe'];
                                $porMes[$ym]['haber'] += (float)$m['haber'];
                            }
                            $saldoCorriente = $saldoInicial;
                            foreach ($porMes as $datosMes) {
                                $saldoCorriente += $esDeudora
                                    ? ($datosMes['debe'] - $datosMes['haber'])
                                    : ($datosMes['haber'] - $datosMes['debe']);
                                $filasMostrar[] = [
                                    'fecha'    => $datosMes['fecha_cierre'],
                                    'concepto' => 'MOVIMIENTO DEL MES',
                                    'debe'     => $datosMes['debe'],
                                    'haber'    => $datosMes['haber'],
                                    'saldo'    => $saldoCorriente,
                                ];
                            }
                        } else {
                            foreach ($bloque['movimientos'] as $m) {
                                $filasMostrar[] = [
                                    'fecha'    => date('d/m/Y', strtotime((string)$m['fecha'])),
                                    'concepto' => (string)($m['descripcion_linea'] ?: $m['concepto']),
                                    'debe'     => (float)$m['debe'],
                                    'haber'    => (float)$m['haber'],
                                    'saldo'    => (float)$m['saldo_acumulado'],
                                ];
                            }
                        }
                        ?>
                        <div class="text-xs">
                            <div class="grid grid-cols-12 gap-2 mb-1 font-bold text-[11px]">
                                <div class="col-span-2">Cuenta Contable</div>
                                <div class="col-span-10 font-mono"><?= e((string)$cta['codigo']) ?></div>
                                <div class="col-span-2">Nombre</div>
                                <div class="col-span-10 uppercase"><?= e((string)$cta['nombre']) ?></div>
                            </div>

                            <table class="w-full text-left border-collapse font-mono text-[11px]">
                                <thead>
                                    <tr class="linea-punteada font-sans font-bold">
                                        <th class="py-1 w-28">Fecha</th>
                                        <th class="py-1">Concepto</th>
                                        <th class="py-1 text-right w-32">Debe</th>
                                        <th class="py-1 text-right w-32">Haber</th>
                                        <th class="py-1 text-right w-32">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="py-1"></td>
                                        <td class="py-1 font-sans font-bold">Saldo Inicial</td>
                                        <td class="py-1 text-right"></td>
                                        <td class="py-1 text-right"></td>
                                        <td class="py-1 text-right font-bold"><?= number_format($saldoInicial, 2, ',', '.') ?></td>
                                    </tr>
                                    <?php foreach ($filasMostrar as $fm): ?>
                                        <tr>
                                            <td class="py-0.5"><?= e($fm['fecha']) ?></td>
                                            <td class="py-0.5 font-sans uppercase"><?= e($fm['concepto']) ?></td>
                                            <td class="py-0.5 text-right"><?= number_format((float)$fm['debe'], 2, ',', '.') ?></td>
                                            <td class="py-0.5 text-right"><?= number_format((float)$fm['haber'], 2, ',', '.') ?></td>
                                            <td class="py-0.5 text-right"><?= number_format((float)$fm['saldo'], 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="linea-punteada">
                                        <td colspan="5" class="pt-1"></td>
                                    </tr>
                                    <tr class="font-bold">
                                        <td colspan="2" class="py-1 font-sans">TOTALES . . . . . . .</td>
                                        <td class="py-1 text-right doble-subrayado"><?= number_format((float)($bloque['total_debe_periodo'] ?? 0), 2, ',', '.') ?></td>
                                        <td class="py-1 text-right doble-subrayado"><?= number_format((float)($bloque['total_haber_periodo'] ?? 0), 2, ',', '.') ?></td>
                                        <td class="py-1 text-right doble-subrayado"><?= number_format((float)($bloque['saldo_final'] ?? 0), 2, ',', '.') ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- ========================================================================= -->
            <!-- MODELO 2: LIBRO MAYOR EN TABLA MERCANTIL (ESTILO INVERSIONES CHARQ'CARNES)-->
            <!-- ========================================================================= -->
            <div class="flex items-start justify-between mb-3">
                <h2 class="text-lg font-extrabold uppercase tracking-wide">LIBRO MAYOR</h2>
                <div class="text-right font-mono text-xs">
                    <span class="font-bold text-sm"><?= (int)$folio ?></span>
                </div>
            </div>

            <div class="mb-4 max-w-xl">
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

                <table class="w-80 tabla-mercantil mt-2">
                    <tr>
                        <td colspan="2" class="font-bold text-center bg-slate-100">PERÍODO FISCAL A CONTABILIZAR</td>
                    </tr>
                    <tr>
                        <td class="font-bold bg-slate-50 w-28">INICIO</td>
                        <td class="font-mono text-center font-bold"><?= e(date('d/m/Y', strtotime($fechaDesde))) ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold bg-slate-50">CIERRE</td>
                        <td class="font-mono text-center font-bold"><?= e(date('d/m/Y', strtotime($fechaHasta))) ?></td>
                    </tr>
                </table>
            </div>

            <table class="w-full tabla-mercantil border-collapse">
                <thead>
                    <tr class="bg-slate-200 font-bold uppercase">
                        <th>CÓDIGO Y DESCRIPCIÓN DE LA CUENTA</th>
                        <th class="w-28 text-right">DEBE</th>
                        <th class="w-28 text-right">HABER</th>
                        <th class="w-28 text-right">SALDO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($libroMayor as $bloque): ?>
                        <?php $cta = $bloque['cuenta']; ?>
                        <tr class="bg-slate-100 font-bold">
                            <td class="font-mono"><?= e((string)$cta['codigo']) ?> — <span class="font-sans uppercase"><?= e((string)$cta['nombre']) ?></span></td>
                            <td class="text-right font-mono"><?= number_format((float)($bloque['total_debe_periodo'] ?? 0), 2, ',', '.') ?></td>
                            <td class="text-right font-mono"><?= number_format((float)($bloque['total_haber_periodo'] ?? 0), 2, ',', '.') ?></td>
                            <td class="text-right font-mono"><?= number_format((float)($bloque['saldo_final'] ?? 0), 2, ',', '.') ?></td>
                        </tr>
                        <?php foreach ($bloque['movimientos'] as $m): ?>
                            <tr>
                                <td class="pl-6 uppercase text-[10px]">
                                    <span class="font-mono text-slate-600"><?= e(date('d/m/Y', strtotime((string)$m['fecha']))) ?></span>
                                    — <?= e((string)($m['descripcion_linea'] ?: $m['concepto'])) ?>
                                </td>
                                <td class="text-right font-mono"><?= number_format((float)$m['debe'], 2, ',', '.') ?></td>
                                <td class="text-right font-mono"><?= number_format((float)$m['haber'], 2, ',', '.') ?></td>
                                <td class="text-right font-mono"><?= number_format((float)$m['saldo_acumulado'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="mt-6 text-center text-xs font-bold uppercase">
                <div><?= e((string)$empresa['razon_social']) ?></div>
                <div class="font-mono"><?= e((string)$empresa['rif']) ?></div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
