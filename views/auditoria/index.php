<?php
declare(strict_types=1);

$rows       = $resultado['rows'] ?? [];
$total      = (int)($resultado['total'] ?? 0);
$page       = (int)($resultado['page'] ?? 1);
$totalPages = (int)($resultado['total_pages'] ?? 1);

$badgeEvento = static function (string $tipo): array {
    return match ($tipo) {
        'CREACION'              => ['bg-emerald-50 text-emerald-800 border-emerald-200', 'Creación'],
        'ACTUALIZACION'         => ['bg-sky-50 text-sky-800 border-sky-200', 'Actualización'],
        'ELIMINACION'           => ['bg-rose-50 text-rose-800 border-rose-200', 'Eliminación'],
        'IMPRESION_EXPORTACION' => ['bg-violet-50 text-violet-800 border-violet-200', 'Impresión / Export.'],
        'AUTENTICACION'         => ['bg-amber-50 text-amber-800 border-amber-200', 'Autenticación'],
        'SISTEMA'               => ['bg-indigo-50 text-indigo-800 border-indigo-200', 'Sistema'],
        default                 => ['bg-slate-100 text-slate-700 border-slate-200', 'Consulta'],
    };
};

$buildQueryUrl = static function (array $overrides = []) use ($filtros): string {
    $params = array_merge($filtros, $overrides);
    $clean = [];
    foreach ($params as $k => $v) {
        if ($v !== '' && $v !== 0 && $v !== null) {
            $clean[$k] = $v;
        }
    }
    return route_url('auditoria', 'index', $clean);
};

$exportParams = array_filter($filtros, static fn($v) => $v !== '' && $v !== 0 && $v !== null);
?>

<div class="space-y-6">
    <!-- ENCABEZADO -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-mono font-bold uppercase tracking-widest text-emerald-700">
                Módulo 5 • Seguridad y Trazabilidad Total
            </span>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">Auditoría y Bitácora del Sistema</h1>
            <p class="text-sm text-slate-600 mt-1">
                Registro automático e inalterable de todas las acciones ejecutadas en Juris Contable, quién las realizó, cuándo y desde qué equipo.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="<?= e(route_url('auditoria', 'exportarCsv', $exportParams)) ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exportar Bitácora (CSV / Excel)
            </a>
        </div>
    </div>

    <!-- KPI RESUMEN -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Eventos Filtrados</p>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-1"><?= number_format((int)($resumen['total'] ?? 0), 0, ',', '.') ?></p>
            <p class="text-xs text-slate-500 mt-1">Total de registros que coinciden</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Cambios y Operaciones</p>
            <p class="text-2xl font-bold font-mono text-emerald-700 mt-1"><?= number_format((int)($resumen['cambios'] ?? 0), 0, ',', '.') ?></p>
            <p class="text-xs text-slate-500 mt-1">Creaciones, ediciones y eliminaciones</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-violet-700">Impresiones y Exportaciones</p>
            <p class="text-2xl font-bold font-mono text-violet-700 mt-1"><?= number_format((int)($resumen['impresiones'] ?? 0), 0, ',', '.') ?></p>
            <p class="text-xs text-slate-500 mt-1">Reportes PDF, libros y descargas</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Sesiones / Usuarios</p>
            <p class="text-2xl font-bold font-mono text-amber-700 mt-1">
                <?= number_format((int)($resumen['autenticacion'] ?? 0), 0, ',', '.') ?>
                <span class="text-xs font-normal text-slate-500">(<?= (int)($resumen['usuarios_distintos'] ?? 0) ?> usuarios)</span>
            </p>
            <p class="text-xs text-slate-500 mt-1">Inicios, intentos fallidos y cierres</p>
        </div>
    </div>

    <!-- PESTAÑAS RÁPIDAS POR TIPO -->
    <div class="flex flex-wrap items-center gap-2">
        <?php
        $quickTabs = [
            ''                      => 'Todos los Eventos',
            'CAMBIOS'               => 'Solo Cambios (Crear / Editar / Borrar)',
            'IMPRESION_EXPORTACION' => 'Impresiones y Exportaciones',
            'AUTENTICACION'         => 'Inicios y Cierres de Sesión',
            'CONSULTA'              => 'Consultas y Navegación',
        ];
        foreach ($quickTabs as $val => $label):
            $active = ($filtros['tipo_evento'] ?? '') === $val;
        ?>
            <a href="<?= e($buildQueryUrl(['tipo_evento' => $val, 'page' => 1])) ?>"
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border transition-colors <?= $active ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- FILTROS AVANZADOS -->
    <form method="GET" action="<?= e(base_url('index.php')) ?>" class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
        <input type="hidden" name="c" value="auditoria">
        <input type="hidden" name="a" value="index">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Buscar</label>
                <input type="text" name="q" value="<?= e($filtros['q']) ?>"
                       placeholder="Descripción, usuario, IP, acción o datos..."
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600">
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Usuario</label>
                <select name="usuario_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="0">Todos los usuarios</option>
                    <?php foreach ($opciones['usuarios'] ?? [] as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= (int)$filtros['usuario_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= e($u['nombre']) ?> (<?= e(strtoupper((string)$u['rol'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Empresa</label>
                <select name="empresa_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="0">Todas las empresas</option>
                    <?php foreach ($opciones['empresas'] ?? [] as $emp): ?>
                        <option value="<?= (int)$emp['id'] ?>" <?= (int)$filtros['empresa_id'] === (int)$emp['id'] ? 'selected' : '' ?>>
                            <?= e($emp['razon_social']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Módulo</label>
                <select name="modulo" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="">Todos los módulos</option>
                    <?php foreach ($opciones['modulos'] ?? [] as $m): ?>
                        <option value="<?= e($m['modulo']) ?>" <?= $filtros['modulo'] === $m['modulo'] ? 'selected' : '' ?>>
                            <?= e($m['modulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Tipo Evento</label>
                <select name="tipo_evento" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="">Todos</option>
                    <option value="CAMBIOS" <?= $filtros['tipo_evento'] === 'CAMBIOS' ? 'selected' : '' ?>>Cambios (Crear/Editar/Borrar)</option>
                    <option value="CREACION" <?= $filtros['tipo_evento'] === 'CREACION' ? 'selected' : '' ?>>Creación</option>
                    <option value="ACTUALIZACION" <?= $filtros['tipo_evento'] === 'ACTUALIZACION' ? 'selected' : '' ?>>Actualización</option>
                    <option value="ELIMINACION" <?= $filtros['tipo_evento'] === 'ELIMINACION' ? 'selected' : '' ?>>Eliminación</option>
                    <option value="IMPRESION_EXPORTACION" <?= $filtros['tipo_evento'] === 'IMPRESION_EXPORTACION' ? 'selected' : '' ?>>Impresión / Exportación</option>
                    <option value="AUTENTICACION" <?= $filtros['tipo_evento'] === 'AUTENTICACION' ? 'selected' : '' ?>>Autenticación</option>
                    <option value="SISTEMA" <?= $filtros['tipo_evento'] === 'SISTEMA' ? 'selected' : '' ?>>Sistema</option>
                    <option value="CONSULTA" <?= $filtros['tipo_evento'] === 'CONSULTA' ? 'selected' : '' ?>>Consulta</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Desde</label>
                <input type="date" name="fecha_desde" value="<?= e($filtros['fecha_desde']) ?>"
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Hasta</label>
                <input type="date" name="fecha_hasta" value="<?= e($filtros['fecha_hasta']) ?>"
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="sm:col-span-2 lg:col-span-4 flex items-end justify-end gap-2">
                <a href="<?= e(route_url('auditoria', 'index')) ?>"
                   class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-300 text-slate-700 hover:bg-slate-50">
                    Limpiar Filtros
                </a>
                <button type="submit"
                        class="px-5 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white shadow-sm cursor-pointer">
                    Aplicar Filtros
                </button>
            </div>
        </div>
    </form>

    <!-- TABLA DE BITÁCORA -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">
                Mostrando <?= count($rows) ?> de <?= number_format($total, 0, ',', '.') ?> eventos registrados
            </span>
            <span class="text-xs font-mono text-slate-500">
                Página <?= $page ?> de <?= $totalPages ?>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-4">Fecha / Hora</th>
                        <th class="py-3 px-4">Usuario</th>
                        <th class="py-3 px-4">Empresa</th>
                        <th class="py-3 px-4">Módulo / Tipo</th>
                        <th class="py-3 px-4">Acción Realizada</th>
                        <th class="py-3 px-4 text-center">HTTP / IP</th>
                        <th class="py-3 px-4 text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $r): ?>
                            <?php [$badgeCls, $badgeLbl] = $badgeEvento((string)$r['tipo_evento']); ?>
                            <tr class="hover:bg-slate-50 align-top">
                                <td class="py-3 px-4 font-mono whitespace-nowrap text-slate-700">
                                    <div class="font-semibold text-slate-900"><?= e(date('d/m/Y', strtotime((string)$r['created_at']))) ?></div>
                                    <div class="text-[11px] text-slate-500"><?= e(date('H:i:s', strtotime((string)$r['created_at']))) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-900"><?= e($r['usuario_nombre']) ?></div>
                                    <div class="text-[11px] text-slate-500">
                                        <?= e($r['usuario_email'] ?: 'Sin correo') ?>
                                        <?php if (!empty($r['usuario_rol'])): ?>
                                            <span class="ml-1 px-1.5 py-0.2 rounded bg-slate-100 border border-slate-200 font-mono uppercase text-[10px] text-slate-700">
                                                <?= e($r['usuario_rol']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-700 max-w-[180px] truncate" title="<?= e($r['empresa_nombre'] ?? '') ?>">
                                    <?= e($r['empresa_nombre'] ?: '—') ?>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-semibold text-slate-800"><?= e($r['modulo']) ?></div>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border <?= $badgeCls ?>">
                                        <?= e($badgeLbl) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-slate-900 font-medium"><?= e($r['descripcion']) ?></div>
                                    <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                                        <?= e($r['metodo_http']) ?> • <?= e($r['controlador']) ?> → <?= e($r['accion']) ?>
                                        <?php if (!empty($r['entidad_id'])): ?>
                                            • ID: <?= e($r['entidad_id']) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-[11px]">
                                    <span class="px-1.5 py-0.5 rounded <?= (int)$r['codigo_http'] >= 400 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-50 text-emerald-800' ?>">
                                        <?= (int)$r['codigo_http'] ?>
                                    </span>
                                    <div class="text-slate-500 mt-1"><?= e($r['ip_address'] ?: '—') ?></div>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button type="button"
                                            onclick='verDetalleAuditoria(<?= json_encode([
                                                "id"          => (int)$r["id"],
                                                "fecha"       => $r["created_at"],
                                                "usuario"     => $r["usuario_nombre"] . ($r["usuario_email"] ? " (" . $r["usuario_email"] . ")" : ""),
                                                "rol"         => $r["usuario_rol"] ?? "—",
                                                "empresa"     => $r["empresa_nombre"] ?? "—",
                                                "modulo"      => $r["modulo"],
                                                "tipo"        => $r["tipo_evento"],
                                                "ruta"        => $r["url_ruta"] ?? "",
                                                "ip"          => $r["ip_address"] ?? "—",
                                                "user_agent"  => $r["user_agent"] ?? "—",
                                                "descripcion" => $r["descripcion"],
                                                "datos_json"  => $r["datos_json"] ?? "",
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 cursor-pointer">
                                        Ver
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-500">
                                No se encontraron registros de auditoría para los filtros seleccionados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
                <span class="text-slate-600">Página <?= $page ?> de <?= $totalPages ?></span>
                <div class="flex items-center gap-1.5">
                    <?php if ($page > 1): ?>
                        <a href="<?= e($buildQueryUrl(['page' => $page - 1])) ?>"
                           class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 font-semibold">
                            ← Anterior
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e($buildQueryUrl(['page' => $page + 1])) ?>"
                           class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 font-semibold">
                            Siguiente →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL DETALLE DE AUDITORÍA -->
<div id="modalDetalleAuditoria" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Detalle Técnico del Evento #<span id="aud_det_id"></span></h3>
                <p id="aud_det_fecha" class="text-xs font-mono text-slate-500 mt-0.5"></p>
            </div>
            <button type="button" onclick="cerrarDetalleAuditoria()" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-200 cursor-pointer">
                ✕
            </button>
        </div>

        <div class="p-6 overflow-y-auto space-y-4 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                <div><span class="font-bold text-slate-600">Usuario:</span> <span id="aud_det_usuario" class="text-slate-900"></span></div>
                <div><span class="font-bold text-slate-600">Rol:</span> <span id="aud_det_rol" class="font-mono uppercase text-slate-900"></span></div>
                <div><span class="font-bold text-slate-600">Empresa:</span> <span id="aud_det_empresa" class="text-slate-900"></span></div>
                <div><span class="font-bold text-slate-600">IP:</span> <span id="aud_det_ip" class="font-mono text-slate-900"></span></div>
                <div class="sm:col-span-2"><span class="font-bold text-slate-600">Descripción:</span> <span id="aud_det_desc" class="text-slate-900"></span></div>
                <div class="sm:col-span-2 break-all"><span class="font-bold text-slate-600">Ruta URL:</span> <span id="aud_det_ruta" class="font-mono text-slate-700"></span></div>
                <div class="sm:col-span-2 break-all"><span class="font-bold text-slate-600">Navegador:</span> <span id="aud_det_ua" class="text-slate-600"></span></div>
            </div>

            <div>
                <p class="font-bold text-slate-700 mb-1.5">Datos Enviados (Payload Sanitizado):</p>
                <pre id="aud_det_json" class="bg-slate-900 text-emerald-300 p-4 rounded-xl text-[11px] font-mono overflow-x-auto max-h-72"></pre>
            </div>
        </div>

        <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
            <button type="button" onclick="cerrarDetalleAuditoria()"
                    class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold cursor-pointer">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
window.verDetalleAuditoria = (item) => {
    document.getElementById('aud_det_id').textContent = item.id;
    document.getElementById('aud_det_fecha').textContent = item.fecha;
    document.getElementById('aud_det_usuario').textContent = item.usuario;
    document.getElementById('aud_det_rol').textContent = item.rol;
    document.getElementById('aud_det_empresa').textContent = item.empresa;
    document.getElementById('aud_det_ip').textContent = item.ip;
    document.getElementById('aud_det_desc').textContent = item.descripcion;
    document.getElementById('aud_det_ruta').textContent = item.ruta || '—';
    document.getElementById('aud_det_ua').textContent = item.user_agent || '—';

    let formattedJson = 'Sin parámetros adicionales.';
    if (item.datos_json) {
        try {
            formattedJson = JSON.stringify(JSON.parse(item.datos_json), null, 2);
        } catch (e) {
            formattedJson = item.datos_json;
        }
    }
    document.getElementById('aud_det_json').textContent = formattedJson;
    document.getElementById('modalDetalleAuditoria').classList.remove('hidden');
};

window.cerrarDetalleAuditoria = () => {
    document.getElementById('modalDetalleAuditoria').classList.add('hidden');
};
</script>
