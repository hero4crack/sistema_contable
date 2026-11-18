<?php
declare(strict_types=1);

/**
 * Pie Institucional Uniforme para Reportes, Libros y Recibos.
 */
$clasePieExtra = $clasePie ?? 'mt-6 pt-3 border-t border-slate-300';
?>
<div class="pie-reporte-oficial <?= e($clasePieExtra) ?> flex flex-col sm:flex-row items-center justify-between gap-2 text-[10px] text-slate-500">
    <div class="flex items-center gap-2">
        <span class="inline-block w-2 h-2 rounded-full bg-emerald-700"></span>
        <span class="font-medium text-slate-700">
            Documento emitido a través de Juris Contable — Sistema de Gestión Contable y Tributaria
        </span>
    </div>
    <span class="font-mono text-slate-400">
        Fecha de emisión: <?= date('d/m/Y H:i') ?>
    </span>
</div>
