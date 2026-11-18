<?php
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

$empresaActivaId = (int)($empresaActiva['id'] ?? Session::getEmpresaActivaId());
$usuarioRol      = (string)Session::get('usuario_rol', 'contador');
$puedeEditar     = in_array($usuarioRol, ['admin', 'contador'], true);
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO Y BOTÓN NUEVA EMPRESA -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-700 uppercase tracking-wider font-semibold">
                <span>Módulo 1 • Gestión Multi-Empresa</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-0.5">
                Directorio de Empresas Clientes
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Al registrar una nueva empresa se le clona automáticamente el catálogo base de 179 cuentas contables venezolanas.
            </p>
        </div>

        <?php if ($puedeEditar): ?>
            <button type="button"
                    onclick="window.JurisApp.abrirModalNuevaEmpresa()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-700 hover:bg-emerald-800 shadow-sm transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Empresa Cliente
            </button>
        <?php endif; ?>
    </div>

    <!-- BARRA DE BÚSQUEDA Y FILTROS -->
    <form method="POST" action="<?= e(route_url('empresa', 'index')) ?>" class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex flex-wrap items-center justify-between gap-3">
        <?= Csrf::field() ?>

        <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[260px]">
            <div class="relative flex-1 min-w-[220px]">
                <input type="text" name="q" value="<?= e($filtroBuscar ?? '') ?>"
                       placeholder="Buscar por RIF (J-40123456-7), Razón Social o Nombre Comercial..."
                       class="w-full bg-white border border-slate-300 focus:border-emerald-600 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="estado"
                    class="bg-white border border-slate-300 focus:border-emerald-600 rounded-xl px-3.5 py-2 text-xs text-slate-900 focus:outline-none">
                <option value="" <?= ($filtroEstado ?? '') === '' ? 'selected' : '' ?>>Todos los Estados</option>
                <option value="1" <?= ($filtroEstado ?? '') === '1' ? 'selected' : '' ?>>Solo Activas</option>
                <option value="0" <?= ($filtroEstado ?? '') === '0' ? 'selected' : '' ?>>Solo Inactivas</option>
            </select>

            <button type="submit"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors">
                Filtrar
            </button>

            <?php if (!empty($filtroBuscar) || ($filtroEstado ?? '') !== ''): ?>
                <a href="<?= e(route_url('empresa', 'index')) ?>"
                   class="text-xs text-slate-500 hover:text-slate-900 underline">
                    Limpiar filtros
                </a>
            <?php endif; ?>
        </div>

        <div class="text-xs font-mono text-slate-500">
            Mostrando <strong class="text-slate-900"><?= count($empresas ?? []) ?></strong> empresas registradas
        </div>
    </form>

    <!-- TABLA DE EMPRESAS CLIENTES -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="py-3.5 px-5 font-semibold">Contexto</th>
                        <th class="py-3.5 px-4 font-semibold">Empresa / RIF / Razón Social</th>
                        <th class="py-3.5 px-4 font-semibold">Condición SENIAT</th>
                        <th class="py-3.5 px-4 font-semibold text-center">Período Fiscal</th>
                        <th class="py-3.5 px-4 font-semibold text-center">Plan de Cuentas</th>
                        <th class="py-3.5 px-4 font-semibold text-center">Estado</th>
                        <th class="py-3.5 px-5 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs">
                    <?php if (!empty($empresas)): ?>
                        <?php foreach ($empresas as $item): ?>
                            <?php
                            $idEmp = (int)$item['id'];
                            $esActivaSesion = ($idEmp === $empresaActivaId);
                            $estaActivaBd   = ((int)$item['activo'] === 1);
                            $logoItem       = trim((string)($item['logo_path'] ?? ''));
                            $inicialItem    = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string)($item['razon_social'] ?? 'EC')), 0, 2) ?: 'EC');
                            ?>
                            <tr class="transition-colors <?= $esActivaSesion ? 'bg-emerald-50/70 hover:bg-emerald-50' : 'hover:bg-slate-50' ?>">
                                <!-- Columna Contexto Multi-Tenant -->
                                <td class="py-4 px-5 whitespace-nowrap">
                                    <?php if ($esActivaSesion): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                            En Sesión
                                        </span>
                                    <?php elseif ($estaActivaBd): ?>
                                        <button type="button"
                                                onclick="window.JurisApp.cambiarEmpresaActiva(<?= $idEmp ?>)"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-medium bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-300 hover:border-emerald-400 transition-all">
                                            Seleccionar
                                        </button>
                                    <?php else: ?>
                                        <span class="text-[11px] font-mono text-slate-400">Inactiva</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Logo, RIF y Razón Social -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <?php if ($logoItem !== ''): ?>
                                            <img src="<?= e(base_url($logoItem)) ?>"
                                                 alt="Logo <?= e($item['razon_social']) ?>"
                                                 class="w-10 h-10 rounded-lg object-contain bg-white border border-slate-200 p-1 shrink-0">
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0">
                                                <?= e($inicialItem) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono font-bold text-emerald-700"><?= e($item['rif']) ?></span>
                                                <?php if (!empty($item['nombre_comercial'])): ?>
                                                    <span class="text-[11px] text-slate-500">(<?= e($item['nombre_comercial']) ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="font-semibold text-slate-900 mt-0.5"><?= e($item['razon_social']) ?></p>
                                            <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1" title="<?= e($item['direccion_fiscal']) ?>">
                                                <?= e($item['direccion_fiscal']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Condición SENIAT -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <?php if ($item['tipo_contribuyente'] === 'Especial'): ?>
                                        <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            Especial
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-sky-50 text-sky-800 border border-sky-200">
                                            <?= e($item['tipo_contribuyente']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="mt-1 text-[11px] font-mono text-slate-500">
                                        Ret. IVA: <strong class="text-slate-800"><?= e($item['porcentaje_retencion_iva']) ?>%</strong>
                                    </div>
                                </td>

                                <!-- Período Fiscal -->
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-lg font-mono text-xs bg-slate-100 text-slate-800 border border-slate-200">
                                        <?= e($item['periodo_fiscal_activo']) ?>
                                    </span>
                                </td>

                                <!-- Plan de Cuentas Clonado -->
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <button type="button"
                                            onclick="window.JurisApp.abrirModalPlanCuentas(<?= $idEmp ?>)"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-mono text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                        </svg>
                                        <?= (int)$item['total_cuentas'] ?> cuentas
                                    </button>
                                </td>

                                <!-- Estado -->
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <?php if ($estaActivaBd): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Activa
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                            Inactiva
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Acciones CRUD -->
                                <td class="py-4 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <?php if ($puedeEditar): ?>
                                            <button type="button"
                                                    onclick="window.JurisApp.abrirModalEditarEmpresa(<?= $idEmp ?>)"
                                                    title="Editar datos fiscales y logotipo"
                                                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 transition-colors">
                                                Editar
                                            </button>

                                            <?php if ($estaActivaBd): ?>
                                                <button type="button"
                                                        onclick="window.JurisApp.cambiarEstadoEmpresa(<?= $idEmp ?>, 0, <?= e(json_encode($item['razon_social'])) ?>)"
                                                        title="Inactivar empresa"
                                                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition-colors">
                                                    Inactivar
                                                </button>
                                            <?php else: ?>
                                                <button type="button"
                                                        onclick="window.JurisApp.cambiarEstadoEmpresa(<?= $idEmp ?>, 1, <?= e(json_encode($item['razon_social'])) ?>)"
                                                        title="Reactivar empresa"
                                                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition-colors">
                                                    Activar
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                No se encontraron empresas clientes con los criterios seleccionados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL CREAR / EDITAR EMPRESA CLIENTE -->
<div id="modalEmpresaForm" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[92vh] flex flex-col overflow-hidden">
        <!-- Cabecera del Modal -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 id="tituloModalEmpresa" class="text-base font-bold text-slate-900">Registrar Nueva Empresa Cliente</h3>
                <p id="subtituloModalEmpresa" class="text-xs text-emerald-700 font-mono mt-0.5">
                    Clonación automática de 179 cuentas del Plan de Cuentas Base activada
                </p>
            </div>
            <button type="button" onclick="window.JurisApp.cerrarModalEmpresa()"
                    class="p-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-200/60 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Formulario -->
        <form id="formEmpresaCliente" enctype="multipart/form-data" class="p-6 overflow-y-auto space-y-5">
            <?= Csrf::field() ?>
            <input type="hidden" id="emp_id" name="id" value="0">

            <!-- Logotipo de la Empresa Cliente -->
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="w-16 h-16 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
                    <img id="emp_logo_preview" src="" alt="Vista previa del logo" class="hidden w-full h-full object-contain p-1">
                    <span id="emp_logo_placeholder" class="text-[11px] font-mono text-slate-400 text-center px-1">Sin Logo</span>
                </div>
                <div class="flex-1 min-w-0">
                    <label for="emp_logo" class="block text-xs font-semibold text-slate-800 mb-1">
                        Logotipo de la Empresa Cliente (Para Membretes e Impresión)
                    </label>
                    <input type="file" id="emp_logo" name="logo" accept=".png,.jpg,.jpeg,.webp"
                           class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-emerald-200 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    <p class="text-[11px] text-slate-500 mt-1">
                        Formatos permitidos: PNG, JPG, JPEG o WEBP (máximo 2 MB). Se mostrará en el membrete de reportes y recibos.
                    </p>
                    <label id="emp_eliminar_logo_wrap" class="hidden inline-flex items-center gap-2 mt-2 text-xs text-rose-700 cursor-pointer">
                        <input type="checkbox" id="emp_eliminar_logo" name="eliminar_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                        <span>Eliminar logotipo actual</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <!-- RIF -->
                <div class="sm:col-span-4">
                    <label for="emp_rif" class="block text-xs font-medium text-slate-700 mb-1">
                        RIF (Formato SENIAT) <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" id="emp_rif" name="rif" required maxlength="15"
                           placeholder="J-40123456-7"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm font-mono uppercase text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:outline-none">
                    <span class="text-[10px] text-slate-500 mt-0.5 block">Ej: J-12345678-9, V-12345678-0, G-20000000-1</span>
                </div>

                <!-- Razón Social -->
                <div class="sm:col-span-8">
                    <label for="emp_razon_social" class="block text-xs font-medium text-slate-700 mb-1">
                        Razón Social o Denominación Fiscal <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" id="emp_razon_social" name="razon_social" required maxlength="200"
                           placeholder="CORPORACIÓN VENEZOLANA DE SERVICIOS, C.A."
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:outline-none">
                </div>

                <!-- Nombre Comercial -->
                <div class="sm:col-span-6">
                    <label for="emp_nombre_comercial" class="block text-xs font-medium text-slate-700 mb-1">
                        Nombre Comercial / Siglas
                    </label>
                    <input type="text" id="emp_nombre_comercial" name="nombre_comercial" maxlength="150"
                           placeholder="CorpoServicios"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:outline-none">
                </div>

                <!-- Representante Legal -->
                <div class="sm:col-span-6">
                    <label for="emp_representante_legal" class="block text-xs font-medium text-slate-700 mb-1">
                        Representante Legal / Administrador
                    </label>
                    <input type="text" id="emp_representante_legal" name="representante_legal" maxlength="150"
                           placeholder="Nombre y Apellido"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:outline-none">
                </div>

                <!-- Dirección Fiscal -->
                <div class="sm:col-span-12">
                    <label for="emp_direccion_fiscal" class="block text-xs font-medium text-slate-700 mb-1">
                        Dirección Fiscal Completa <span class="text-rose-600">*</span>
                    </label>
                    <textarea id="emp_direccion_fiscal" name="direccion_fiscal" rows="2" required
                              placeholder="Av. Principal, Edificio / Torre, Piso, Oficina, Urbanización, Municipio y Estado"
                              class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:outline-none"></textarea>
                </div>

                <!-- Teléfono y Correo -->
                <div class="sm:col-span-6">
                    <label for="emp_telefono" class="block text-xs font-medium text-slate-700 mb-1">
                        Teléfono Fiscal
                    </label>
                    <input type="text" id="emp_telefono" name="telefono" maxlength="40"
                           placeholder="0212-9510000 / 0414-0000000"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm font-mono text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:outline-none">
                </div>

                <div class="sm:col-span-6">
                    <label for="emp_email_contacto" class="block text-xs font-medium text-slate-700 mb-1">
                        Correo Electrónico de Contacto
                    </label>
                    <input type="email" id="emp_email_contacto" name="email_contacto" maxlength="150"
                           placeholder="administracion@empresa.com.ve"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:outline-none">
                </div>
            </div>

            <!-- Parámetros Tributarios SENIAT / LOTTT -->
            <div class="pt-4 border-t border-slate-200">
                <h4 class="text-xs font-mono font-semibold uppercase tracking-wider text-emerald-700 mb-3">
                    Configuración Tributaria SENIAT y Parámetros LOTTT
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="emp_tipo_contribuyente" class="block text-xs font-medium text-slate-700 mb-1">
                            Tipo de Contribuyente <span class="text-rose-600">*</span>
                        </label>
                        <select id="emp_tipo_contribuyente" name="tipo_contribuyente"
                                class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:outline-none">
                            <option value="Ordinario">Ordinario</option>
                            <option value="Especial">Especial (Agente de Retención)</option>
                            <option value="Formal">Formal</option>
                        </select>
                    </div>

                    <div>
                        <label for="emp_porcentaje_retencion_iva" class="block text-xs font-medium text-slate-700 mb-1">
                            % Retención IVA por Defecto <span class="text-rose-600">*</span>
                        </label>
                        <select id="emp_porcentaje_retencion_iva" name="porcentaje_retencion_iva"
                                class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm font-mono text-slate-900 focus:border-emerald-600 focus:outline-none">
                            <option value="0">0% (No Retiene)</option>
                            <option value="75" selected>75% (General SNAT/2015/0049)</option>
                            <option value="100">100% (Retención Total)</option>
                        </select>
                    </div>

                    <div>
                        <label for="emp_periodo_fiscal_activo" class="block text-xs font-medium text-slate-700 mb-1">
                            Período Fiscal Activo <span class="text-rose-600">*</span>
                        </label>
                        <input type="month" id="emp_periodo_fiscal_activo" name="periodo_fiscal_activo" required
                               value="<?= date('Y-m') ?>"
                               class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm font-mono text-slate-900 focus:border-emerald-600 focus:outline-none">
                    </div>

                    <div>
                        <label for="emp_valor_unidad_tributaria" class="block text-xs font-medium text-slate-700 mb-1">
                            Valor Unidad Tributaria (Bs.)
                        </label>
                        <input type="number" step="0.01" min="0" id="emp_valor_unidad_tributaria" name="valor_unidad_tributaria"
                               value="9.00"
                               class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm font-mono text-slate-900 focus:border-emerald-600 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="emp_monto_cestaticket" class="block text-xs font-medium text-slate-700 mb-1">
                            Bono de Alimentación / Cestaticket Mensual (Bs.)
                        </label>
                        <input type="number" step="0.01" min="0" id="emp_monto_cestaticket" name="monto_cestaticket_mensual"
                               value="1800.00"
                               class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-sm font-mono text-slate-900 focus:border-emerald-600 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Pie del Formulario -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                <button type="button" onclick="window.JurisApp.cerrarModalEmpresa()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 transition-colors">
                    Cancelar
                </button>
                <button type="submit" id="btnGuardarEmpresa"
                        class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-emerald-700 hover:bg-emerald-800 shadow-sm transition-all">
                    Guardar Empresa Cliente
                </button>
            </div>
        </form>
    </div>
</div>
