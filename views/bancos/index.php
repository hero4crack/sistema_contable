<?php
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

$emp         = $empresaActiva ?? [];
$usuarioRol  = (string)Session::get('usuario_rol', 'contador');
$puedeEditar = in_array($usuarioRol, ['admin', 'contador'], true);
?>

<div class="max-w-7xl mx-auto space-y-6">

    <!-- ENCABEZADO DEL MÓDULO -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-700 uppercase tracking-wider font-semibold">
                <span>Módulo 2 • Enlace Bancario y Tesorería</span>
                <span>•</span>
                <span><?= e($emp['rif'] ?? '') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-0.5">
                Cuentas Bancarias y Libro Auxiliar de Banco
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Gestione las cuentas bancarias de <strong class="text-slate-800"><?= e($emp['razon_social'] ?? '') ?></strong>, importe estados de cuenta CSV y contabilice operaciones hacia el Libro Diario.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <?php if ($cuentaSeleccionada): ?>
                <a href="<?= e(route_url('bancos', 'conciliar', ['cuenta_id' => $cuentaSeleccionada['id']])) ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-sm transition-all">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Mesa de Conciliación
                </a>
            <?php endif; ?>

            <?php if ($puedeEditar): ?>
                <button type="button" onclick="abrirModalCuenta()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-emerald-700 hover:bg-emerald-800 shadow-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva Cuenta Bancaria
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- TARJETAS RESUMEN DE CUENTAS BANCARIAS -->
    <?php if (!empty($cuentas)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($cuentas as $cta): ?>
                <?php
                $esSeleccionada = $cuentaSeleccionada && (int)$cuentaSeleccionada['id'] === (int)$cta['id'];
                $ultimos4       = substr((string)$cta['numero_cuenta'], -4);
                $esActiva       = $cta['estatus'] === 'Activa';
                $simbolo        = $cta['moneda'] === 'USD' ? '$ ' : 'Bs. ';
                ?>
                <div class="rounded-2xl bg-white border <?= $esSeleccionada ? 'border-emerald-600 ring-2 ring-emerald-100' : 'border-slate-200' ?> shadow-sm p-5 flex flex-col justify-between transition-all">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <?= e($cta['codigo_sudeban']) ?>
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold <?= $esActiva ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' ?>">
                                        <?= e($cta['estatus']) ?>
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-slate-100 text-slate-700">
                                        <?= e($cta['moneda']) ?>
                                    </span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 mt-2">
                                    <?= e($cta['banco_nombre']) ?>
                                </h3>
                                <p class="text-xs font-mono text-slate-500 mt-0.5">
                                    <?= e($cta['tipo_cuenta']) ?> • •••• <?= e($ultimos4) ?>
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold block">Saldo Disponible</span>
                            <p class="text-xl font-mono font-bold <?= (float)$cta['saldo_actual'] >= 0 ? 'text-emerald-800' : 'text-rose-700' ?> mt-0.5">
                                <?= e($simbolo . number_format((float)$cta['saldo_actual'], 2, ',', '.')) ?>
                            </p>
                            <p class="text-[11px] text-slate-500 mt-1 truncate">
                                <?php if (!empty($cta['cuenta_contable_codigo'])): ?>
                                    Enlace Mayor: <span class="font-mono font-semibold text-slate-700"><?= e($cta['cuenta_contable_codigo']) ?></span> — <?= e($cta['cuenta_contable_nombre']) ?>
                                <?php else: ?>
                                    <span class="text-amber-700">Sin cuenta contable enlazada</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                        <a href="<?= e(route_url('bancos', 'index', ['cuenta_id' => $cta['id']])) ?>"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $esSeleccionada ? 'bg-emerald-700 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200' ?> transition-colors">
                            <?= $esSeleccionada ? 'Viendo Auxiliar' : 'Ver Auxiliar' ?>
                        </a>

                        <div class="flex items-center gap-1.5">
                            <a href="<?= e(route_url('bancos', 'conciliar', ['cuenta_id' => $cta['id']])) ?>"
                               class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 transition-colors">
                                Conciliar
                            </a>
                            <?php if ($puedeEditar): ?>
                                <button type="button" onclick="editarCuenta(<?= (int)$cta['id'] ?>)"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 transition-colors">
                                    Editar
                                </button>
                                <button type="button"
                                        onclick="cambiarEstatusCuenta(<?= (int)$cta['id'] ?>, '<?= $esActiva ? 'Inactiva' : 'Activa' ?>')"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium <?= $esActiva ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200' ?> border transition-colors">
                                    <?= $esActiva ? 'Inactivar' : 'Activar' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-10 text-center">
            <p class="text-sm font-semibold text-slate-800">No hay cuentas bancarias registradas para esta empresa.</p>
            <p class="text-xs text-slate-500 mt-1">Registre la primera cuenta bancaria (20 dígitos) para habilitar el libro auxiliar, importación CSV y conciliación mensual.</p>
        </div>
    <?php endif; ?>

    <!-- LIBRO AUXILIAR DE BANCO DE LA CUENTA SELECCIONADA -->
    <?php if ($cuentaSeleccionada): ?>
        <?php $simboloSel = $cuentaSeleccionada['moneda'] === 'USD' ? '$ ' : 'Bs. '; ?>
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        Auxiliar de Banco: <?= e($cuentaSeleccionada['banco_nombre']) ?> (•••• <?= e(substr((string)$cuentaSeleccionada['numero_cuenta'], -4)) ?>)
                    </h2>
                    <p class="text-xs text-slate-500 font-mono mt-0.5">
                        Cuenta: <?= e($cuentaSeleccionada['numero_cuenta']) ?> • Saldo Actual: <strong class="text-emerald-800"><?= e($simboloSel . number_format((float)$cuentaSeleccionada['saldo_actual'], 2, ',', '.')) ?></strong>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="<?= e(route_url('bancos', 'index')) ?>" class="flex items-center gap-2">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="cuenta_id" value="<?= (int)$cuentaSeleccionada['id'] ?>">
                        <input type="month" name="periodo" value="<?= e($periodoFiltro) ?>"
                               class="rounded-xl bg-white border border-slate-300 px-3 py-1.5 text-xs font-mono text-slate-900 focus:border-emerald-600 focus:outline-none">
                        <button type="submit"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors">
                            Filtrar Mes
                        </button>
                        <?php if ($periodoFiltro !== ''): ?>
                            <a href="<?= e(route_url('bancos', 'index', ['cuenta_id' => $cuentaSeleccionada['id']])) ?>"
                               class="text-xs text-slate-500 hover:text-slate-900 underline">
                                Todos
                            </a>
                        <?php endif; ?>
                    </form>

                    <?php if ($puedeEditar): ?>
                        <button type="button" onclick="abrirModalImportarCsv()"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 transition-colors">
                            Importar CSV Banco
                        </button>
                        <button type="button" onclick="abrirModalMovimiento()"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white shadow-sm transition-colors">
                            + Registrar Movimiento
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Fecha</th>
                            <th class="py-3 px-4 font-semibold">Referencia</th>
                            <th class="py-3 px-4 font-semibold">Tipo</th>
                            <th class="py-3 px-4 font-semibold">Descripción</th>
                            <th class="py-3 px-4 font-semibold text-right">Entrada (Debe)</th>
                            <th class="py-3 px-4 font-semibold text-right">Salida (Haber)</th>
                            <th class="py-3 px-4 font-semibold text-center">Estado</th>
                            <th class="py-3 px-4 font-semibold text-right">Contabilidad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (!empty($movimientos)): ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <?php
                                $debe  = (float)$mov['monto_debe'];
                                $haber = (float)$mov['monto_haber'];
                                ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="py-3 px-4 font-mono whitespace-nowrap text-slate-700">
                                        <?= e(date('d/m/Y', strtotime((string)$mov['fecha_operacion']))) ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-semibold text-slate-900 whitespace-nowrap">
                                        <?= e($mov['referencia']) ?>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-slate-600">
                                        <?= e($mov['tipo_movimiento']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-slate-800">
                                        <?= e($mov['descripcion']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-semibold text-emerald-700 whitespace-nowrap">
                                        <?= $debe > 0 ? e($simboloSel . number_format($debe, 2, ',', '.')) : '—' ?>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-semibold text-rose-700 whitespace-nowrap">
                                        <?= $haber > 0 ? e($simboloSel . number_format($haber, 2, ',', '.')) : '—' ?>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <?php if ((int)$mov['conciliado'] === 1): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                Conciliado
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                Pendiente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <?php if (!empty($mov['numero_asiento'])): ?>
                                            <span class="px-2 py-0.5 rounded font-mono text-[11px] bg-slate-100 text-slate-700 border border-slate-200">
                                                <?= e($mov['numero_asiento']) ?>
                                            </span>
                                        <?php elseif ($puedeEditar): ?>
                                            <button type="button"
                                                    onclick="abrirModalContabilizar(<?= (int)$mov['id'] ?>, <?= e(json_encode($mov['referencia'])) ?>, <?= e(json_encode($mov['descripcion'])) ?>, <?= $debe > 0 ? $debe : $haber ?>, <?= $debe > 0 ? 'true' : 'false' ?>)"
                                                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition-colors">
                                                Contabilizar
                                            </button>
                                        <?php else: ?>
                                            <span class="text-[11px] text-slate-400">Sin asiento</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-500">
                                    No existen movimientos bancarios registrados para esta cuenta y período.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL 1: CREAR / EDITAR CUENTA BANCARIA -->
<div id="modalCuentaBancaria" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 id="tituloModalCuenta" class="text-base font-bold text-slate-900">Registrar Cuenta Bancaria</h3>
                <p class="text-xs text-emerald-700 font-mono mt-0.5">Formato estándar SUDEBAN (20 dígitos)</p>
            </div>
            <button type="button" onclick="cerrarModal('modalCuentaBancaria')" class="p-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-200/60">✕</button>
        </div>

        <form id="formCuentaBancaria" class="p-6 space-y-4">
            <input type="hidden" id="cb_id" name="id" value="0">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Institución Bancaria (Venezuela)</label>
                    <select id="cb_banco_selector" class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900 focus:border-emerald-600 focus:outline-none">
                        <option value="">-- Seleccione Banco --</option>
                        <?php foreach ($bancosCatalogo as $cod => $nom): ?>
                            <option value="<?= e($cod) ?>" data-nombre="<?= e($nom) ?>"><?= e($cod . ' — ' . $nom) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="cb_codigo_sudeban" class="block text-xs font-medium text-slate-700 mb-1">Código SUDEBAN (4 dígitos)</label>
                    <input type="text" id="cb_codigo_sudeban" name="codigo_sudeban" required maxlength="4" placeholder="0134"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs font-mono text-slate-900 focus:border-emerald-600 focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label for="cb_banco_nombre" class="block text-xs font-medium text-slate-700 mb-1">Nombre del Banco</label>
                    <input type="text" id="cb_banco_nombre" name="banco_nombre" required maxlength="100"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900 focus:border-emerald-600 focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label for="cb_numero_cuenta" class="block text-xs font-medium text-slate-700 mb-1">Número de Cuenta (20 dígitos exactos)</label>
                    <input type="text" id="cb_numero_cuenta" name="numero_cuenta" required maxlength="20" pattern="\d{20}" placeholder="01340000000000000000"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs font-mono text-slate-900 focus:border-emerald-600 focus:outline-none">
                </div>

                <div>
                    <label for="cb_tipo_cuenta" class="block text-xs font-medium text-slate-700 mb-1">Tipo de Cuenta</label>
                    <select id="cb_tipo_cuenta" name="tipo_cuenta" class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900 focus:border-emerald-600 focus:outline-none">
                        <?php foreach ($tiposCuenta as $tc): ?>
                            <option value="<?= e($tc) ?>"><?= e($tc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="cb_moneda" class="block text-xs font-medium text-slate-700 mb-1">Moneda</label>
                    <select id="cb_moneda" name="moneda" class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900 focus:border-emerald-600 focus:outline-none">
                        <option value="VES">VES (Bolívares)</option>
                        <option value="USD">USD (Dólares)</option>
                    </select>
                </div>

                <div>
                    <label for="cb_cuenta_contable_id" class="block text-xs font-medium text-slate-700 mb-1">Cuenta Contable Asociada (Libro Mayor)</label>
                    <select id="cb_cuenta_contable_id" name="cuenta_contable_id" class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900 focus:border-emerald-600 focus:outline-none">
                        <option value="">-- Sin enlace contable --</option>
                        <?php foreach ($cuentasContables as $cc): ?>
                            <option value="<?= (int)$cc['id'] ?>"><?= e($cc['codigo'] . ' — ' . $cc['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="cb_saldo_inicial" class="block text-xs font-medium text-slate-700 mb-1">Saldo Inicial</label>
                    <input type="number" step="0.01" id="cb_saldo_inicial" name="saldo_inicial" value="0.00" required
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs font-mono text-slate-900 focus:border-emerald-600 focus:outline-none">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="cerrarModal('modalCuentaBancaria')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white text-slate-700 border border-slate-300 hover:bg-slate-50">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">Guardar Cuenta</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: REGISTRAR MOVIMIENTO MANUAL -->
<?php if ($cuentaSeleccionada): ?>
<div id="modalMovimientoBanco" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Registrar Movimiento Bancario Manual</h3>
            <button type="button" onclick="cerrarModal('modalMovimientoBanco')" class="p-2 rounded-lg text-slate-500 hover:text-slate-900">✕</button>
        </div>

        <form id="formMovimientoBanco" class="p-6 space-y-4">
            <input type="hidden" name="cuenta_bancaria_id" value="<?= (int)$cuentaSeleccionada['id'] ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Fecha de Operación</label>
                    <input type="date" name="fecha_operacion" value="<?= date('Y-m-d') ?>" required
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs font-mono text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Referencia Bancaria</label>
                    <input type="text" name="referencia" required maxlength="60" placeholder="00045891"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs font-mono text-slate-900">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tipo de Movimiento</label>
                    <select name="tipo_movimiento" class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900">
                        <?php foreach ($tiposMovimiento as $tm): ?>
                            <option value="<?= e($tm) ?>"><?= e($tm) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Descripción / Concepto</label>
                    <input type="text" name="descripcion" required maxlength="255" placeholder="Pago de factura o cobro a cliente"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Entrada / Crédito Bancario (Debe)</label>
                    <input type="number" step="0.01" min="0" name="monto_debe" value="0.00"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs font-mono text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Salida / Débito Bancario (Haber)</label>
                    <input type="number" step="0.01" min="0" name="monto_haber" value="0.00"
                           class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs font-mono text-slate-900">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="cerrarModal('modalMovimientoBanco')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white text-slate-700 border border-slate-300">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">Guardar Movimiento</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: IMPORTADOR CSV CON VISTA PREVIA -->
<div id="modalImportarCsv" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Importar Estado de Cuenta Bancario (CSV)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Formato estándar: <code>Fecha, Referencia, Descripción, Débito (Entrada), Crédito (Salida)</code></p>
            </div>
            <button type="button" onclick="cerrarModal('modalImportarCsv')" class="p-2 rounded-lg text-slate-500 hover:text-slate-900">✕</button>
        </div>

        <div class="p-6 overflow-y-auto space-y-4">
            <form id="formPreviewCsv" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl p-4">
                <input type="hidden" name="cuenta_bancaria_id" value="<?= (int)$cuentaSeleccionada['id'] ?>">
                <input type="file" name="archivo_csv" id="inputArchivoCsv" accept=".csv,.txt" required
                       class="flex-1 text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-emerald-200 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800">
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">
                    Cargar Vista Previa
                </button>
            </form>

            <div id="contenedorPreviewCsv" class="hidden space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <span id="resumenPreviewCsv" class="font-semibold text-slate-800"></span>
                    <button type="button" id="btnConfirmarCsv" onclick="confirmarCargaCsv()"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">
                        Confirmar Importación
                    </button>
                </div>
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase text-slate-600">
                            <tr>
                                <th class="py-2 px-3">Fecha</th>
                                <th class="py-2 px-3">Referencia</th>
                                <th class="py-2 px-3">Descripción</th>
                                <th class="py-2 px-3 text-right">Entrada</th>
                                <th class="py-2 px-3 text-right">Salida</th>
                                <th class="py-2 px-3 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPreviewCsv" class="divide-y divide-slate-200"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 4: CONTABILIZAR MOVIMIENTO BANCARIO -->
<div id="modalContabilizarMov" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Contabilizar Movimiento Bancario</h3>
            <button type="button" onclick="cerrarModal('modalContabilizarMov')" class="p-2 rounded-lg text-slate-500 hover:text-slate-900">✕</button>
        </div>

        <form id="formContabilizarMov" class="p-6 space-y-4">
            <input type="hidden" id="cont_mov_id" name="movimiento_id">
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs space-y-1">
                <p class="font-semibold text-emerald-900" id="cont_resumen_mov"></p>
                <p class="font-mono text-emerald-800" id="cont_monto_mov"></p>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Cuenta Contable de Contrapartida (Plan de Cuentas)</label>
                <select id="cont_contrapartida_id" name="contrapartida_id" required
                        class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900">
                    <option value="">-- Seleccione contrapartida --</option>
                    <?php foreach ($cuentasContables as $cc): ?>
                        <option value="<?= (int)$cc['id'] ?>"><?= e($cc['codigo'] . ' — ' . $cc['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Glosa / Concepto del Asiento</label>
                <input type="text" id="cont_concepto" name="concepto" required maxlength="255"
                       class="w-full rounded-xl bg-white border border-slate-300 px-3.5 py-2 text-xs text-slate-900">
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="cerrarModal('modalContabilizarMov')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white text-slate-700 border border-slate-300">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white">Generar Asiento en Libro Diario</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
(() => {
    const BASE_URL = window.JURIS_CONFIG?.baseUrl || '';
    const CSRF     = window.JURIS_CONFIG?.csrfToken || '';
    let filasCsvCache = [];

    window.cerrarModal = (id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('hidden');
        el.classList.remove('flex');
    };

    function abrirModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('hidden');
        el.classList.add('flex');
    }

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

    // Selector de banco venezolano auto-completa código SUDEBAN y nombre
    document.getElementById('cb_banco_selector')?.addEventListener('change', (e) => {
        const opt = e.target.selectedOptions?.[0];
        if (!opt || !opt.value) return;
        document.getElementById('cb_codigo_sudeban').value = opt.value;
        document.getElementById('cb_banco_nombre').value = opt.getAttribute('data-nombre') || '';
    });

    window.abrirModalCuenta = () => {
        document.getElementById('formCuentaBancaria').reset();
        document.getElementById('cb_id').value = '0';
        document.getElementById('tituloModalCuenta').textContent = 'Registrar Cuenta Bancaria';
        abrirModal('modalCuentaBancaria');
    };

    window.editarCuenta = async (id) => {
        try {
            const res = await postJson(`${BASE_URL}/index.php?c=bancos&a=obtenerCuenta`, { id });
            const c = res.cuenta;
            document.getElementById('cb_id').value = c.id;
            document.getElementById('cb_codigo_sudeban').value = c.codigo_sudeban || '';
            document.getElementById('cb_banco_nombre').value = c.banco_nombre || '';
            document.getElementById('cb_numero_cuenta').value = c.numero_cuenta || '';
            document.getElementById('cb_tipo_cuenta').value = c.tipo_cuenta || 'Corriente';
            document.getElementById('cb_moneda').value = c.moneda || 'VES';
            document.getElementById('cb_cuenta_contable_id').value = c.cuenta_contable_id || '';
            document.getElementById('cb_saldo_inicial').value = c.saldo_inicial ?? '0.00';
            document.getElementById('tituloModalCuenta').textContent = 'Editar Cuenta Bancaria';
            abrirModal('modalCuentaBancaria');
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    };

    window.cambiarEstatusCuenta = async (id, estatus) => {
        try {
            const res = await postJson(`${BASE_URL}/index.php?c=bancos&a=cambiarEstatusCuenta`, { id, estatus });
            await Swal.fire({ icon: 'success', title: 'Actualizado', text: res.message, timer: 1600, showConfirmButton: false });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    };

    document.getElementById('formCuentaBancaria')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const payload = Object.fromEntries(new FormData(e.target).entries());
            const res = await postJson(`${BASE_URL}/index.php?c=bancos&a=guardarCuenta`, payload);
            cerrarModal('modalCuentaBancaria');
            await Swal.fire({ icon: 'success', title: 'Cuenta Guardada', text: res.message, timer: 1600, showConfirmButton: false });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Validación', text: err.message });
        }
    });

    window.abrirModalMovimiento = () => abrirModal('modalMovimientoBanco');
    window.abrirModalImportarCsv = () => abrirModal('modalImportarCsv');

    document.getElementById('formMovimientoBanco')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const payload = Object.fromEntries(new FormData(e.target).entries());
            const res = await postJson(`${BASE_URL}/index.php?c=bancos&a=registrarMovimiento`, payload);
            cerrarModal('modalMovimientoBanco');
            await Swal.fire({ icon: 'success', title: 'Registrado', text: res.message, timer: 1600, showConfirmButton: false });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Validación', text: err.message });
        }
    });

    document.getElementById('formPreviewCsv')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const formData = new FormData(e.target);
            const res = await postJson(`${BASE_URL}/index.php?c=bancos&a=previsualizarCsv`, formData);
            filasCsvCache = res.filas || [];
            const nuevos = filasCsvCache.filter(f => !f.duplicado).length;
            const dups   = filasCsvCache.length - nuevos;

            document.getElementById('resumenPreviewCsv').textContent =
                `Detectadas ${filasCsvCache.length} filas: ${nuevos} nuevas a importar, ${dups} duplicadas (se omitirán).`;

            document.getElementById('tbodyPreviewCsv').innerHTML = filasCsvCache.map(f => `
                <tr class="${f.duplicado ? 'bg-amber-50/70 text-slate-500' : 'bg-white'}">
                    <td class="py-2 px-3 font-mono">${f.fecha_operacion}</td>
                    <td class="py-2 px-3 font-mono">${f.referencia}</td>
                    <td class="py-2 px-3">${f.descripcion}</td>
                    <td class="py-2 px-3 text-right font-mono text-emerald-700">${Number(f.monto_debe).toFixed(2)}</td>
                    <td class="py-2 px-3 text-right font-mono text-rose-700">${Number(f.monto_haber).toFixed(2)}</td>
                    <td class="py-2 px-3 text-center">
                        ${f.duplicado
                            ? '<span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">Duplicado</span>'
                            : '<span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-semibold">Nuevo</span>'}
                    </td>
                </tr>
            `).join('');

            document.getElementById('contenedorPreviewCsv').classList.remove('hidden');
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error CSV', text: err.message });
        }
    });

    window.confirmarCargaCsv = async () => {
        const cuentaId = document.querySelector('#formPreviewCsv input[name="cuenta_bancaria_id"]')?.value || 0;
        try {
            const res = await postJson(`${BASE_URL}/index.php?c=bancos&a=confirmarImportacionCsv`, {
                cuenta_bancaria_id: Number(cuentaId),
                filas: filasCsvCache
            });
            cerrarModal('modalImportarCsv');
            await Swal.fire({ icon: 'success', title: 'Importación Exitosa', text: res.message });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    };

    window.abrirModalContabilizar = (id, ref, desc, monto, esEntrada) => {
        document.getElementById('cont_mov_id').value = id;
        document.getElementById('cont_resumen_mov').textContent = `Ref. ${ref} — ${desc}`;
        document.getElementById('cont_monto_mov').textContent = `${esEntrada ? 'Entrada (Debe al Banco)' : 'Salida (Haber al Banco)'}: ${Number(monto).toFixed(2)}`;
        document.getElementById('cont_concepto').value = `Operación Bancaria Ref. ${ref}: ${desc}`;
        abrirModal('modalContabilizarMov');
    };

    document.getElementById('formContabilizarMov')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const payload = Object.fromEntries(new FormData(e.target).entries());
            const res = await postJson(`${BASE_URL}/index.php?c=bancos&a=contabilizarMovimiento`, payload);
            cerrarModal('modalContabilizarMov');
            await Swal.fire({ icon: 'success', title: 'Asiento Generado', text: res.message });
            window.location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error Contable', text: err.message });
        }
    });
})();
</script>
