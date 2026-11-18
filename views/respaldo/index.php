<?php
declare(strict_types=1);

use Core\Csrf;

$resumen = $resumenBd ?? ['database' => 'juris_contable', 'total_tablas' => 0, 'total_filas' => 0, 'total_bytes' => 0, 'tablas' => []];
$listaRespaldos = $respaldos ?? [];

$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    }
    return $bytes . ' B';
};
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO DEL MÓDULO -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-700 uppercase tracking-wider font-semibold">
                <span>Módulo 5 • Administración y Seguridad</span>
                <span>•</span>
                <span>Solo Administrador</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-0.5">
                Respaldo y Restauración de Base de Datos
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Genere copias de seguridad completas en formato <code>.sql</code> compatibles con phpMyAdmin y restaure el sistema ante cualquier eventualidad.
            </p>
        </div>

        <button type="button" id="btnGenerarRespaldo" onclick="generarRespaldo()"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold text-white bg-emerald-700 hover:bg-emerald-800 shadow-sm transition-all shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Generar Respaldo Completo (.SQL)
        </button>
    </div>

    <!-- TARJETAS RESUMEN -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Base de Datos Activa</p>
            <p class="mt-1.5 text-lg font-mono font-bold text-emerald-800"><?= e($resumen['database']) ?></p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tablas del Sistema</p>
            <p class="mt-1.5 text-lg font-mono font-bold text-slate-900"><?= (int)$resumen['total_tablas'] ?> tablas</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Registros Totales (Aprox.)</p>
            <p class="mt-1.5 text-lg font-mono font-bold text-slate-900"><?= number_format((int)$resumen['total_filas'], 0, ',', '.') ?></p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tamaño en Disco / Backups</p>
            <p class="mt-1.5 text-lg font-mono font-bold text-slate-900">
                <?= e($formatBytes((int)$resumen['total_bytes'])) ?>
                <span class="text-xs font-normal text-slate-500">• <?= count($listaRespaldos) ?> copias</span>
            </p>
        </div>
    </div>

    <!-- PANEL DE RESTAURACIÓN DESDE ARCHIVO .SQL -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Restaurar desde Archivo Externo (.SQL)</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Suba un archivo <code>.sql</code> generado previamente por Juris Contable o phpMyAdmin. <strong class="text-rose-700">Precaución:</strong> sobrescribirá los datos actuales.
                </p>
            </div>

            <form id="formRestaurarArchivo" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                <input type="file" name="archivo_sql" id="inputArchivoSql" accept=".sql" required
                       class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-emerald-200 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100">
                <button type="submit"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white shadow-sm transition-colors">
                    Restaurar Base de Datos
                </button>
            </form>
        </div>
    </div>

    <!-- HISTORIAL DE RESPALDOS EN EL SERVIDOR -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Respaldos Almacenados en el Servidor</h2>
                <p class="text-xs text-slate-500">Directorio protegido: <code>storage/backups/</code></p>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <?= count($listaRespaldos) ?> archivos
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-5 font-semibold">Archivo de Respaldo</th>
                        <th class="py-3 px-4 font-semibold">Fecha de Creación</th>
                        <th class="py-3 px-4 font-semibold text-right">Tamaño</th>
                        <th class="py-3 px-5 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (!empty($listaRespaldos)): ?>
                        <?php foreach ($listaRespaldos as $b): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 px-5 font-mono font-semibold text-slate-900">
                                    <?= e($b['archivo']) ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600">
                                    <?= e($b['fecha']) ?>
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700">
                                    <?= e($formatBytes((int)$b['bytes'])) ?>
                                </td>
                                <td class="py-3 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <form method="POST" action="<?= e(route_url('respaldo', 'descargar')) ?>" class="inline">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="archivo" value="<?= e($b['archivo']) ?>">
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition-colors">
                                                Descargar .SQL
                                            </button>
                                        </form>
                                        <button type="button"
                                                onclick="restaurarDesdeServidor(<?= e(json_encode($b['archivo'])) ?>)"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition-colors">
                                            Restaurar
                                        </button>
                                        <button type="button"
                                                onclick="eliminarRespaldo(<?= e(json_encode($b['archivo'])) ?>)"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition-colors">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="py-10 text-center text-slate-500">
                                Aún no se han generado respaldos. Haga clic en <strong>Generar Respaldo Completo (.SQL)</strong> para crear el primero.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- DETALLE DE TABLAS -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
            <h2 class="text-sm font-bold text-slate-900">Estado de Tablas en <?= e($resumen['database']) ?></h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase text-slate-600">
                    <tr>
                        <th class="py-2.5 px-5 font-semibold">Tabla</th>
                        <th class="py-2.5 px-4 font-semibold">Motor</th>
                        <th class="py-2.5 px-4 font-semibold text-right">Filas (Aprox.)</th>
                        <th class="py-2.5 px-5 font-semibold text-right">Tamaño</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($resumen['tablas'] as $t): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-5 font-mono text-slate-800"><?= e($t['nombre']) ?></td>
                            <td class="py-2 px-4 font-mono text-slate-500"><?= e($t['motor']) ?></td>
                            <td class="py-2 px-4 text-right font-mono text-slate-700"><?= number_format((int)$t['filas'], 0, ',', '.') ?></td>
                            <td class="py-2 px-5 text-right font-mono text-slate-700"><?= e($formatBytes((int)$t['bytes'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
(() => {
    const getMeta  = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || '';
    const BASE_URL = getMeta('base-url');
    const CSRF     = getMeta('csrf-token');

    async function postJson(url, body) {
        const isForm = body instanceof FormData;
        if (isForm && !body.has('csrf_token')) {
            body.append('csrf_token', CSRF);
        }
        const res = await fetch(url, {
            method: 'POST',
            headers: isForm ? { 'X-CSRF-Token': CSRF } : { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: isForm ? body : JSON.stringify(body)
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || 'Error en la operación.');
        return data;
    }

    window.generarRespaldo = async () => {
        const btn = document.getElementById('btnGenerarRespaldo');
        if (btn) btn.disabled = true;
        try {
            const res = await postJson(`${BASE_URL}/index.php?c=respaldo&a=generar`, {});
            await Swal.fire({ icon: 'success', title: 'Respaldo Generado', text: res.message });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        } finally {
            if (btn) btn.disabled = false;
        }
    };

    window.eliminarRespaldo = async (archivo) => {
        const conf = await Swal.fire({
            icon: 'warning',
            title: '¿Eliminar respaldo?',
            text: `Se eliminará permanentemente ${archivo} del servidor.`,
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });
        if (!conf.isConfirmed) return;

        try {
            const res = await postJson(`${BASE_URL}/index.php?c=respaldo&a=eliminar`, { archivo });
            await Swal.fire({ icon: 'success', title: 'Eliminado', text: res.message, timer: 1500, showConfirmButton: false });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    };

    window.restaurarDesdeServidor = async (archivo) => {
        const conf = await Swal.fire({
            icon: 'warning',
            title: '¿Restaurar Base de Datos?',
            text: `Esta acción sobrescribirá las tablas actuales con el contenido de ${archivo}.`,
            showCancelButton: true,
            confirmButtonColor: '#d97706',
            confirmButtonText: 'Sí, restaurar ahora',
            cancelButtonText: 'Cancelar'
        });
        if (!conf.isConfirmed) return;

        const formData = new FormData();
        formData.append('archivo', archivo);

        try {
            const res = await postJson(`${BASE_URL}/index.php?c=respaldo&a=restaurar`, formData);
            await Swal.fire({ icon: 'success', title: 'Restauración Exitosa', text: res.message });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error al Restaurar', text: err.message });
        }
    };

    document.getElementById('formRestaurarArchivo')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const conf = await Swal.fire({
            icon: 'warning',
            title: '¿Restaurar desde archivo .SQL?',
            text: 'Esta acción reemplazará la base de datos actual con el archivo seleccionado.',
            showCancelButton: true,
            confirmButtonColor: '#d97706',
            confirmButtonText: 'Sí, restaurar',
            cancelButtonText: 'Cancelar'
        });
        if (!conf.isConfirmed) return;

        try {
            const res = await postJson(`${BASE_URL}/index.php?c=respaldo&a=restaurar`, new FormData(e.target));
            await Swal.fire({ icon: 'success', title: 'Restauración Exitosa', text: res.message });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error al Restaurar', text: err.message });
        }
    });
})();
</script>
