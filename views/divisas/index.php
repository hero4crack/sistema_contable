<?php
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

$emp         = $empresaActiva ?? [];
$usuarioRol  = (string)Session::get('usuario_rol', 'contador');
$puedeEditar = in_array($usuarioRol, ['admin', 'contador'], true);
$movs        = $relacion['movimientos'] ?? [];
$tot         = $relacion['totales'] ?? [];
$anterior    = $relacion['saldo_anterior'] ?? ['saldo_usd' => 0.0, 'saldo_bs' => 0.0];
$tasaActual  = (float)($emp['tasa_bcv_actual'] ?? 45.00);
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-700 uppercase tracking-wider font-semibold">
                <span>Módulo 2 • Control Cambiario VEN-NIF / NIC 21</span>
                <span>•</span>
                <span><?= e($emp['rif'] ?? '') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-0.5">
                Relación en Divisas (<?= $tipoCaja === 'CAJA' ? 'Caja Efectivo USD' : 'Banco en Divisas USD' ?>)
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Control de entradas/salidas en USD, conversión a Tasa BCV oficial y cálculo automático de Diferencial Cambiario al cierre.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= e(route_url('divisas', 'imprimir', ['tipo_caja' => $tipoCaja, 'periodo' => $periodo])) ?>"
               target="_blank"
               class="px-4 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 shadow-sm transition-colors">
                Imprimir Relación Oficial
            </a>
            <?php if ($puedeEditar): ?>
                <button type="button" onclick="abrirModalDivisa()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white shadow-sm transition-colors">
                    + Registrar Operación USD
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- PESTAÑAS BANCO / CAJA + FILTRO PERÍODO + AJUSTE BCV -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= e(route_url('divisas', 'index', ['tipo_caja' => 'BANCO', 'periodo' => $periodo])) ?>"
               class="px-4 py-2 rounded-xl text-xs font-semibold border transition-all <?= $tipoCaja === 'BANCO' ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' ?>">
                Banco en Divisas (USD)
            </a>
            <a href="<?= e(route_url('divisas', 'index', ['tipo_caja' => 'CAJA', 'periodo' => $periodo])) ?>"
               class="px-4 py-2 rounded-xl text-xs font-semibold border transition-all <?= $tipoCaja === 'CAJA' ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' ?>">
                Caja Efectivo (USD)
            </a>
        </div>

        <form method="POST" action="<?= e(route_url('divisas', 'index')) ?>" class="flex flex-wrap items-center gap-2">
            <?= Csrf::field() ?>
            <input type="hidden" name="tipo_caja" value="<?= e($tipoCaja) ?>">
            <input type="month" name="periodo" value="<?= e($periodo) ?>"
                   class="rounded-xl bg-white border border-slate-300 px-3 py-1.5 text-xs font-mono text-slate-900">
            <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300">
                Consultar Mes
            </button>
        </form>
    </div>

    <!-- TARJETAS RESUMEN Y AJUSTE DIFERENCIAL CAMBIARIO -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
            <span class="text-[11px] font-semibold uppercase text-slate-400 block">Saldo Actual USD</span>
            <p class="text-xl font-mono font-bold text-emerald-800 mt-1">$ <?= number_format((float)($tot['saldo_final_usd'] ?? 0), 2, ',', '.') ?></p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
            <span class="text-[11px] font-semibold uppercase text-slate-400 block">Expresión en Bolívares</span>
            <p class="text-xl font-mono font-bold text-slate-900 mt-1">Bs. <?= number_format((float)($tot['saldo_final_bs'] ?? 0), 2, ',', '.') ?></p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
            <span class="text-[11px] font-semibold uppercase text-slate-400 block">Diferencial Cambiario del Mes</span>
            <p class="text-xl font-mono font-bold text-emerald-700 mt-1">Bs. <?= number_format((float)($tot['diferencial_realizado'] ?? 0), 2, ',', '.') ?></p>
        </div>
        <?php if ($puedeEditar): ?>
        <div class="rounded-2xl bg-emerald-50/70 border border-emerald-200 p-4 flex flex-col justify-between gap-2">
            <label class="text-[11px] font-bold uppercase text-emerald-900 block">Tasa BCV Cierre de Mes</label>
            <div class="flex items-center gap-2">
                <input type="number" step="0.0001" min="0.01" id="div_tasa_cierre" value="<?= number_format($tasaActual, 4, '.', '') ?>"
                       class="w-full rounded-lg bg-white border border-emerald-300 px-2.5 py-1 text-xs font-mono text-slate-900">
                <button type="button" onclick="calcularAjusteCierre()"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white shrink-0">
                    Ajustar
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- TABLA RELACIÓN EN DIVISAS -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase text-slate-600">
                    <tr>
                        <th rowspan="2" class="py-2.5 px-3 border-r border-slate-200">Fecha</th>
                        <th colspan="3" class="py-1.5 px-3 text-center border-r border-slate-200 bg-emerald-50/70 text-emerald-900">Moneda Extranjera (USD)</th>
                        <th rowspan="2" class="py-2.5 px-3 text-right border-r border-slate-200">Tasa BCV</th>
                        <th colspan="3" class="py-1.5 px-3 text-center border-r border-slate-200 bg-slate-100 text-slate-800">Expresión en Bolívares (Bs.)</th>
                        <th rowspan="2" class="py-2.5 px-3 text-right border-r border-slate-200">Dif. Cambio</th>
                        <th rowspan="2" class="py-2.5 px-3">Descripción</th>
                        <th rowspan="2" class="py-2.5 px-3 text-right">Acción</th>
                    </tr>
                    <tr class="border-t border-slate-200">
                        <th class="py-1.5 px-2 text-right bg-emerald-50/70">Debe</th>
                        <th class="py-1.5 px-2 text-right bg-emerald-50/70">Haber</th>
                        <th class="py-1.5 px-2 text-right border-r border-slate-200 bg-emerald-50/70">Saldo</th>
                        <th class="py-1.5 px-2 text-right bg-slate-100">Debe</th>
                        <th class="py-1.5 px-2 text-right bg-slate-100">Haber</th>
                        <th class="py-1.5 px-2 text-right border-r border-slate-200 bg-slate-100">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr class="bg-slate-50 font-semibold text-slate-700">
                        <td class="py-2 px-3 font-mono"><?= e($periodo . '-01') ?></td>
                        <td class="py-2 px-2 text-right font-mono">—</td>
                        <td class="py-2 px-2 text-right font-mono">—</td>
                        <td class="py-2 px-2 text-right font-mono border-r border-slate-200"><?= number_format((float)$anterior['saldo_usd'], 2, ',', '.') ?></td>
                        <td class="py-2 px-3 text-right font-mono border-r border-slate-200">—</td>
                        <td class="py-2 px-2 text-right font-mono">—</td>
                        <td class="py-2 px-2 text-right font-mono">—</td>
                        <td class="py-2 px-2 text-right font-mono border-r border-slate-200"><?= number_format((float)$anterior['saldo_bs'], 2, ',', '.') ?></td>
                        <td class="py-2 px-3 text-right font-mono border-r border-slate-200">—</td>
                        <td class="py-2 px-3 uppercase">Saldo Inicial del Período</td>
                        <td></td>
                    </tr>

                    <?php if (!empty($movs)): ?>
                        <?php foreach ($movs as $m): ?>
                            <tr class="<?= (int)$m['es_ajuste_cambiario'] === 1 ? 'bg-emerald-50/60 font-semibold' : 'hover:bg-slate-50' ?>">
                                <td class="py-2.5 px-3 font-mono whitespace-nowrap"><?= e(date('d/m/Y', strtotime($m['fecha_operacion']))) ?></td>
                                <td class="py-2.5 px-2 text-right font-mono text-emerald-700"><?= $m['debe_usd'] > 0 ? number_format($m['debe_usd'], 2, ',', '.') : '—' ?></td>
                                <td class="py-2.5 px-2 text-right font-mono text-rose-700"><?= $m['haber_usd'] > 0 ? number_format($m['haber_usd'], 2, ',', '.') : '—' ?></td>
                                <td class="py-2.5 px-2 text-right font-mono font-bold border-r border-slate-200"><?= number_format($m['saldo_usd'], 2, ',', '.') ?></td>
                                <td class="py-2.5 px-3 text-right font-mono border-r border-slate-200"><?= number_format($m['tasa_bcv'], 4, ',', '.') ?></td>
                                <td class="py-2.5 px-2 text-right font-mono text-emerald-700"><?= $m['debe_bs'] > 0 ? number_format($m['debe_bs'], 2, ',', '.') : '—' ?></td>
                                <td class="py-2.5 px-2 text-right font-mono text-rose-700"><?= $m['haber_bs'] > 0 ? number_format($m['haber_bs'], 2, ',', '.') : '—' ?></td>
                                <td class="py-2.5 px-2 text-right font-mono font-bold border-r border-slate-200"><?= number_format($m['saldo_bs'], 2, ',', '.') ?></td>
                                <td class="py-2.5 px-3 text-right font-mono text-emerald-800 border-r border-slate-200">
                                    <?= $m['diferencial_realizado'] != 0 ? number_format($m['diferencial_realizado'], 2, ',', '.') : '—' ?>
                                </td>
                                <td class="py-2.5 px-3"><?= e($m['descripcion']) ?></td>
                                <td class="py-2.5 px-3 text-right">
                                    <?php if ($puedeEditar): ?>
                                        <button type="button" onclick="eliminarMovDivisa(<?= (int)$m['id'] ?>)"
                                                class="px-2 py-1 rounded text-[11px] bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200">
                                            Quitar
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="py-8 text-center text-slate-500">
                                No hay operaciones en divisas registradas para <?= e($periodo) ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL REGISTRAR OPERACIÓN USD -->
<div id="modalDivisaForm" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Registrar Operación en Divisas (USD)</h3>
            <button type="button" onclick="cerrarModalDivisa()" class="p-2 text-slate-500 hover:text-slate-900">✕</button>
        </div>

        <form id="formDivisa" class="p-6 space-y-4">
            <input type="hidden" name="tipo_caja" value="<?= e($tipoCaja) ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Fecha de Operación</label>
                    <input type="date" name="fecha_operacion" value="<?= date('Y-m-d') ?>" required
                           class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tasa BCV (Bs./USD)</label>
                    <input type="number" step="0.0001" min="0.01" name="tasa_bcv" value="<?= number_format($tasaActual, 4, '.', '') ?>" required
                           class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Descripción</label>
                    <input type="text" name="descripcion" required maxlength="255" placeholder="Compra de divisas / Pago factura / Venta"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Entrada / Debe (USD)</label>
                    <input type="number" step="0.01" min="0" name="debe_usd" value="0.00"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Salida / Haber (USD)</label>
                    <input type="number" step="0.01" min="0" name="haber_usd" value="0.00"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3 py-2 text-xs font-mono">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="cerrarModalDivisa()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-300 text-slate-700">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">Guardar Operación</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const getMeta  = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || '';
    const BASE_URL = getMeta('base-url');
    const CSRF     = getMeta('csrf-token');

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || 'Error en la operación.');
        return data;
    }

    window.abrirModalDivisa = () => {
        const m = document.getElementById('modalDivisaForm');
        m.classList.remove('hidden');
        m.classList.add('flex');
    };
    window.cerrarModalDivisa = () => {
        const m = document.getElementById('modalDivisaForm');
        m.classList.add('hidden');
        m.classList.remove('flex');
    };

    document.getElementById('formDivisa')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const payload = Object.fromEntries(new FormData(e.target).entries());
            const res = await postJson(`${BASE_URL}/index.php?c=divisas&a=registrar`, payload);
            cerrarModalDivisa();
            await Swal.fire({ icon: 'success', title: 'Registrado', text: res.message, timer: 1500, showConfirmButton: false });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Validación', text: err.message });
        }
    });

    window.calcularAjusteCierre = async () => {
        const tasa = parseFloat(document.getElementById('div_tasa_cierre')?.value || '0');
        try {
            const res = await postJson(`${BASE_URL}/index.php?c=divisas&a=ajustarDiferencial`, {
                tipo_caja: <?= json_encode($tipoCaja) ?>,
                periodo: <?= json_encode($periodo) ?>,
                tasa_cierre_bcv: tasa
            });
            await Swal.fire({ icon: 'success', title: 'Diferencial Cambiario', text: res.message });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    };

    window.eliminarMovDivisa = async (id) => {
        try {
            await postJson(`${BASE_URL}/index.php?c=divisas&a=eliminar`, { id });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    };
})();
</script>
