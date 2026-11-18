<?php
declare(strict_types=1);
?>
        </main>

        <!-- Barra de Estado Inferior -->
        <footer class="h-9 bg-slate-900/90 border-t border-slate-800/80 px-6 flex items-center justify-between text-[11px] text-slate-400 shrink-0">
            <div class="flex items-center gap-4">
                <span><strong class="text-slate-200"><?= e(APP_NAME) ?></strong> — <?= e(APP_SUBTITLE) ?></span>
                <span class="hidden md:inline text-slate-600">|</span>
                <span class="hidden md:inline font-mono text-emerald-400">Arquitectura Multi-Tenant (Filtro estricto por empresa_id)</span>
            </div>
            <div class="font-mono text-slate-500">
                v<?= e(APP_VERSION) ?> • PHP <?= e(PHP_VERSION) ?>
            </div>
        </footer>
    </div>
</div>

<!-- MODAL GLOBAL: VISOR DEL PLAN DE CUENTAS DE LA EMPRESA -->
<div id="modalVisorPlanCuentas" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[88vh] flex flex-col overflow-hidden">
        <!-- Cabecera del Modal -->
        <div class="px-6 py-4 bg-slate-950/70 border-b border-slate-800 flex items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 text-[11px] font-mono font-semibold rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30" id="planModalRif">RIF</span>
                    <h3 class="text-base font-bold text-white" id="planModalTitulo">Catálogo del Plan de Cuentas</h3>
                </div>
                <p class="text-xs text-slate-400 mt-0.5" id="planModalSubtitulo">Estructura contable venezolana clonada para esta empresa</p>
            </div>
            <button type="button" onclick="window.JurisApp.cerrarModalPlanCuentas()"
                    class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Barra de Filtros del Plan de Cuentas -->
        <div class="px-6 py-3 bg-slate-900 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-1 min-w-[240px]">
                <input type="text" id="inputBuscarCuentaModal"
                       placeholder="Filtrar por código (ej. 1.1.02) o nombre de cuenta..."
                       class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:outline-none">
                <select id="selectTipoCuentaModal"
                        class="bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-xl px-3 py-2 text-xs text-slate-100 focus:outline-none">
                    <option value="">Todos los Rubros</option>
                    <option value="ACTIVO">1. ACTIVO</option>
                    <option value="PASIVO">2. PASIVO</option>
                    <option value="CAPITAL">3. CAPITAL</option>
                    <option value="INGRESOS">4. INGRESOS</option>
                    <option value="COSTO">5. COSTO DE VENTAS</option>
                    <option value="GASTOS">6. GASTOS</option>
                </select>
            </div>
            <div class="flex items-center gap-3 text-xs font-mono">
                <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 border border-slate-700">
                    Total: <strong id="planModalStatTotal" class="text-white">0</strong>
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                    Imputables: <strong id="planModalStatMov">0</strong>
                </span>
            </div>
        </div>

        <!-- Tabla Scrollable del Plan de Cuentas -->
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-950/90 sticky top-0 z-10 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="py-3 px-5 font-semibold">Código</th>
                        <th class="py-3 px-4 font-semibold">Nombre de la Cuenta</th>
                        <th class="py-3 px-4 font-semibold">Rubro</th>
                        <th class="py-3 px-4 font-semibold">Naturaleza</th>
                        <th class="py-3 px-4 font-semibold text-center">Nivel</th>
                        <th class="py-3 px-5 font-semibold text-right">Tipo</th>
                    </tr>
                </thead>
                <tbody id="tablaBodyPlanCuentasModal" class="divide-y divide-slate-800/60 text-xs">
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500">Cargando catálogo contable...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL GLOBAL: TASA OFICIAL DEL DÓLAR BCV Y SINCRONIZACIÓN DE CESTATICKET ($40 BCV) -->
<div id="modalTasaBcvGlobal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-950/70 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-mono uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-bold block">Banco Central de Venezuela</span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Tasa Oficial Dólar BCV (Bs. / USD)</h3>
            </div>
            <button type="button" onclick="window.JurisApp.cerrarModalTasaBcv()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="formTasaBcvGlobal" class="p-6 space-y-4">
            <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs text-slate-700 dark:text-slate-300 flex items-center justify-between gap-3">
                <div>
                    <strong class="block text-emerald-700 dark:text-emerald-300">Sincronización Automática BCV</strong>
                    <span class="text-[11px]">Consulta la tasa oficial vigente o ingrésela manualmente.</span>
                </div>
                <button type="button"
                        id="btnConsultarBcvOnline"
                        onclick="window.JurisApp.consultarTasaBcvEnLinea()"
                        class="px-3 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shrink-0 transition">
                    Consultar BCV Hoy
                </button>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tasa BCV (Bs. por 1 USD) *</label>
                    <input type="number" step="0.0001" min="0.01" id="inputTasaBcvGlobal"
                           value="<?= e(number_format((float)($empresaActiva['tasa_bcv_usd'] ?? \Core\Session::get('empresa_activa_tasa_bcv', 45.00)), 4, '.', '')) ?>"
                           oninput="window.JurisApp.previsualizarCestaticketBcv()"
                           class="w-full px-3.5 py-2.5 rounded-xl text-sm font-mono font-bold bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-emerald-600 dark:text-emerald-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Base Cestaticket ($ USD)</label>
                    <input type="number" step="0.01" min="1" id="inputMontoUsdCestaticket" value="40.00"
                           oninput="window.JurisApp.previsualizarCestaticketBcv()"
                           class="w-full px-3.5 py-2.5 rounded-xl text-sm font-mono bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30">
                <label class="inline-flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" id="chkRecalcularCestaticketBcv" checked class="mt-0.5 rounded text-amber-600 focus:ring-amber-500">
                    <div class="text-xs">
                        <span class="font-bold text-slate-900 dark:text-white block">
                            Actualizar Bono de Alimentación ($40 × Tasa BCV)
                        </span>
                        <span class="text-[11px] text-slate-600 dark:text-slate-400">
                            Cestaticket resultante: <strong id="lblPreviewCestaticketBcv" class="font-mono text-amber-600 dark:text-amber-400">Bs. 1.800,00</strong>
                        </span>
                    </div>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="window.JurisApp.cerrarModalTasaBcv()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-950/30">
                    Aplicar Tasa BCV
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Script Principal de Juris Contable -->
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>

<?php if (!empty($alertaFlash) && is_array($alertaFlash)): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isDark = document.documentElement.classList.contains('dark');
        Swal.fire({
            icon: <?= json_encode($alertaFlash['tipo'] ?? 'info') ?>,
            title: <?= json_encode($alertaFlash['titulo'] ?? 'Notificación') ?>,
            text: <?= json_encode($alertaFlash['mensaje'] ?? '') ?>,
            background: isDark ? '#0f172a' : '#ffffff',
            color: isDark ? '#f1f5f9' : '#0f172a',
            confirmButtonColor: '#10b981',
            timer: 3800,
            timerProgressBar: true
        });
    });
</script>
<?php endif; ?>
</body>
</html>
