<?php
declare(strict_types=1);

$totalActivos        = 0;
$masaSalarialMensual = 0.0;
$totalPrestaciones   = 0.0;
$totalCestaticketMes = 0.0;
$montoCestUnitario   = (float)($configLaboral['monto_cestaticket_mensual'] ?? 1800.00);

foreach ($trabajadores as $t) {
    if ((int)$t['activo'] === 1 && ($t['estatus_laboral'] ?? 'ACTIVO') !== 'EGRESADO') {
        $totalActivos++;
        $masaSalarialMensual += (float)$t['salario_mensual'];
        $totalPrestaciones   += (float)$t['prestaciones_acumuladas_bs'];
        if ((int)$t['aplica_cestaticket'] === 1) {
            $totalCestaticketMes += $montoCestUnitario;
        }
    }
}
?>
<!-- Cabecera del Módulo de Trabajadores LOTTT -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-600 mb-1">
            <span>Módulo Laboral LOTTT</span>
            <span>•</span>
            <span class="font-mono"><?= e((string)$empresaActiva['rif']) ?></span>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
            Directorio de Trabajadores y Prestaciones Sociales
        </h2>
        <p class="text-sm text-slate-600 mt-0.5">
            Ficha integral del personal, cuentas nómina (20 dígitos), cálculo automático de antigüedad y garantía trimestral de Prestaciones Sociales (Art. 142 LOTTT).
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <a href="<?= e(route_url('nomina', 'index')) ?>"
           class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition">
            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Ir a Nómina
        </a>

        <button type="button"
                onclick="window.JurisNomina?.abrirModalConfigLaboral()"
                class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 border border-amber-500/30 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Parámetros LOTTT / IVSS
        </button>

        <button type="button"
                onclick="window.JurisNomina?.abrirModalTrabajador(0)"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Registrar Trabajador
        </button>
    </div>
</div>

<!-- Tarjetas de Resumen Laboral -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Personal Activo</span>
        <div class="mt-2 flex items-baseline justify-between">
            <span class="text-2xl font-bold font-mono text-slate-900"><?= (int)$totalActivos ?></span>
            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 font-medium">
                de <?= count($trabajadores) ?> registrados
            </span>
        </div>
        <p class="text-[11px] text-slate-500 mt-1">Nómina exclusiva de la empresa en sesión</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Masa Salarial Mensual Base</span>
        <div class="mt-2 flex items-baseline justify-between">
            <span class="text-xl font-bold font-mono text-emerald-600"><?= e(formato_ves($masaSalarialMensual)) ?></span>
        </div>
        <p class="text-[11px] text-slate-500 mt-1">
            Quincena estimada: <strong class="font-mono"><?= e(formato_ves($masaSalarialMensual / 2)) ?></strong>
        </p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Bono Alimentación Mensual</span>
        <div class="mt-2 flex items-baseline justify-between">
            <span class="text-xl font-bold font-mono text-amber-600"><?= e(formato_ves($totalCestaticketMes)) ?></span>
        </div>
        <p class="text-[11px] text-slate-500 mt-1">
            Base legal: <strong class="font-mono"><?= e(formato_ves($montoCestUnitario)) ?></strong> / trabajador
        </p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pasivo Prestaciones (Art. 142)</span>
        <div class="mt-2 flex items-baseline justify-between">
            <span class="text-xl font-bold font-mono text-sky-600"><?= e(formato_ves($totalPrestaciones)) ?></span>
        </div>
        <p class="text-[11px] text-slate-500 mt-1">Garantía trimestral acumulada (15 días/trim.)</p>
    </div>
</div>

<!-- Barra de Filtros -->
<form method="POST" action="<?= e(route_url('nomina', 'trabajadores')) ?>" class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
    <?= \Core\Csrf::field() ?>

    <div class="flex flex-wrap items-center gap-2">
        <?php
        $estados = [
            'TODOS'      => 'Todos',
            'ACTIVO'     => 'Activos',
            'VACACIONES' => 'En Vacaciones',
            'REPOSO'     => 'De Reposo',
            'EGRESADO'   => 'Egresados',
        ];
        foreach ($estados as $codigo => $etiqueta):
            $activoBtn = $filtroEstatus === $codigo;
        ?>
            <a href="<?= e(route_url('nomina', 'trabajadores', ['estatus' => $codigo, 'q' => $filtroBuscar])) ?>"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= $activoBtn
                   ? 'bg-emerald-600 text-white shadow-sm'
                   : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                <?= e($etiqueta) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="flex items-center gap-2">
        <input type="text"
               name="q"
               value="<?= e($filtroBuscar) ?>"
               placeholder="Buscar por cédula, nombre, cargo..."
               class="w-full md:w-72 px-3.5 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900 focus:outline-none focus:border-emerald-500">
        <button type="submit"
                class="px-4 py-2 rounded-xl text-xs font-semibold bg-white text-slate-900 hover:bg-slate-100 transition">
            Filtrar
        </button>
    </div>
</form>

<!-- Tabla del Directorio de Trabajadores -->
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <th class="py-3.5 px-4">Cédula / Trabajador</th>
                    <th class="py-3.5 px-4">Cargo / Clasificación</th>
                    <th class="py-3.5 px-4">Ingreso y Antigüedad</th>
                    <th class="py-3.5 px-4 text-right">Salario Base Mensual</th>
                    <th class="py-3.5 px-4 text-right">Prestaciones Acum. (Art. 142)</th>
                    <th class="py-3.5 px-4">Cuenta Nómina / Parafiscales</th>
                    <th class="py-3.5 px-4 text-center">Estatus</th>
                    <th class="py-3.5 px-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-xs">
                <?php if (empty($trabajadores)): ?>
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-500">
                            No se encontraron trabajadores registrados con los criterios seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trabajadores as $t): ?>
                        <?php
                        $estatus = (string)($t['estatus_laboral'] ?? 'ACTIVO');
                        $badgeClass = match ($estatus) {
                            'ACTIVO'     => 'bg-emerald-500/15 text-emerald-700 border-emerald-500/30',
                            'VACACIONES' => 'bg-sky-500/15 text-sky-700 border-sky-500/30',
                            'REPOSO'     => 'bg-amber-500/15 text-amber-700 border-amber-500/30',
                            default      => 'bg-rose-500/15 text-rose-700 border-rose-500/30',
                        };
                        ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-mono font-bold text-emerald-600"><?= e((string)$t['cedula']) ?></div>
                                <div class="font-semibold text-slate-900 mt-0.5"><?= e((string)$t['nombre_completo']) ?></div>
                                <div class="text-[11px] text-slate-500">
                                    <?= e((string)($t['telefono'] ?: 'Sin teléfono')) ?>
                                    <?php if (!empty($t['email'])): ?>
                                        • <?= e((string)$t['email']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800"><?= e((string)$t['cargo']) ?></div>
                                <div class="text-[11px] text-slate-500">
                                    <?= e((string)$t['departamento']) ?> • <span class="font-mono"><?= e((string)$t['tipo_trabajador']) ?></span>
                                </div>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-600">
                                    Nómina <?= e((string)($t['tipo_nomina'] ?? 'QUINCENAL')) ?>
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-mono text-slate-800">
                                    Ingreso: <?= e(date('d/m/Y', strtotime((string)$t['fecha_ingreso']))) ?>
                                </div>
                                <div class="text-[11px] font-medium text-emerald-600 mt-0.5">
                                    <?= e((string)$t['antiguedad_texto']) ?>
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    <?= (int)$t['trimestres_servicio'] ?> trimestres cumplidos
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono">
                                <div class="font-bold text-slate-900 text-sm">
                                    <?= e(formato_ves((float)$t['salario_mensual'])) ?>
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    Diario: <?= e(formato_ves((float)$t['sueldo_diario'])) ?>
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    Integral: <?= e(formato_ves((float)$t['salario_integral_diario'])) ?>/día
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono">
                                <div class="font-bold text-sky-600 text-sm">
                                    <?= e(formato_ves((float)$t['prestaciones_acumuladas_bs'])) ?>
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    <?= (int)$t['dias_prestaciones_acumulados'] ?> días acumulados
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="text-[11px] font-semibold text-slate-700">
                                    <?= e((string)($t['banco'] ?: 'PAGO EN EFECTIVO / CHEQUE')) ?>
                                </div>
                                <?php if (!empty($t['numero_cuenta'])): ?>
                                    <div class="font-mono text-[11px] text-slate-500 tracking-tight">
                                        <?= e((string)$t['numero_cuenta']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <?php if ((int)$t['aplica_sso'] === 1): ?>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-mono text-slate-600">IVSS</span>
                                    <?php endif; ?>
                                    <?php if ((int)$t['aplica_rpe'] === 1): ?>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-mono text-slate-600">RPE</span>
                                    <?php endif; ?>
                                    <?php if ((int)$t['aplica_faov'] === 1): ?>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-mono text-slate-600">FAOV</span>
                                    <?php endif; ?>
                                    <?php if ((int)$t['aplica_cestaticket'] === 1): ?>
                                        <span class="px-1.5 py-0.5 rounded bg-amber-500/15 text-[10px] font-mono text-amber-600">CESTATICKET</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border <?= $badgeClass ?>">
                                    <?= e($estatus) ?>
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <button type="button"
                                        onclick="window.JurisNomina?.abrirModalTrabajador(<?= (int)$t['id'] ?>)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 transition">
                                    Editar Ficha
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL: CREAR / EDITAR TRABAJADOR -->
<div id="modalTrabajador" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-50/75 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden my-8">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <div>
                <h3 id="modalTrabajadorTitulo" class="text-base font-bold text-slate-900">Registrar Trabajador</h3>
                <p class="text-xs text-slate-500">Ficha laboral LOTTT vinculada a <?= e((string)$empresaActiva['razon_social']) ?></p>
            </div>
            <button type="button" onclick="window.JurisNomina?.cerrarModalTrabajador()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="formTrabajador" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <input type="hidden" name="id" id="trab_id" value="0">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cédula de Identidad *</label>
                    <input type="text" name="cedula" id="trab_cedula" required placeholder="V-18452310"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nombres *</label>
                    <input type="text" name="nombres" id="trab_nombres" required placeholder="Carlos Eduardo"
                           class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Apellidos *</label>
                    <input type="text" name="apellidos" id="trab_apellidos" required placeholder="Méndez Rojas"
                           class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Teléfono</label>
                    <input type="text" name="telefono" id="trab_telefono" placeholder="0414-0000000"
                           class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Correo Electrónico</label>
                    <input type="email" name="email" id="trab_email" placeholder="trabajador@empresa.com"
                           class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" id="trab_fecha_nacimiento"
                           class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cargo *</label>
                    <input type="text" name="cargo" id="trab_cargo" required placeholder="Analista Contable Senior"
                           class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Departamento</label>
                    <input type="text" name="departamento" id="trab_departamento" value="Administración"
                           class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Clasificación</label>
                    <select name="tipo_trabajador" id="trab_tipo_trabajador"
                            class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                        <option value="EMPLEADO">EMPLEADO</option>
                        <option value="OBRERO">OBRERO</option>
                        <option value="DIRECTIVO">DIRECTIVO</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-emerald-700 mb-1">
                        Ref. en USD ($ BCV <?= e(number_format((float)($configLaboral['tasa_bcv_usd'] ?? 45.00), 2, ',', '.')) ?>)
                    </label>
                    <input type="number" step="0.01" min="0" id="trab_salario_usd_ref" placeholder="Ej: 300.00"
                           oninput="window.JurisNomina?.calcularSalarioBsDesdeUsd(this.value, <?= e((string)($configLaboral['tasa_bcv_usd'] ?? 45.00)) ?>)"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-emerald-500/5 border border-emerald-500/30 text-emerald-700">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Salario Base Mensual (Bs.) *</label>
                    <input type="number" step="0.01" min="1" name="salario_mensual" id="trab_salario_mensual" required placeholder="14500.00"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold bg-slate-50 border border-slate-300 text-emerald-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipo de Nómina</label>
                    <select name="tipo_nomina" id="trab_tipo_nomina"
                            class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                        <option value="QUINCENAL">QUINCENAL</option>
                        <option value="MENSUAL">MENSUAL</option>
                        <option value="SEMANAL">SEMANAL</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Fecha de Ingreso *</label>
                    <input type="date" name="fecha_ingreso" id="trab_fecha_ingreso" required value="<?= e(date('Y-m-d')) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Estatus Laboral</label>
                    <select name="estatus_laboral" id="trab_estatus_laboral"
                            class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                        <option value="ACTIVO">ACTIVO</option>
                        <option value="VACACIONES">EN VACACIONES</option>
                        <option value="REPOSO">DE REPOSO</option>
                        <option value="EGRESADO">EGRESADO</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Banco Nómina</label>
                    <input type="text" name="banco" id="trab_banco" placeholder="BANCO PROVINCIAL / BANCAMIGA"
                           class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cuenta Bancaria (20 dígitos)</label>
                    <input type="text" maxlength="20" name="numero_cuenta" id="trab_numero_cuenta" placeholder="01080025140100984512"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pago Móvil (Teléfono)</label>
                    <input type="text" maxlength="25" name="pago_movil_telefono" id="trab_pago_movil" placeholder="0414-1234567"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">% Retención ISLR (AR-I)</label>
                    <input type="number" step="0.01" min="0" max="34" name="porcentaje_islr" id="trab_porcentaje_islr" value="0.00"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="trab_aplica_sso" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                    <span class="text-slate-700 font-medium">Cotiza IVSS (4%)</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="trab_aplica_rpe" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                    <span class="text-slate-700 font-medium">Cotiza RPE (0.5%)</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="trab_aplica_faov" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                    <span class="text-slate-700 font-medium">Cotiza FAOV (1%)</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="trab_aplica_cestaticket" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                    <span class="text-slate-700 font-medium">Aplica Cestaticket</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button type="button" onclick="window.JurisNomina?.cerrarModalTrabajador()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30">
                    Guardar Ficha Laboral
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CONFIGURACIÓN DE PARÁMETROS LABORALES LOTTT -->
<div id="modalConfigLaboral" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-50/75 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden my-8">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="text-base font-bold text-slate-900">Parámetros Laborales LOTTT / IVSS / FAOV / Cestaticket</h3>
                <p class="text-xs text-slate-500">Configuración exclusiva de <?= e((string)$empresaActiva['razon_social']) ?></p>
            </div>
            <button type="button" onclick="window.JurisNomina?.cerrarModalConfigLaboral()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="formConfigLaboral" class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cestaticket Mensual (Bs.)</label>
                    <input type="number" step="0.01" id="cfg_monto_cestaticket" value="<?= e((string)$configLaboral['monto_cestaticket_mensual']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold bg-slate-50 border border-slate-300 text-amber-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Salario Ref. Tope IVSS (Bs.)</label>
                    <input type="number" step="0.01" id="cfg_salario_minimo" value="<?= e((string)$configLaboral['salario_minimo_referencia']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tope Salarios Mínimos IVSS</label>
                    <input type="number" min="1" max="20" id="cfg_tope_sso" value="<?= (int)$configLaboral['tope_salarios_minimos_sso'] ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">% Ret. IVSS Trabajador</label>
                    <input type="number" step="0.01" id="cfg_sso_emp" value="<?= e((string)$configLaboral['porcentaje_sso_empleado']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">% Ret. RPE (Paro Forzoso)</label>
                    <input type="number" step="0.01" id="cfg_rpe_emp" value="<?= e((string)$configLaboral['porcentaje_rpe_empleado']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">% Ret. FAOV / Banavih</label>
                    <input type="number" step="0.01" id="cfg_faov_emp" value="<?= e((string)$configLaboral['porcentaje_faov_empleado']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">% Aporte Patronal IVSS</label>
                    <input type="number" step="0.01" id="cfg_sso_pat" value="<?= e((string)$configLaboral['porcentaje_sso_patronal']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">% Aporte Patronal RPE</label>
                    <input type="number" step="0.01" id="cfg_rpe_pat" value="<?= e((string)$configLaboral['porcentaje_rpe_patronal']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">% Aporte Patronal FAOV</label>
                    <input type="number" step="0.01" id="cfg_faov_pat" value="<?= e((string)$configLaboral['porcentaje_faov_patronal']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">% Aporte Patronal INCES</label>
                    <input type="number" step="0.01" id="cfg_inces_pat" value="<?= e((string)$configLaboral['porcentaje_inces_patronal']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Días Base Comercial Mes</label>
                    <input type="number" id="cfg_dias_base" value="<?= (int)$configLaboral['dias_base_calculo'] ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Factor Hora Extra Diurna</label>
                    <input type="number" step="0.01" id="cfg_factor_he" value="<?= e((string)$configLaboral['factor_hora_extra_diurna']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Factor Bono Nocturno</label>
                    <input type="number" step="0.01" id="cfg_factor_bn" value="<?= e((string)$configLaboral['factor_bono_nocturno']) ?>"
                           class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-slate-50 border border-slate-300 text-slate-900">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button type="button" onclick="window.JurisNomina?.cerrarModalConfigLaboral()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30">
                    Guardar Parámetros
                </button>
            </div>
        </form>
    </div>
</div>
