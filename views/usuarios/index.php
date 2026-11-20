<?php
declare(strict_types=1);

$totalUsuarios   = count($usuarios);
$totalAdmins     = 0;
$totalContadores = 0;
$totalActivos    = 0;

foreach ($usuarios as $u) {
    if ($u['rol'] === 'admin') {
        $totalAdmins++;
    } else {
        $totalContadores++;
    }
    if ((int)$u['activo'] === 1) {
        $totalActivos++;
    }
}
?>
<!-- Cabecera del Módulo de Usuarios y Permisos -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-600 mb-1">
            <span>Administración del Servidor Local</span>
            <span>•</span>
            <span class="font-mono">Control de Acceso y Roles</span>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
            Gestión de Usuarios, Correos, Contraseñas y Permisos
        </h2>
        <p class="text-sm text-slate-600 mt-0.5">
            Cree nuevos administradores, contadores o asistentes con permisos por módulo, y edite el correo o contraseña de cualquier usuario (incluida su propia cuenta).
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <button type="button"
                onclick="window.JurisUsuarios?.abrirModalUsuario(<?= (int)$usuarioActualId ?>)"
                class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            Editar Mi Correo / Contraseña
        </button>

        <button type="button"
                onclick="window.JurisUsuarios?.abrirModalUsuario(0)"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Usuario
        </button>
    </div>
</div>

<!-- Tarjetas de Resumen -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Usuarios</span>
        <div class="mt-2 text-2xl font-bold font-mono text-slate-900"><?= (int)$totalUsuarios ?></div>
        <p class="text-[11px] text-slate-500 mt-1">Cuentas registradas en el servidor</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Administradores</span>
        <div class="mt-2 text-2xl font-bold font-mono text-emerald-600"><?= (int)$totalAdmins ?></div>
        <p class="text-[11px] text-slate-500 mt-1">Acceso total y gestión de usuarios</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Contadores / Asistentes</span>
        <div class="mt-2 text-2xl font-bold font-mono text-sky-600"><?= (int)$totalContadores ?></div>
        <p class="text-[11px] text-slate-500 mt-1">Operadores con permisos asignados</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cuentas Activas</span>
        <div class="mt-2 text-2xl font-bold font-mono text-amber-600"><?= (int)$totalActivos ?></div>
        <p class="text-[11px] text-slate-500 mt-1">Habilitadas para iniciar sesión</p>
    </div>
</div>

<!-- Tabla de Usuarios -->
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <th class="py-3.5 px-4">Usuario / Correo Electrónico</th>
                    <th class="py-3.5 px-4">Rol</th>
                    <th class="py-3.5 px-4">Permisos Habilitados</th>
                    <th class="py-3.5 px-4">Último Acceso</th>
                    <th class="py-3.5 px-4 text-center">Estado</th>
                    <th class="py-3.5 px-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-xs">
                <?php foreach ($usuarios as $u): ?>
                    <?php
                    $esMismoUsuario = (int)$u['id'] === (int)$usuarioActualId;
                    $activo         = (int)$u['activo'] === 1;
                    $permisos       = $u['permisos'] ?? [];
                    $rolBadge = match ($u['rol']) {
                        'admin'    => 'bg-emerald-500/15 text-emerald-700 border-emerald-500/30',
                        'contador' => 'bg-sky-500/15 text-sky-700 border-sky-500/30',
                        default    => 'bg-amber-500/15 text-amber-700 border-amber-500/30',
                    };
                    $etiquetasPermisos = [
                        'empresas'     => 'Empresas',
                        'contabilidad' => 'Contabilidad',
                        'tributario'   => 'SENIAT',
                        'nomina'       => 'Nómina LOTTT',
                        'anular'       => 'Anular Docs.',
                        'usuarios'     => 'Usuarios',
                    ];
                    ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900"><?= e((string)$u['nombre']) ?></span>
                                <?php if ($esMismoUsuario): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-700 border border-emerald-500/30">
                                        Tú (Sesión Actual)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="font-mono text-[11px] text-slate-500 mt-0.5"><?= e((string)$u['email']) ?></div>
                        </td>

                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold uppercase border <?= $rolBadge ?>">
                                <?= e((string)$u['rol']) ?>
                            </span>
                        </td>

                        <td class="py-3.5 px-4">
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($etiquetasPermisos as $clave => $nombrePerm): ?>
                                    <?php if (!empty($permisos[$clave])): ?>
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-[10px] font-medium text-slate-700 border border-slate-200">
                                            <?= e($nombrePerm) ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </td>

                        <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                            <?= !empty($u['ultimo_acceso']) ? e(date('d/m/Y H:i', strtotime((string)$u['ultimo_acceso']))) : 'Nunca' ?>
                        </td>

                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border <?= $activo
                                ? 'bg-emerald-500/15 text-emerald-700 border-emerald-500/30'
                                : 'bg-rose-500/15 text-rose-700 border-rose-500/30' ?>">
                                <?= $activo ? 'ACTIVO' : 'INACTIVO' ?>
                            </span>
                        </td>

                        <td class="py-3.5 px-4 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button"
                                        onclick="window.JurisUsuarios?.abrirModalUsuario(<?= (int)$u['id'] ?>)"
                                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 transition">
                                    Editar / Clave
                                </button>

                                <?php if (!$esMismoUsuario): ?>
                                    <button type="button"
                                            onclick="window.JurisUsuarios?.cambiarEstadoUsuario(<?= (int)$u['id'] ?>, <?= $activo ? 0 : 1 ?>, '<?= e((string)$u['nombre']) ?>')"
                                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= $activo
                                                ? 'bg-rose-500/10 hover:bg-rose-600 hover:text-white text-rose-600'
                                                : 'bg-emerald-500/10 hover:bg-emerald-600 hover:text-white text-emerald-600' ?>">
                                        <?= $activo ? 'Inactivar' : 'Activar' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL: CREAR / EDITAR USUARIO, CORREO, CONTRASEÑA Y PERMISOS -->
<div id="modalUsuarioForm" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden my-8">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <div>
                <h3 id="modalUsuarioTitulo" class="text-base font-bold text-slate-900">Nuevo Usuario</h3>
                <p class="text-xs text-slate-500">Configure credenciales de acceso y permisos por módulo</p>
            </div>
            <button type="button" onclick="window.JurisUsuarios?.cerrarModalUsuario()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="formUsuarioCrud" class="p-6 space-y-4">
            <input type="hidden" id="usr_id" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre Completo *</label>
                    <input type="text" id="usr_nombre" required placeholder="Lic. Ana María Pérez"
                           class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Correo Electrónico *</label>
                    <input type="email" id="usr_email" required placeholder="usuario@juriscontable.com"
                           class="w-full px-3.5 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Rol del Usuario *</label>
                    <select id="usr_rol" onchange="window.JurisUsuarios?.aplicarPermisosPorRol()"
                            class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                        <option value="admin">Administrador (Acceso Total)</option>
                        <option value="contador">Contador</option>
                        <option value="asistente">Asistente Contable</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Contraseña <span id="lblPassRequerida" class="text-rose-500">*</span>
                    </label>
                    <input type="password" id="usr_password" placeholder="Mínimo 6 caracteres"
                           class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                    <span id="lblPassOpcional" class="hidden text-[10px] text-slate-500 mt-0.5 block">Déjalo vacío para mantener la actual</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Estado de Cuenta</label>
                    <select id="usr_activo"
                            class="w-full px-3.5 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                        <option value="1">Activo (Puede entrar)</option>
                        <option value="0">Inactivo (Bloqueado)</option>
                    </select>
                </div>
            </div>

            <!-- Permisos Granulares por Módulo -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <p class="text-xs font-bold text-slate-800 mb-2.5">
                    Permisos por Módulo (Personalizable para Contadores y Asistentes)
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="perm_empresas" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="text-slate-700">Gestionar Empresas Clientes</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="perm_contabilidad" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="text-slate-700">Contabilidad, Asientos y Libros</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="perm_tributario" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="text-slate-700">Módulo Tributario SENIAT (IVA / ISLR)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="perm_nomina" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="text-slate-700">Nómina LOTTT, Cestaticket y Recibos</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="perm_anular" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="text-slate-700">Anular Asientos, Retenciones o Nóminas</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button type="button" onclick="window.JurisUsuarios?.cerrarModalUsuario()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30">
                    Guardar Usuario
                </button>
            </div>
        </form>
    </div>
</div>
