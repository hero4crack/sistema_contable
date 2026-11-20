<?php
declare(strict_types=1);

$esCestaticket = ($tipoDocumento ?? 'NOMINA') === 'CESTATICKET';
$tituloDoc     = $esCestaticket
    ? 'Recibo de Pago de Bono de Alimentación (Cestaticket Socialista)'
    : 'Recibo de Pago de Nómina (Art. 106 LOTTT)';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloDoc) ?> — <?= e((string)$empresa['razon_social']) ?></title>
    <script src="<?= e(base_url('assets/vendor/tailwind/tailwindcss.js')) ?>"></script>
    <style>
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            body { background: #ffffff !important; color: #000000 !important; }
            .page-break { page-break-after: always; }
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }
    </style>
</head>
<body class="py-6 px-4">

    <!-- Barra Superior de Impresión -->
    <div class="max-w-4xl mx-auto mb-6 bg-white text-slate-900 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-lg no-print">
        <div>
            <span class="text-xs font-mono uppercase text-emerald-700 block">Vista Oficial de Impresión LOTTT</span>
            <h1 class="text-sm font-bold"><?= e($tituloDoc) ?> (<?= count($recibos) ?> <?= count($recibos) === 1 ? 'recibo' : 'recibos' ?>)</h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()"
                    class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition">
                Imprimir / Guardar como PDF
            </button>
            <button type="button" onclick="window.close()"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 transition">
                Cerrar Ventana
            </button>
        </div>
    </div>

    <!-- Contenedor de Recibos -->
    <div class="max-w-4xl mx-auto space-y-8">
        <?php foreach ($recibos as $idx => $r): ?>
            <?php
            $esUltimo = ($idx === count($recibos) - 1);
            ?>
            <div class="bg-white border-2 border-slate-200 rounded-xl p-6 shadow-sm <?= !$esUltimo ? 'page-break' : '' ?>">
                <?php
                $tituloReporte = $esCestaticket ? 'RECIBO DE CESTATICKET SOCIALISTA' : 'RECIBO DE PAGO DE NÓMINA (LOTTT)';
                $subtituloPeriodo = 'Lote: ' . (string)($esCestaticket ? $r['codigo_lote'] : $r['codigo_nomina']) . ' • Período: ' . (string)($r['periodo'] ?? '');
                require ROOT_PATH . '/views/layouts/membrete_reporte.php';
                ?>

                <!-- Datos del Trabajador -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 py-3 px-4 my-3 bg-slate-100 rounded-lg border border-slate-300 text-xs">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-500 block">Trabajador(a):</span>
                        <strong class="text-slate-900 uppercase"><?= e((string)$r['nombre_completo']) ?></strong>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-500 block">Cédula de Identidad:</span>
                        <strong class="font-mono text-slate-900"><?= e((string)$r['cedula']) ?></strong>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-500 block">Cargo / Departamento:</span>
                        <strong class="text-slate-900"><?= e((string)$r['cargo']) ?></strong>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-500 block">Fecha de Ingreso:</span>
                        <strong class="font-mono text-slate-900"><?= e(date('d/m/Y', strtotime((string)$r['fecha_ingreso']))) ?></strong>
                    </div>
                </div>

                <?php if (!$esCestaticket): ?>
                    <!-- ========================================================= -->
                    <!-- FORMATO 1: RECIBO DE NÓMINA REGULAR (ART. 106 LOTTT)      -->
                    <!-- ========================================================= -->
                    <div class="grid grid-cols-3 gap-3 mb-3 text-xs font-mono bg-slate-50 p-2.5 rounded border border-slate-300">
                        <div>
                            <span class="text-[10px] uppercase text-slate-500 block">Período de Pago:</span>
                            <strong>Del <?= e(date('d/m/Y', strtotime((string)$r['fecha_desde']))) ?> al <?= e(date('d/m/Y', strtotime((string)$r['fecha_hasta']))) ?></strong>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-slate-500 block">Salario Base Mensual:</span>
                            <strong><?= e(formato_ves((float)$r['salario_mensual'])) ?></strong>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-slate-500 block">Salario Diario Base:</span>
                            <strong><?= e(formato_ves((float)$r['sueldo_diario'])) ?></strong>
                        </div>
                    </div>

                    <table class="w-full text-left border-collapse border border-slate-200 text-xs mb-3">
                        <thead>
                            <tr class="bg-slate-200 border-b border-slate-200 text-[11px] font-bold uppercase">
                                <th class="py-2 px-2.5 border-r border-slate-200 w-16">Cód.</th>
                                <th class="py-2 px-2.5 border-r border-slate-200">Descripción del Concepto</th>
                                <th class="py-2 px-2.5 border-r border-slate-200 text-center w-24">Cant. / %</th>
                                <th class="py-2 px-2.5 border-r border-slate-200 text-right w-36">Asignaciones (Bs.)</th>
                                <th class="py-2 px-2.5 text-right w-36">Deducciones (Bs.)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-300 font-mono text-xs">
                            <tr>
                                <td class="py-1.5 px-2.5 border-r border-slate-200">A-01</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 font-sans font-medium">Días Hábiles Trabajados</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 text-center"><?= number_format((float)$r['dias_habiles'], 1, ',', '.') ?> días</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 text-right"><?= number_format((float)$r['monto_dias_habiles'], 2, ',', '.') ?></td>
                                <td class="py-1.5 px-2.5 text-right">-</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 px-2.5 border-r border-slate-200">A-02</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 font-sans font-medium">Días de Descanso Remunerado (Sáb/Dom)</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 text-center"><?= number_format((float)$r['dias_descanso'], 1, ',', '.') ?> días</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 text-right"><?= number_format((float)$r['monto_dias_descanso'], 2, ',', '.') ?></td>
                                <td class="py-1.5 px-2.5 text-right">-</td>
                            </tr>
                            <?php if ((float)$r['dias_feriados'] > 0): ?>
                                <tr>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200">A-03</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 font-sans font-medium">Días Feriados Remunerados</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 text-center"><?= number_format((float)$r['dias_feriados'], 1, ',', '.') ?> días</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 text-right"><?= number_format((float)$r['monto_dias_feriados'], 2, ',', '.') ?></td>
                                    <td class="py-1.5 px-2.5 text-right">-</td>
                                </tr>
                            <?php endif; ?>
                            <?php if ((float)$r['horas_extras'] > 0): ?>
                                <tr>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200">A-04</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 font-sans font-medium">Horas Extras / Recargos LOTTT</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 text-center">Período</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 text-right"><?= number_format((float)$r['horas_extras'], 2, ',', '.') ?></td>
                                    <td class="py-1.5 px-2.5 text-right">-</td>
                                </tr>
                            <?php endif; ?>
                            <?php if ((float)$r['bonificaciones'] > 0): ?>
                                <tr>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200">A-05</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 font-sans font-medium">Bono de Producción / Bonificaciones</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 text-center">Período</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 text-right"><?= number_format((float)$r['bonificaciones'], 2, ',', '.') ?></td>
                                    <td class="py-1.5 px-2.5 text-right">-</td>
                                </tr>
                            <?php endif; ?>

                            <!-- Deducciones -->
                            <tr>
                                <td class="py-1.5 px-2.5 border-r border-slate-200">D-01</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 font-sans font-medium">Retención Seguro Social Obligatorio (IVSS)</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 text-center"><?= e((string)$config['porcentaje_sso_empleado']) ?>%</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 text-right">-</td>
                                <td class="py-1.5 px-2.5 text-right"><?= number_format((float)$r['retencion_sso'], 2, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td class="py-1.5 px-2.5 border-r border-slate-200">D-02</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 font-sans font-medium">Retención Régimen Prestacional de Empleo (RPE)</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 text-center"><?= e((string)$config['porcentaje_rpe_empleado']) ?>%</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 text-right">-</td>
                                <td class="py-1.5 px-2.5 text-right"><?= number_format((float)$r['retencion_rpe'], 2, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td class="py-1.5 px-2.5 border-r border-slate-200">D-03</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 font-sans font-medium">Retención Ahorro Habitacional (FAOV / Banavih)</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 text-center"><?= e((string)$config['porcentaje_faov_empleado']) ?>%</td>
                                <td class="py-1.5 px-2.5 border-r border-slate-200 text-right">-</td>
                                <td class="py-1.5 px-2.5 text-right"><?= number_format((float)$r['retencion_faov'], 2, ',', '.') ?></td>
                            </tr>
                            <?php if (((float)$r['retencion_islr'] + (float)$r['otras_deducciones']) > 0): ?>
                                <tr>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200">D-04</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 font-sans font-medium">Retención ISLR / Otras Deducciones</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 text-center">Período</td>
                                    <td class="py-1.5 px-2.5 border-r border-slate-200 text-right">-</td>
                                    <td class="py-1.5 px-2.5 text-right"><?= number_format((float)$r['retencion_islr'] + (float)$r['otras_deducciones'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100 border-t-2 border-slate-200 font-mono font-bold text-xs">
                                <td colspan="3" class="py-2 px-2.5 border-r border-slate-200 text-right uppercase">Subtotales del Período:</td>
                                <td class="py-2 px-2.5 border-r border-slate-200 text-right"><?= e(formato_ves((float)$r['total_asignaciones'])) ?></td>
                                <td class="py-2 px-2.5 text-right"><?= e(formato_ves((float)$r['total_deducciones'])) ?></td>
                            </tr>
                            <tr class="bg-white text-slate-900 font-mono font-bold text-sm">
                                <td colspan="3" class="py-2.5 px-3 text-right uppercase">NETO A CANCELAR AL TRABAJADOR(A):</td>
                                <td colspan="2" class="py-2.5 px-3 text-right text-base"><?= e(formato_ves((float)$r['neto_pagar'])) ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="p-3 bg-slate-100 border border-slate-300 rounded-lg text-xs mb-4">
                        <span class="font-bold uppercase text-slate-600">Son:</span>
                        <span class="font-mono font-bold text-slate-900"><?= e(monto_en_letras_ves((float)$r['neto_pagar'])) ?></span>
                        <?php if (!empty($r['banco'])): ?>
                            <span class="block text-[11px] text-slate-600 mt-1">
                                Forma de Pago: Transferencia Bancaria — <strong><?= e((string)$r['banco']) ?></strong>
                                <?= !empty($r['numero_cuenta']) ? ' • Cuenta N° ' . e((string)$r['numero_cuenta']) : '' ?>
                            </span>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <!-- ========================================================= -->
                    <!-- FORMATO 2: RECIBO LEGAL DE CESTATICKET SOCIALISTA         -->
                    <!-- ========================================================= -->
                    <div class="p-4 bg-amber-50/60 border border-slate-400 rounded-lg text-xs leading-relaxed text-justify mb-4">
                        He recibido de <strong><?= e((string)$empresa['razon_social']) ?></strong>, identificada con el RIF
                        <strong><?= e((string)$empresa['rif']) ?></strong>, la cantidad de
                        <strong><?= e(monto_en_letras_ves((float)$r['total_cestaticket'])) ?></strong>
                        (<strong><?= e(formato_ves((float)$r['total_cestaticket'])) ?></strong>), por concepto de pago en efectivo del
                        <strong>Beneficio de Alimentación (Cestaticket Socialista)</strong> correspondiente al período
                        <strong><?= e((string)$r['periodo']) ?></strong>, dando cumplimiento a lo establecido en el
                        <strong>Artículo 4 Parágrafo Primero</strong> del Decreto con Rango, Valor y Fuerza de Ley del Cestaticket Socialista para los Trabajadores y Trabajadoras, sin que dicho beneficio tenga carácter salarial conforme al Artículo 7 de la referida Ley.
                    </div>

                    <table class="w-full text-left border-collapse border border-slate-200 text-xs mb-4">
                        <thead>
                            <tr class="bg-slate-200 border-b border-slate-200 text-[11px] font-bold uppercase">
                                <th class="py-2 px-3 border-r border-slate-200">Concepto</th>
                                <th class="py-2 px-3 border-r border-slate-200 text-center">Días Efectivos</th>
                                <th class="py-2 px-3 border-r border-slate-200 text-right">Alícuota Diaria</th>
                                <th class="py-2 px-3 border-r border-slate-200 text-right">Deducción Inasist.</th>
                                <th class="py-2 px-3 text-right">Total a Pagar (Bs.)</th>
                            </tr>
                        </thead>
                        <tbody class="font-mono text-xs">
                            <tr>
                                <td class="py-2.5 px-3 border-r border-slate-200 font-sans font-semibold">
                                    Bono de Alimentación Mensual (Período <?= e((string)$r['periodo']) ?>)
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-center"><?= (float)$r['dias_efectivos'] ?> días</td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-right"><?= e(formato_ves((float)$r['valor_diario'])) ?></td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-right"><?= e(formato_ves((float)$r['monto_descuento'])) ?></td>
                                <td class="py-2.5 px-3 text-right font-bold text-sm"><?= e(formato_ves((float)$r['total_cestaticket'])) ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Cajas de Firma, Cédula, Fecha y Huella Dactilar -->
                <div class="grid grid-cols-3 gap-6 pt-6 mt-4 border-t border-slate-300 text-xs">
                    <div class="text-center">
                        <div class="h-14 border-b border-slate-200 mb-1"></div>
                        <span class="font-bold uppercase block">Por la Empresa</span>
                        <span class="text-[10px] text-slate-600"><?= e((string)$empresa['razon_social']) ?></span>
                    </div>

                    <div class="text-center">
                        <div class="h-14 border-b border-slate-200 mb-1"></div>
                        <span class="font-bold uppercase block">Recibí Conforme — Firma del Trabajador(a)</span>
                        <span class="text-[10px] font-mono text-slate-600"><?= e((string)$r['nombre_completo']) ?> • C.I. <?= e((string)$r['cedula']) ?></span>
                    </div>

                    <div class="flex items-center justify-center">
                        <div class="w-24 h-20 border border-slate-600 rounded flex items-end justify-center pb-1 text-[9px] uppercase text-slate-500">
                            Huella / Sello
                        </div>
                    </div>
                </div>
                <?php require ROOT_PATH . '/views/layouts/pie_reporte.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
