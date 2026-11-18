<?php
declare(strict_types=1);

/**
 * Membrete Institucional Uniforme para Reportes, Libros Contables/Fiscales y Recibos.
 *
 * Variables esperadas (opcionales, con fallback seguro):
 * - array  $empresa / $empresaActiva : Datos de la empresa cliente activa
 * - string $tituloReporte            : Título principal del documento
 * - string $subtituloPeriodo         : Período fiscal, rango de fechas o referencia
 * - string $tituloReporteId          : ID HTML opcional para actualización dinámica por JS
 * - string $claseContenedor          : Clases extra para el contenedor (ej: 'hidden print:block mb-5')
 */

$empMembrete = $empresa ?? ($empresaActiva ?? []);
$razonSocialMem = (string)($empMembrete['razon_social'] ?? 'EMPRESA CLIENTE');
$rifMem         = (string)($empMembrete['rif'] ?? 'N/D');
$direccionMem   = (string)($empMembrete['direccion_fiscal'] ?? 'Dirección fiscal no especificada');
$telefonoMem    = (string)($empMembrete['telefono'] ?? '');
$logoPathMem    = trim((string)($empMembrete['logo_path'] ?? ''));

$tituloRep    = $tituloReporte ?? 'REPORTE INSTITUCIONAL';
$subtituloPer = $subtituloPeriodo ?? ('Período Fiscal: ' . (string)($empMembrete['periodo_fiscal_activo'] ?? date('Y-m')));
$tituloIdAttr = !empty($tituloReporteId) ? ' id="' . e($tituloReporteId) . '"' : '';
$claseExtra   = $claseContenedor ?? 'mb-5';

$inicialesMem = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $razonSocialMem), 0, 2) ?: 'JC');
?>
<div class="membrete-oficial bg-white border-b-2 border-emerald-700 pb-3.5 <?= e($claseExtra) ?>">
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
        <!-- Bloque Izquierdo: Logo + Datos Fiscales del Cliente -->
        <div class="flex items-start gap-3.5 min-w-0">
            <?php if ($logoPathMem !== ''): ?>
                <img src="<?= e(base_url($logoPathMem)) ?>"
                     alt="Logo <?= e($razonSocialMem) ?>"
                     class="w-14 h-14 object-contain rounded-lg border border-slate-200 bg-white p-1 shrink-0">
            <?php else: ?>
                <div class="w-14 h-14 rounded-lg bg-emerald-700 text-white font-bold text-base flex items-center justify-center shrink-0">
                    <?= e($inicialesMem) ?>
                </div>
            <?php endif; ?>

            <div class="min-w-0 leading-snug">
                <h2 class="text-sm sm:text-base font-bold text-slate-900 uppercase tracking-tight">
                    <?= e($razonSocialMem) ?>
                </h2>
                <p class="text-[11px] font-mono font-semibold text-emerald-800 mt-0.5">
                    RIF: <?= e($rifMem) ?>
                    <?php if (!empty($empMembrete['tipo_contribuyente'])): ?>
                        <span class="text-slate-400 mx-1">•</span>
                        <span class="text-slate-600 font-sans font-normal">Contribuyente <?= e($empMembrete['tipo_contribuyente']) ?></span>
                    <?php endif; ?>
                </p>
                <p class="text-[11px] text-slate-600 mt-0.5">
                    <?= e($direccionMem) ?>
                </p>
                <?php if ($telefonoMem !== ''): ?>
                    <p class="text-[11px] text-slate-500 font-mono">
                        Tel.: <?= e($telefonoMem) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bloque Derecho: Título del Reporte, Período y Emisión -->
        <div class="sm:text-right shrink-0 sm:max-w-[42%]">
            <div class="inline-block px-2.5 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-[10px] font-mono font-semibold text-emerald-800 uppercase tracking-wider mb-1">
                Documento Oficial
            </div>
            <h3<?= $tituloIdAttr ?> class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                <?= e($tituloRep) ?>
            </h3>
            <p class="text-xs font-medium text-slate-700 mt-0.5">
                <?= e($subtituloPer) ?>
            </p>
            <p class="text-[10px] font-mono text-slate-500 mt-1">
                Emitido: <?= date('d/m/Y H:i') ?>
            </p>
        </div>
    </div>
</div>
