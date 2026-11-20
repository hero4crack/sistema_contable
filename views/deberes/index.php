<?php
declare(strict_types=1);

use Core\Session;

$emp         = $empresaActiva ?? [];
$usuarioRol  = (string)Session::get('usuario_rol', 'contador');
$puedeEditar = in_array($usuarioRol, ['admin', 'contador'], true);
$tabActual   = $tab ?? 'detalle';

$conteos = [
    'VENCIDO'       => 0,
    'PRONTO_VENCER' => 0,
    'POR_VERIFICAR' => 0,
    'VIGENTE'       => 0,
];
foreach ($obligaciones as $o) {
    $est = $o['estatus_efectivo'] ?? 'POR_VERIFICAR';
    if (isset($conteos[$est])) {
        $conteos[$est]++;
    }
}

$badges = [
    'VIGENTE'       => ['label' => 'Vigente / Renovado', 'short' => 'VIGENTE', 'cls' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
    'POR_VERIFICAR' => ['label' => 'Por Verificar', 'short' => 'VERIFICAR', 'cls' => 'bg-amber-100 text-amber-800 border-amber-300'],
    'PRONTO_VENCER' => ['label' => 'Pronto a Vencer (≤ 30d)', 'short' => 'PRONTO', 'cls' => 'bg-orange-100 text-orange-800 border-orange-300'],
    'VENCIDO'       => ['label' => 'Vencido', 'short' => 'VENCIDO', 'cls' => 'bg-rose-100 text-rose-800 border-rose-300'],
    'NO_APLICA'     => ['label' => 'No Aplica / No Posee', 'short' => 'N/A', 'cls' => 'bg-slate-100 text-slate-600 border-slate-300'],
    'SIN_SOPORTE'   => ['label' => 'Sin Soporte', 'short' => 'S/S', 'cls' => 'bg-slate-100 text-slate-600 border-slate-300'],
];
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-700 uppercase tracking-wider font-semibold">
                <span>Módulo 3 • Cumplimiento Legal y Municipal</span>
                <span>•</span>
                <span><?= e($emp['rif'] ?? '') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-0.5">
                Control y Alertas de Deberes Formales
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Gestione renovaciones, adjunte soportes (PDF/Imagen) y consulte la matriz global de vencimientos de todos los clientes del escritorio.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 no-print">
            <a href="<?= e(route_url('deberes', 'index', ['tab' => 'detalle'])) ?>"
               class="px-4 py-2 rounded-xl text-xs font-semibold border transition-all <?= $tabActual === 'detalle' ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' ?>">
                Empresa Activa (Detalle)
            </a>
            <a href="<?= e(route_url('deberes', 'index', ['tab' => 'matriz'])) ?>"
               class="px-4 py-2 rounded-xl text-xs font-semibold border transition-all <?= $tabActual === 'matriz' ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' ?>">
                Matriz Global (Todos los Clientes)
            </a>
            <a href="<?= e(route_url('deberes', 'imprimir', ['tab' => $tabActual])) ?>" target="_blank"
               class="px-4 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 shadow-sm">
                Imprimir Reporte Oficial
            </a>
        </div>
    </div>

    <?php if ($tabActual === 'matriz'): ?>
        <!-- =================================================================== -->
        <!-- VISTA MATRIZ GLOBAL DE CLIENTES ACTIVOS (COMO LA HOJA DE CONTROL)   -->
        <!-- =================================================================== -->
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Control Global de Clientes Activos — Deberes Formales</h2>
                    <p class="text-xs text-slate-500">Vista consolidada de todas las empresas activas del escritorio contable.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-[10px] font-semibold">
                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-300">Vigente</span>
                    <span class="px-2 py-0.5 rounded bg-orange-100 text-orange-800 border border-orange-300">Pronto a vencer</span>
                    <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-300">Vencido</span>
                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-300">Por verificar</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-[10px]">
                    <thead class="bg-slate-50 border-b border-slate-200 uppercase text-slate-600">
                        <tr>
                            <th class="py-2.5 px-3 sticky left-0 bg-slate-50 z-10 min-w-[180px] border-r border-slate-200">Cliente / RIF</th>
                            <?php foreach ($obligacionesBase as $cod => $info): ?>
                                <th class="py-2 px-2 text-center min-w-[90px] border-r border-slate-200"><?= e($info['nombre']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($matrizGlobal as $fila): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-2 px-3 sticky left-0 bg-white z-10 border-r border-slate-200">
                                    <div class="font-bold text-slate-900"><?= e($fila['empresa']['razon_social']) ?></div>
                                    <div class="font-mono text-emerald-700"><?= e($fila['empresa']['rif']) ?></div>
                                </td>
                                <?php foreach ($obligacionesBase as $cod => $info): ?>
                                    <?php
                                    $item = $fila['obligaciones'][$cod] ?? null;
                                    $est  = $item['estatus_efectivo'] ?? 'POR_VERIFICAR';
                                    $bInf = $badges[$est] ?? $badges['POR_VERIFICAR'];
                                    $fv   = !empty($item['fecha_vencimiento']) ? date('d/m/y', strtotime((string)$item['fecha_vencimiento'])) : '';
                                    ?>
                                    <td class="py-1.5 px-1.5 text-center border-r border-slate-200">
                                        <span class="inline-block px-1.5 py-0.5 rounded border font-semibold <?= e($bInf['cls']) ?>">
                                            <?= e($fv !== '' ? $fv : $bInf['short']) ?>
                                        </span>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- =================================================================== -->
        <!-- VISTA DETALLE Y GESTIÓN OPERATIVA DE LA EMPRESA ACTIVA              -->
        <!-- =================================================================== -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <button type="button" onclick="filtrarEstado('VENCIDO')" class="text-left rounded-2xl bg-rose-50 hover:bg-rose-100/80 border border-rose-200 p-4 transition">
                <span class="text-[11px] font-bold uppercase text-rose-700 block">Vencidos</span>
                <p class="text-2xl font-mono font-bold text-rose-800 mt-1"><?= $conteos['VENCIDO'] ?></p>
            </button>
            <button type="button" onclick="filtrarEstado('PRONTO_VENCER')" class="text-left rounded-2xl bg-orange-50 hover:bg-orange-100/80 border border-orange-200 p-4 transition">
                <span class="text-[11px] font-bold uppercase text-orange-700 block">Pronto a Vencer (≤ 30 días)</span>
                <p class="text-2xl font-mono font-bold text-orange-800 mt-1"><?= $conteos['PRONTO_VENCER'] ?></p>
            </button>
            <button type="button" onclick="filtrarEstado('POR_VERIFICAR')" class="text-left rounded-2xl bg-amber-50 hover:bg-amber-100/80 border border-amber-200 p-4 transition">
                <span class="text-[11px] font-bold uppercase text-amber-700 block">Por Verificar</span>
                <p class="text-2xl font-mono font-bold text-amber-800 mt-1"><?= $conteos['POR_VERIFICAR'] ?></p>
            </button>
            <button type="button" onclick="filtrarEstado('')" class="text-left rounded-2xl bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 p-4 transition">
                <span class="text-[11px] font-bold uppercase text-emerald-700 block">Vigentes / Mostrar Todos</span>
                <p class="text-2xl font-mono font-bold text-emerald-800 mt-1"><?= $conteos['VIGENTE'] ?> <span class="text-xs font-normal text-emerald-700">/ <?= count($obligaciones) ?></span></p>
            </button>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse" id="tablaDeberesFormales">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase text-slate-600">
                        <tr>
                            <th class="py-3 px-4">Obligación y Acción Requerida</th>
                            <th class="py-3 px-3">Estatus</th>
                            <th class="py-3 px-3">Últ. Renovación</th>
                            <th class="py-3 px-3">Próx. Vencimiento</th>
                            <th class="py-3 px-3">Observación / Soporte</th>
                            <th class="py-3 px-4 text-right no-print">Gestión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($obligaciones as $ob): ?>
                            <?php
                            $estEf = $ob['estatus_efectivo'] ?? 'POR_VERIFICAR';
                            $bInfo = $badges[$estEf] ?? $badges['POR_VERIFICAR'];
                            ?>
                            <tr class="fila-deber hover:bg-slate-50 transition-colors" data-estatus="<?= e($estEf) ?>">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">
                                        <?= e($ob['nombre_obligacion']) ?>
                                        <span class="ml-1.5 text-[10px] font-mono font-normal text-slate-500">(<?= e($ob['periodicidad']) ?>)</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5"><?= e($ob['accion_requerida'] ?? '') ?></p>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border <?= e($bInfo['cls']) ?>">
                                        <?= e($bInfo['label']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-600 whitespace-nowrap">
                                    <?= !empty($ob['fecha_renovacion']) ? e(date('d/m/Y', strtotime((string)$ob['fecha_renovacion']))) : '—' ?>
                                </td>
                                <td class="py-3 px-3 font-mono font-semibold text-slate-900 whitespace-nowrap">
                                    <?= !empty($ob['fecha_vencimiento']) ? e(date('d/m/Y', strtotime((string)$ob['fecha_vencimiento']))) : '—' ?>
                                </td>
                                <td class="py-3 px-3 text-slate-600">
                                    <div><?= e($ob['observacion'] ?: '—') ?></div>
                                    <?php if (!empty($ob['soporte_path'])): ?>
                                        <a href="<?= e(base_url((string)$ob['soporte_path'])) ?>" target="_blank"
                                           class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 hover:underline mt-0.5">
                                            Ver Soporte Adjunto
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap no-print">
                                    <?php if ($puedeEditar): ?>
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button"
                                                    onclick='abrirRenovarRapido(<?= e(json_encode($ob)) ?>)'
                                                    class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-emerald-700 hover:bg-emerald-800 text-white transition-colors">
                                                Renovar
                                            </button>
                                            <button type="button"
                                                    onclick='editarObligacion(<?= e(json_encode($ob)) ?>)'
                                                    class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 transition-colors">
                                                Editar / Soporte
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL EDITAR / RENOVAR OBLIGACIÓN + SUBIR SOPORTE -->
<div id="modalDeberFormal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 no-print">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 id="tituloModalDeber" class="text-base font-bold text-slate-900">Actualizar Deber Formal</h3>
                <p id="accionModalDeber" class="text-[11px] text-emerald-700 mt-0.5"></p>
            </div>
            <button type="button" onclick="cerrarModalDeber()" class="p-2 text-slate-500 hover:text-slate-900">✕</button>
        </div>
        <form id="formDeberFormal" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" id="df_codigo" name="codigo_obligacion">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Estatus</label>
                    <select id="df_estatus" name="estatus" class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs">
                        <option value="VIGENTE">Vigente / Renovado</option>
                        <option value="POR_VERIFICAR">Por Verificar</option>
                        <option value="PRONTO_VENCER">Pronto a Vencer</option>
                        <option value="VENCIDO">Vencido</option>
                        <option value="NO_APLICA">No Aplica / No Posee</option>
                        <option value="SIN_SOPORTE">Sin Soporte</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Fecha de Renovación / Pago</label>
                    <input type="date" id="df_renovacion" name="fecha_renovacion" class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Próximo Vencimiento</label>
                    <input type="date" id="df_fecha" name="fecha_vencimiento" class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Observación / N° Planilla o Certificado</label>
                    <input type="text" id="df_obs" name="observacion" maxlength="255" placeholder="Ej. Pagado 2do trimestre / Certificado N° 4412"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Adjuntar Soporte (PDF, PNG, JPG — máx. 4 MB)</label>
                    <input type="file" name="soporte" id="df_soporte" accept=".pdf,.png,.jpg,.jpeg,.webp"
                           class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-emerald-200 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800">
                </div>
            </div>
            <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="cerrarModalDeber()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-300 text-slate-700">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const getMeta  = (n) => document.querySelector(`meta[name="${n}"]`)?.getAttribute('content') || '';
    const BASE_URL = getMeta('base-url');
    const CSRF     = getMeta('csrf-token');

    window.filtrarEstado = (est) => {
        document.querySelectorAll('#tablaDeberesFormales tbody tr.fila-deber').forEach(tr => {
            tr.classList.toggle('hidden', est !== '' && tr.dataset.estatus !== est);
        });
    };

    window.cerrarModalDeber = () => {
        const m = document.getElementById('modalDeberFormal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    };

    window.editarObligacion = (ob) => {
        document.getElementById('tituloModalDeber').textContent = ob.nombre_obligacion || 'Actualizar Deber Formal';
        document.getElementById('accionModalDeber').textContent = ob.accion_requerida || '';
        document.getElementById('df_codigo').value = ob.codigo_obligacion;
        document.getElementById('df_estatus').value = ob.estatus || 'POR_VERIFICAR';
        document.getElementById('df_renovacion').value = ob.fecha_renovacion || '';
        document.getElementById('df_fecha').value = ob.fecha_vencimiento || '';
        document.getElementById('df_obs').value = ob.observacion || '';
        const inpSop = document.getElementById('df_soporte');
        if (inpSop) inpSop.value = '';
        const m = document.getElementById('modalDeberFormal');
        m.classList.remove('hidden');
        m.classList.add('flex');
    };

    window.abrirRenovarRapido = (ob) => {
        editarObligacion(ob);
        document.getElementById('df_estatus').value = 'VIGENTE';
        document.getElementById('df_renovacion').value = new Date().toISOString().slice(0, 10);
    };

    document.getElementById('formDeberFormal')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const formData = new FormData(e.target);
            formData.append('csrf_token', CSRF);
            const res = await fetch(`${BASE_URL}/index.php?c=deberes&a=guardar`, {
                method: 'POST',
                headers: { 'X-CSRF-Token': CSRF },
                body: formData
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'Error al guardar.');
            cerrarModalDeber();
            await Swal.fire({ icon: 'success', title: 'Guardado', text: data.message, timer: 1400, showConfirmButton: false });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    });
})();
</script>
