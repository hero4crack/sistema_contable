<?php
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

$emp = $empresaActiva ?? [];
$usuarioRol  = (string)Session::get('usuario_rol', 'contador');
$puedeEditar = in_array($usuarioRol, ['admin', 'contador'], true);

$gruposFiltro = [
    ''         => 'Todos los Grupos',
    'ACTIVO'   => '1. Activo',
    'PASIVO'   => '2. Pasivo',
    'CAPITAL'  => '3. Patrimonio',
    'INGRESOS' => '4. Ingresos',
    'COSTO'    => '5. Costos',
    'GASTOS'   => '6. Gastos',
];

$etiquetasGrupo = [
    'ACTIVO'   => '1. ACTIVO',
    'PASIVO'   => '2. PASIVO',
    'CAPITAL'  => '3. PATRIMONIO',
    'INGRESOS' => '4. INGRESOS',
    'COSTO'    => '5. COSTOS',
    'GASTOS'   => '6. GASTOS',
];
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO DEL PLAN DE CUENTAS -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 no-print">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-700 uppercase tracking-wider">
                <span>Módulo 2 • Contabilidad General</span>
                <span>•</span>
                <span><?= e($emp['rif'] ?? '') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-0.5">
                Catálogo del Plan de Cuentas
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Estructura jerárquica multinivel aislada para <strong class="text-slate-800"><?= e($emp['razon_social'] ?? '') ?></strong>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-mono">
                Total: <strong class="text-slate-900"><?= (int)($stats['total_cuentas'] ?? 0) ?></strong>
                <span class="mx-1.5 text-slate-600">|</span>
                Imputables: <strong class="text-emerald-700"><?= (int)($stats['cuentas_movimiento'] ?? 0) ?></strong>
                <span class="mx-1.5 text-slate-600">|</span>
                Agrupadoras: <strong class="text-emerald-800"><?= (int)($stats['cuentas_grupo'] ?? 0) ?></strong>
            </div>

            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir Catálogo
            </button>

            <?php if ($puedeEditar): ?>
                <button type="button"
                        onclick="window.JurisContabilidad.abrirModalNuevaCuenta()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-950/50 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva Cuenta / Subcuenta
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- FILTROS POR GRUPO Y BUSCADOR EN TIEMPO REAL -->
    <div class="rounded-2xl bg-white border border-slate-200 p-4 space-y-3 no-print">
        <!-- Píldoras de Filtro por Grupo Contable -->
        <div class="flex flex-wrap items-center gap-2">
            <?php foreach ($gruposFiltro as $claveGrupo => $nombreGrupo): ?>
                <button type="button"
                        data-grupo="<?= e($claveGrupo) ?>"
                        onclick="window.JurisContabilidad.filtrarPlanPorGrupo('<?= e($claveGrupo) ?>')"
                        class="btn-filtro-grupo px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border <?= ($filtroTipo ?? '') === $claveGrupo ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
                    <?= e($nombreGrupo) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Buscador Instantáneo -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-200">
            <div class="relative flex-1 min-w-[260px]">
                <input type="text" id="inputBuscarPlanPage"
                       value="<?= e($filtroBuscar ?? '') ?>"
                       placeholder="Buscar en tiempo real por código (ej. 1.1.02, 6.1.01) o nombre de cuenta..."
                       class="w-full bg-slate-50 border border-slate-300 focus:border-emerald-500 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 placeholder-slate-400 focus:outline-none">
                <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="text-xs font-mono text-slate-400">
                Mostrando <strong id="contadorFilasPlan" class="text-slate-900"><?= count($cuentas ?? []) ?></strong> cuentas
            </div>
        </div>
    </div>

    <!-- MEMBRETE SOLO PARA IMPRESIÓN -->
    <div class="hidden print-only border-b-2 border-slate-400 pb-3 mb-4">
        <h2 class="text-lg font-bold"><?= e($emp['razon_social'] ?? '') ?> — RIF: <?= e($emp['rif'] ?? '') ?></h2>
        <p class="text-xs"><?= e($emp['direccion_fiscal'] ?? '') ?></p>
        <p class="text-sm font-bold mt-2">CATÁLOGO GENERAL DEL PLAN DE CUENTAS CONTABLE</p>
    </div>

    <!-- TABLA JERÁRQUICA DEL PLAN DE CUENTAS -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-xl overflow-hidden print-container">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="tablaPlanCuentasPage">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="py-3.5 px-5 font-semibold">Código</th>
                        <th class="py-3.5 px-4 font-semibold">Denominación de la Cuenta (Jerarquía)</th>
                        <th class="py-3.5 px-3 font-semibold">Grupo</th>
                        <th class="py-3.5 px-3 font-semibold text-center">Naturaleza</th>
                        <th class="py-3.5 px-3 font-semibold text-center">Nivel</th>
                        <th class="py-3.5 px-3 font-semibold text-center">Tipo</th>
                        <th class="py-3.5 px-4 font-semibold text-right">Saldo Actual</th>
                        <th class="py-3.5 px-5 font-semibold text-right no-print">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 text-xs">
                    <?php if (!empty($cuentas)): ?>
                        <?php foreach ($cuentas as $cta): ?>
                            <?php
                            $idCta        = (int)$cta['id'];
                            $nivel        = (int)$cta['nivel'];
                            $esMovimiento = ((int)$cta['acepta_movimiento'] === 1);
                            $estaActiva   = ((int)$cta['activo'] === 1);
                            $movCount     = (int)($cta['movimientos_count'] ?? 0);
                            $hijosCount   = (int)($cta['hijos_count'] ?? 0);
                            $puedeBorrar  = ($movCount === 0 && $hijosCount === 0);

                            $saldoInicial = (float)$cta['saldo_inicial'];
                            $debeTotal    = (float)$cta['total_debe'];
                            $haberTotal   = (float)$cta['total_haber'];
                            $saldoActual  = $cta['naturaleza'] === 'DEUDORA'
                                ? ($saldoInicial + $debeTotal - $haberTotal)
                                : ($saldoInicial + $haberTotal - $debeTotal);

                            $indentPx = max(0, ($nivel - 1) * 18);
                            ?>
                            <tr class="fila-cuenta transition-colors <?= $esMovimiento ? 'hover:bg-slate-50 text-slate-800' : 'bg-slate-50 font-semibold text-slate-900' ?> <?= !$estaActiva ? 'opacity-50' : '' ?>"
                                data-codigo="<?= e(strtolower((string)$cta['codigo'])) ?>"
                                data-nombre="<?= e(strtolower((string)$cta['nombre'])) ?>"
                                data-grupo="<?= e((string)$cta['tipo']) ?>">

                                <!-- Código -->
                                <td class="py-3 px-5 font-mono whitespace-nowrap <?= $esMovimiento ? 'text-emerald-700' : 'text-emerald-800 font-bold' ?>">
                                    <?= e($cta['codigo']) ?>
                                </td>

                                <!-- Nombre Indentado por Nivel -->
                                <td class="py-3 px-4" style="padding-left: <?= 16 + $indentPx ?>px;">
                                    <div class="flex items-center gap-2">
                                        <?php if (!$esMovimiento): ?>
                                            <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                            </svg>
                                        <?php else: ?>
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                                        <?php endif; ?>
                                        <span><?= e($cta['nombre']) ?></span>
                                        <?php if (!$estaActiva): ?>
                                            <span class="px-1.5 py-0.5 text-[10px] font-mono rounded bg-rose-500/20 text-rose-700">Inactiva</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Grupo -->
                                <td class="py-3 px-3 font-mono text-[11px] whitespace-nowrap text-slate-700">
                                    <?= e($etiquetasGrupo[$cta['tipo']] ?? $cta['tipo']) ?>
                                </td>

                                <!-- Naturaleza -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <span class="font-mono text-[11px] <?= $cta['naturaleza'] === 'DEUDORA' ? 'text-sky-800' : 'text-amber-800' ?>">
                                        <?= e($cta['naturaleza']) ?>
                                    </span>
                                </td>

                                <!-- Nivel -->
                                <td class="py-3 px-3 text-center font-mono text-slate-400">
                                    N<?= $nivel ?>
                                </td>

                                <!-- Tipo (Grupo vs Movimiento) -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <?php if ($esMovimiento): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-500/15 text-emerald-800 border border-emerald-500/30">
                                            Imputable
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-700 border border-slate-300">
                                            Agrupadora (<?= $hijosCount ?>)
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Saldo Actual -->
                                <td class="py-3 px-4 font-mono text-right whitespace-nowrap <?= abs($saldoActual) > 0.001 ? 'text-slate-900 font-semibold' : 'text-slate-500' ?>">
                                    <?= $esMovimiento ? formato_ves($saldoActual) : '—' ?>
                                </td>

                                <!-- Acciones CRUD -->
                                <td class="py-3 px-5 text-right whitespace-nowrap no-print">
                                    <?php if ($puedeEditar): ?>
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <?php if (!$esMovimiento): ?>
                                                <button type="button"
                                                        onclick="window.JurisContabilidad.abrirModalSubcuenta(<?= e(json_encode($cta['codigo'])) ?>)"
                                                        title="Crear subcuenta hija bajo <?= e($cta['codigo']) ?>"
                                                        class="px-2 py-1 rounded-lg text-[11px] font-mono font-medium bg-teal-500/15 hover:bg-teal-500/25 text-emerald-800 border border-teal-500/30 transition-colors">
                                                    + Subcuenta
                                                </button>
                                            <?php endif; ?>

                                            <button type="button"
                                                    onclick="window.JurisContabilidad.abrirModalEditarCuenta(<?= $idCta ?>)"
                                                    title="Editar cuenta"
                                                    class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors">
                                                Editar
                                            </button>

                                            <?php if ($puedeBorrar): ?>
                                                <button type="button"
                                                        onclick="window.JurisContabilidad.eliminarCuenta(<?= $idCta ?>, <?= e(json_encode($cta['codigo'] . ' - ' . $cta['nombre'])) ?>)"
                                                        title="Eliminar cuenta sin movimientos"
                                                        class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 border border-rose-500/20 transition-colors">
                                                    Eliminar
                                                </button>
                                            <?php else: ?>
                                                <span class="px-2 py-1 rounded-lg text-[10px] font-mono text-slate-500 bg-slate-50 border border-slate-200 cursor-not-allowed"
                                                      title="<?= $movCount > 0 ? "Protegida: posee {$movCount} movimiento(s) en Libro Diario" : "Protegida: posee {$hijosCount} subcuenta(s)" ?>">
                                                    Protegida
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL CREAR / EDITAR CUENTA CONTABLE -->
<div id="modalCrudCuenta" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 no-print">
    <div class="bg-white border border-slate-300 rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 id="tituloModalCuenta" class="text-base font-bold text-slate-900">Nueva Cuenta Contable</h3>
                <p id="subtituloModalCuenta" class="text-xs text-emerald-700 font-mono mt-0.5">
                    Catálogo de <?= e($emp['razon_social'] ?? '') ?>
                </p>
            </div>
            <button type="button" onclick="window.JurisContabilidad.cerrarModalCuenta()"
                    class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="formCrudCuenta" class="p-6 space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" id="cta_id" name="id" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <div class="sm:col-span-5">
                    <label for="cta_codigo_padre" class="block text-xs font-medium text-slate-700 mb-1">
                        Código Cuenta Padre (Opcional)
                    </label>
                    <input type="text" id="cta_codigo_padre" name="codigo_padre"
                           placeholder="Ej: 1.1.02"
                           class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-sm font-mono text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-7">
                    <label for="cta_codigo" class="block text-xs font-medium text-slate-700 mb-1">
                        Código de la Cuenta <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="cta_codigo" name="codigo" required
                           placeholder="Ej: 1.1.02.006"
                           class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-sm font-mono text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-12">
                    <label for="cta_nombre" class="block text-xs font-medium text-slate-700 mb-1">
                        Nombre o Denominación de la Cuenta <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="cta_nombre" name="nombre" required maxlength="180"
                           placeholder="Ej: BANCO BANESCO CUENTA CORRIENTE"
                           class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-6">
                    <label for="cta_tipo" class="block text-xs font-medium text-slate-700 mb-1">
                        Grupo Principal <span class="text-rose-400">*</span>
                    </label>
                    <select id="cta_tipo" name="tipo"
                            class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none">
                        <option value="ACTIVO">1. ACTIVO</option>
                        <option value="PASIVO">2. PASIVO</option>
                        <option value="CAPITAL">3. PATRIMONIO (CAPITAL)</option>
                        <option value="INGRESOS">4. INGRESOS</option>
                        <option value="COSTO">5. COSTOS</option>
                        <option value="GASTOS">6. GASTOS</option>
                    </select>
                </div>

                <div class="sm:col-span-6">
                    <label for="cta_naturaleza" class="block text-xs font-medium text-slate-700 mb-1">
                        Naturaleza del Saldo <span class="text-rose-400">*</span>
                    </label>
                    <select id="cta_naturaleza" name="naturaleza"
                            class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-sm font-mono text-slate-900 focus:border-emerald-500 focus:outline-none">
                        <option value="DEUDORA">DEUDORA (Debe + / Haber -)</option>
                        <option value="ACREEDORA">ACREEDORA (Haber + / Debe -)</option>
                    </select>
                </div>

                <div class="sm:col-span-6">
                    <label for="cta_acepta_movimiento" class="block text-xs font-medium text-slate-700 mb-1">
                        Tipo de Cuenta <span class="text-rose-400">*</span>
                    </label>
                    <select id="cta_acepta_movimiento" name="acepta_movimiento"
                            class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none">
                        <option value="1" selected>Cuenta de Detalle / Movimiento (Imputable)</option>
                        <option value="0">Cuenta Padre / Agrupadora</option>
                    </select>
                </div>

                <div class="sm:col-span-6">
                    <label for="cta_saldo_inicial" class="block text-xs font-medium text-slate-700 mb-1">
                        Saldo Inicial de Apertura (Bs.)
                    </label>
                    <input type="number" step="0.01" id="cta_saldo_inicial" name="saldo_inicial" value="0.00"
                           class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-sm font-mono text-slate-900 focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-12">
                    <label for="cta_activo" class="block text-xs font-medium text-slate-700 mb-1">
                        Estado de la Cuenta
                    </label>
                    <select id="cta_activo" name="activo"
                            class="w-full rounded-xl bg-slate-50 border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none">
                        <option value="1" selected>Activa (Disponible en Catálogo)</option>
                        <option value="0">Inactiva (Solo si no posee movimientos)</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                <button type="button" onclick="window.JurisContabilidad.cerrarModalCuenta()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition-colors">
                    Cancelar
                </button>
                <button type="submit" id="btnGuardarCuenta"
                        class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-950/50 transition-all">
                    Guardar Cuenta Contable
                </button>
            </div>
        </form>
    </div>
</div>
