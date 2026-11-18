/**
 * Juris Contable - Lógica Frontend Global y Contabilidad General (ES6+ Fetch API + SweetAlert2)
 * Arquitectura Multi-Tenant (SENIAT / LOTTT)
 */
(() => {
    'use strict';

    const getMeta = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || '';
    const BASE_URL = getMeta('base-url');
    const CSRF_TOKEN = getMeta('csrf-token');

    let catalogoActualModal = [];
    let grupoFiltroPlanPage = '';

    /**
     * Formatea un número al estándar monetario venezolano (1.234,56)
     */
    function formatVes(valor, conSimbolo = true) {
        const num = Number(valor) || 0;
        const formatted = num.toLocaleString('es-VE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        return conSimbolo ? `Bs. ${formatted}` : formatted;
    }

    /**
     * Wrapper para peticiones Fetch JSON con protección CSRF integrada
     */
    async function requestJson(url, options = {}) {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CSRF_TOKEN,
            ...(options.headers || {})
        };

        if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json; charset=utf-8';
            options.body = JSON.stringify({
                ...options.body,
                csrf_token: CSRF_TOKEN
            });
        }

        const response = await fetch(url, {
            ...options,
            headers
        });

        const data = await response.json().catch(() => ({
            ok: false,
            message: 'Respuesta inválida del servidor.'
        }));

        if (!response.ok || data.ok === false) {
            throw new Error(data.message || `Error HTTP ${response.status}`);
        }

        return data;
    }

    /**
     * Configuración base para alertas de SweetAlert2 en tema institucional claro
     */
    function alertaSwal(opciones) {
        return Swal.fire({
            background: '#ffffff',
            color: '#0f172a',
            confirmButtonColor: '#047857',
            cancelButtonColor: '#475569',
            ...opciones
        });
    }

    /**
     * Inicializa el colapsado de la barra lateral con persistencia en localStorage
     */
    function initSidebar() {
        const sidebar = document.getElementById('appSidebar');
        const btnToggle = document.getElementById('btnToggleSidebar');
        if (!sidebar || !btnToggle) return;

        if (localStorage.getItem('juris_sidebar_collapsed') === '1') {
            sidebar.classList.add('sidebar-collapsed');
        }

        btnToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-collapsed');
            const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
            localStorage.setItem('juris_sidebar_collapsed', isCollapsed ? '1' : '0');
        });
    }

    /**
     * Cambia dinámicamente la empresa activa en sesión ($_SESSION['empresa_activa_id'])
     */
    async function cambiarEmpresaActiva(empresaId) {
        const id = Number(empresaId);
        if (!id || id <= 0) return;

        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=empresa&a=cambiarContexto`, {
                method: 'POST',
                body: { empresa_id: id }
            });

            await alertaSwal({
                icon: 'success',
                title: 'Contexto Multi-Empresa Actualizado',
                text: res.message,
                timer: 1300,
                showConfirmButton: false
            });

            window.location.reload();
        } catch (err) {
            alertaSwal({
                icon: 'error',
                title: 'No se pudo cambiar de empresa',
                text: err.message
            });
        }
    }

    // =========================================================================
    // MÓDULO 1: EMPRESAS CLIENTES
    // =========================================================================
    function resetearPreviewLogoEmpresa(logoPath = null) {
        const inputLogo = document.getElementById('emp_logo');
        const imgPreview = document.getElementById('emp_logo_preview');
        const placeholder = document.getElementById('emp_logo_placeholder');
        const wrapEliminar = document.getElementById('emp_eliminar_logo_wrap');
        const chkEliminar = document.getElementById('emp_eliminar_logo');

        if (inputLogo) inputLogo.value = '';
        if (chkEliminar) chkEliminar.checked = false;

        if (logoPath) {
            if (imgPreview) {
                imgPreview.src = `${BASE_URL}/${logoPath.replace(/^\/+/, '')}`;
                imgPreview.classList.remove('hidden');
            }
            placeholder?.classList.add('hidden');
            wrapEliminar?.classList.remove('hidden');
        } else {
            if (imgPreview) {
                imgPreview.src = '';
                imgPreview.classList.add('hidden');
            }
            placeholder?.classList.remove('hidden');
            wrapEliminar?.classList.add('hidden');
        }
    }

    function abrirModalNuevaEmpresa() {
        const modal = document.getElementById('modalEmpresaForm');
        const form = document.getElementById('formEmpresaCliente');
        if (!modal || !form) return;

        form.reset();
        resetearPreviewLogoEmpresa(null);
        document.getElementById('emp_id').value = '0';
        document.getElementById('tituloModalEmpresa').textContent = 'Registrar Nueva Empresa Cliente';
        document.getElementById('subtituloModalEmpresa').textContent = 'Clonación automática de 179 cuentas del Plan de Cuentas Base activada';
        document.getElementById('btnGuardarEmpresa').textContent = 'Crear Empresa y Clonar Plan de Cuentas';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('emp_rif')?.focus();
    }

    async function abrirModalEditarEmpresa(id) {
        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=empresa&a=obtener`, {
                method: 'POST',
                body: { id: Number(id) }
            });
            const emp = res.empresa;

            document.getElementById('emp_id').value = emp.id;
            document.getElementById('emp_rif').value = emp.rif || '';
            document.getElementById('emp_razon_social').value = emp.razon_social || '';
            document.getElementById('emp_nombre_comercial').value = emp.nombre_comercial || '';
            document.getElementById('emp_representante_legal').value = emp.representante_legal || '';
            document.getElementById('emp_direccion_fiscal').value = emp.direccion_fiscal || '';
            document.getElementById('emp_telefono').value = emp.telefono || '';
            document.getElementById('emp_email_contacto').value = emp.email_contacto || '';
            document.getElementById('emp_tipo_contribuyente').value = emp.tipo_contribuyente || 'Ordinario';
            document.getElementById('emp_porcentaje_retencion_iva').value = String(emp.porcentaje_retencion_iva ?? '75');
            document.getElementById('emp_periodo_fiscal_activo').value = emp.periodo_fiscal_activo || '';
            document.getElementById('emp_valor_unidad_tributaria').value = emp.valor_unidad_tributaria ?? '9.00';
            document.getElementById('emp_monto_cestaticket').value = emp.monto_cestaticket_mensual ?? '1800.00';
            resetearPreviewLogoEmpresa(emp.logo_path || null);

            document.getElementById('tituloModalEmpresa').textContent = `Editar Empresa: ${emp.rif}`;
            document.getElementById('subtituloModalEmpresa').textContent = emp.razon_social;
            document.getElementById('btnGuardarEmpresa').textContent = 'Guardar Cambios Fiscales';

            const modal = document.getElementById('modalEmpresaForm');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } catch (err) {
            alertaSwal({
                icon: 'error',
                title: 'Error al cargar empresa',
                text: err.message
            });
        }
    }

    function cerrarModalEmpresa() {
        const modal = document.getElementById('modalEmpresaForm');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function cambiarEstadoEmpresa(id, nuevoEstado, razonSocial) {
        const accion = nuevoEstado === 1 ? 'activar' : 'inactivar';
        const confirmacion = await alertaSwal({
            icon: 'warning',
            title: `¿Desea ${accion} esta empresa?`,
            text: `${razonSocial} cambiará su estado operativo en el escritorio contable.`,
            showCancelButton: true,
            confirmButtonText: `Sí, ${accion}`,
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=empresa&a=cambiarEstado`, {
                method: 'POST',
                body: { id, activo: nuevoEstado }
            });

            await alertaSwal({
                icon: 'success',
                title: 'Estado Actualizado',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            });

            window.location.reload();
        } catch (err) {
            alertaSwal({
                icon: 'error',
                title: 'No se pudo cambiar el estado',
                text: err.message
            });
        }
    }

    // Modal rápido de Plan de Cuentas (usado desde el directorio de empresas)
    async function abrirModalPlanCuentas(empresaId) {
        const modal = document.getElementById('modalVisorPlanCuentas');
        const tbody = document.getElementById('tablaBodyPlanCuentasModal');
        if (!modal || !tbody) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        tbody.innerHTML = '<tr><td colspan="6" class="py-10 text-center text-slate-400 font-mono">Consultando Plan de Cuentas en Base de Datos...</td></tr>';

        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=empresa&a=verPlanCuentas`, {
                method: 'POST',
                body: { empresa_id: Number(empresaId) }
            });
            catalogoActualModal = res.cuentas || [];

            document.getElementById('planModalRif').textContent = res.empresa.rif;
            document.getElementById('planModalTitulo').textContent = `Plan de Cuentas — ${res.empresa.razon_social}`;
            document.getElementById('planModalStatTotal').textContent = res.stats.total_cuentas;
            document.getElementById('planModalStatMov').textContent = res.stats.cuentas_movimiento;

            document.getElementById('inputBuscarCuentaModal').value = '';
            document.getElementById('selectTipoCuentaModal').value = '';

            renderizarTablaPlanCuentasModal();
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-rose-400">${err.message}</td></tr>`;
        }
    }

    function renderizarTablaPlanCuentasModal() {
        const tbody = document.getElementById('tablaBodyPlanCuentasModal');
        if (!tbody) return;

        const termino = (document.getElementById('inputBuscarCuentaModal')?.value || '').trim().toLowerCase();
        const rubro = document.getElementById('selectTipoCuentaModal')?.value || '';

        const filtradas = catalogoActualModal.filter((c) => {
            const coincideRubro = !rubro || c.tipo === rubro;
            const coincideTexto = !termino
                || String(c.codigo).toLowerCase().includes(termino)
                || String(c.nombre).toLowerCase().includes(termino);
            return coincideRubro && coincideTexto;
        });

        if (filtradas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-slate-500">No se encontraron cuentas que coincidan con el filtro.</td></tr>';
            return;
        }

        tbody.innerHTML = filtradas.map((c) => {
            const esMovimiento = Number(c.acepta_movimiento) === 1;
            const nivel = Number(c.nivel) || 1;
            const indentPx = (nivel - 1) * 14;

            return `
                <tr class="${esMovimiento ? 'hover:bg-slate-50 text-slate-700' : 'bg-slate-100 font-semibold text-slate-900'}">
                    <td class="py-2.5 px-5 font-mono ${esMovimiento ? 'text-emerald-700' : 'text-emerald-800 font-bold'}">
                        ${escapeHtml(c.codigo)}
                    </td>
                    <td class="py-2.5 px-4" style="padding-left: ${16 + indentPx}px;">
                        ${escapeHtml(c.nombre)}
                    </td>
                    <td class="py-2.5 px-4 font-mono text-[11px] text-slate-600">
                        ${escapeHtml(c.tipo)}
                    </td>
                    <td class="py-2.5 px-4 font-mono text-[11px] ${c.naturaleza === 'DEUDORA' ? 'text-sky-700' : 'text-amber-700'}">
                        ${escapeHtml(c.naturaleza)}
                    </td>
                    <td class="py-2.5 px-4 text-center font-mono text-slate-500">
                        N${nivel}
                    </td>
                    <td class="py-2.5 px-5 text-right">
                        ${esMovimiento
                            ? '<span class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">Imputable</span>'
                            : '<span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-600 border border-slate-200">Grupo</span>'
                        }
                    </td>
                </tr>
            `;
        }).join('');
    }

    function cerrarModalPlanCuentas() {
        const modal = document.getElementById('modalVisorPlanCuentas');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // =========================================================================
    // MÓDULO 2.1: PLAN DE CUENTAS (?c=plan_cuentas&a=index)
    // =========================================================================
    function aplicarFiltroPlanPage() {
        const filas = document.querySelectorAll('#tablaPlanCuentasPage tbody tr.fila-cuenta');
        if (!filas.length) return;

        const termino = (document.getElementById('inputBuscarPlanPage')?.value || '').trim().toLowerCase();
        let visibles = 0;

        filas.forEach((tr) => {
            const codigo = tr.getAttribute('data-codigo') || '';
            const nombre = tr.getAttribute('data-nombre') || '';
            const grupo = tr.getAttribute('data-grupo') || '';

            const coincideGrupo = !grupoFiltroPlanPage || grupo === grupoFiltroPlanPage;
            const coincideTexto = !termino || codigo.includes(termino) || nombre.includes(termino);

            if (coincideGrupo && coincideTexto) {
                tr.classList.remove('hidden');
                visibles++;
            } else {
                tr.classList.add('hidden');
            }
        });

        const contador = document.getElementById('contadorFilasPlan');
        if (contador) contador.textContent = String(visibles);
    }

    function filtrarPlanPorGrupo(grupo) {
        grupoFiltroPlanPage = grupo || '';
        document.querySelectorAll('.btn-filtro-grupo').forEach((btn) => {
            const btnGrupo = btn.getAttribute('data-grupo') || '';
            if (btnGrupo === grupoFiltroPlanPage) {
                btn.className = 'btn-filtro-grupo px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border bg-emerald-700 text-white border-emerald-700 shadow-sm';
            } else {
                btn.className = 'btn-filtro-grupo px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border bg-white text-slate-700 border-slate-300 hover:bg-slate-50';
            }
        });
        aplicarFiltroPlanPage();
    }

    function abrirModalNuevaCuenta() {
        const modal = document.getElementById('modalCrudCuenta');
        const form = document.getElementById('formCrudCuenta');
        if (!modal || !form) return;

        form.reset();
        document.getElementById('cta_id').value = '0';
        document.getElementById('cta_codigo').readOnly = false;
        document.getElementById('cta_codigo_padre').readOnly = false;
        document.getElementById('tituloModalCuenta').textContent = 'Registrar Nueva Cuenta Contable';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('cta_codigo')?.focus();
    }

    async function abrirModalSubcuenta(codigoPadre) {
        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=plan_cuentas&a=sugerirSubcodigo`, {
                method: 'POST',
                body: { codigo_padre: codigoPadre }
            });
            abrirModalNuevaCuenta();

            document.getElementById('cta_codigo_padre').value = codigoPadre;
            document.getElementById('cta_codigo').value = res.codigo_sugerido || '';
            document.getElementById('cta_tipo').value = res.padre.tipo || 'ACTIVO';
            document.getElementById('cta_naturaleza').value = res.padre.naturaleza || 'DEUDORA';
            document.getElementById('cta_acepta_movimiento').value = '1';
            document.getElementById('tituloModalCuenta').textContent = `Nueva Subcuenta de ${codigoPadre} (${res.padre.nombre})`;
            document.getElementById('cta_nombre')?.focus();
        } catch (err) {
            alertaSwal({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    async function abrirModalEditarCuenta(id) {
        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=plan_cuentas&a=obtener`, {
                method: 'POST',
                body: { id: Number(id) }
            });
            const c = res.cuenta;

            document.getElementById('cta_id').value = c.id;
            document.getElementById('cta_codigo_padre').value = c.codigo_padre || '';
            document.getElementById('cta_codigo_padre').readOnly = true;
            document.getElementById('cta_codigo').value = c.codigo || '';
            document.getElementById('cta_codigo').readOnly = true;
            document.getElementById('cta_nombre').value = c.nombre || '';
            document.getElementById('cta_tipo').value = c.tipo || 'ACTIVO';
            document.getElementById('cta_naturaleza').value = c.naturaleza || 'DEUDORA';
            document.getElementById('cta_acepta_movimiento').value = String(c.acepta_movimiento ?? '1');
            document.getElementById('cta_saldo_inicial').value = c.saldo_inicial ?? '0.00';
            document.getElementById('cta_activo').value = String(c.activo ?? '1');

            document.getElementById('tituloModalCuenta').textContent = `Editar Cuenta ${c.codigo}`;

            const modal = document.getElementById('modalCrudCuenta');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } catch (err) {
            alertaSwal({ icon: 'error', title: 'Error al cargar cuenta', text: err.message });
        }
    }

    function cerrarModalCuenta() {
        const modal = document.getElementById('modalCrudCuenta');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function eliminarCuenta(id, descripcion) {
        const confirmacion = await alertaSwal({
            icon: 'warning',
            title: '¿Eliminar cuenta contable?',
            text: `Se eliminará la cuenta "${descripcion}" por no poseer movimientos ni subcuentas dependientes.`,
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=plan_cuentas&a=eliminar`, {
                method: 'POST',
                body: { id }
            });

            await alertaSwal({
                icon: 'success',
                title: 'Cuenta Eliminada',
                text: res.message,
                timer: 1600,
                showConfirmButton: false
            });

            window.location.reload();
        } catch (err) {
            alertaSwal({ icon: 'error', title: 'Operación bloqueada', text: err.message });
        }
    }

    // =========================================================================
    // MÓDULO 2.2: LIBRO DIARIO Y ASIENTOS CONTABLES (PARTIDA DOBLE)
    // =========================================================================
    function obtenerCuentasImputablesMemoria() {
        const scriptEl = document.getElementById('jsonCuentasImputables');
        if (!scriptEl) return [];
        try {
            return JSON.parse(scriptEl.textContent || '[]');
        } catch (e) {
            return [];
        }
    }

    function agregarFilaAsiento(datos = {}) {
        const tbody = document.getElementById('tbodyRenglonesAsiento');
        if (!tbody) return;

        const cuentas = obtenerCuentasImputablesMemoria();
        const indice = tbody.querySelectorAll('tr').length + 1;

        const optionsHtml = [
            '<option value="">-- Seleccione cuenta de movimiento --</option>',
            ...cuentas.map((c) => {
                const selected = Number(datos.cuenta_id) === Number(c.id) ? 'selected' : '';
                return `<option value="${c.id}" ${selected}>${escapeHtml(c.codigo)} — ${escapeHtml(c.nombre)}</option>`;
            })
        ].join('');

        const tr = document.createElement('tr');
        tr.className = 'fila-renglon-asiento hover:bg-slate-50 transition-colors';
        tr.innerHTML = `
            <td class="py-2 px-3 text-center font-mono text-slate-500 num-renglon">${indice}</td>
            <td class="py-2 px-3">
                <select class="input-cuenta w-full bg-white border border-slate-300 focus:border-emerald-600 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 focus:outline-none" required>
                    ${optionsHtml}
                </select>
            </td>
            <td class="py-2 px-3">
                <input type="text" class="input-descripcion w-full bg-white border border-slate-300 focus:border-emerald-600 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none"
                       placeholder="Detalle del movimiento (opcional)" value="${escapeHtml(datos.descripcion || '')}">
            </td>
            <td class="py-2 px-3">
                <input type="number" step="0.01" min="0" class="input-debe w-full bg-white border border-slate-300 focus:border-emerald-600 rounded-lg px-2.5 py-1.5 text-xs font-mono text-right text-slate-900 focus:outline-none"
                       value="${datos.debe ?? '0.00'}">
            </td>
            <td class="py-2 px-3">
                <input type="number" step="0.01" min="0" class="input-haber w-full bg-white border border-slate-300 focus:border-emerald-600 rounded-lg px-2.5 py-1.5 text-xs font-mono text-right text-slate-900 focus:outline-none"
                       value="${datos.haber ?? '0.00'}">
            </td>
            <td class="py-2 px-3 text-center">
                <button type="button" class="btn-quitar-fila p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Quitar línea">
                    <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </td>
        `;

        const inputDebe = tr.querySelector('.input-debe');
        const inputHaber = tr.querySelector('.input-haber');
        const btnQuitar = tr.querySelector('.btn-quitar-fila');

        // Si escribe en Debe, poner Haber en 0.00 automáticamente y viceversa
        inputDebe.addEventListener('input', () => {
            if (parseFloat(inputDebe.value) > 0) {
                inputHaber.value = '0.00';
            }
            recalcularTotalesAsiento();
        });

        inputHaber.addEventListener('input', () => {
            if (parseFloat(inputHaber.value) > 0) {
                inputDebe.value = '0.00';
            }
            recalcularTotalesAsiento();
        });

        btnQuitar.addEventListener('click', () => {
            const totalFilas = tbody.querySelectorAll('tr').length;
            if (totalFilas <= 2) {
                alertaSwal({
                    icon: 'info',
                    title: 'Partida Doble',
                    text: 'Todo asiento contable debe mantener como mínimo 2 líneas de detalle.'
                });
                return;
            }
            tr.remove();
            renumerarFilasAsiento();
            recalcularTotalesAsiento();
        });

        tbody.appendChild(tr);
        recalcularTotalesAsiento();
    }

    function renumerarFilasAsiento() {
        document.querySelectorAll('#tbodyRenglonesAsiento tr').forEach((tr, idx) => {
            const numCell = tr.querySelector('.num-renglon');
            if (numCell) numCell.textContent = String(idx + 1);
        });
    }

    /**
     * Calcula en tiempo real Total Debe, Total Haber y Diferencia, bloqueando o habilitando el botón Guardar
     */
    function recalcularTotalesAsiento() {
        const filas = document.querySelectorAll('#tbodyRenglonesAsiento tr');
        if (!filas.length) return;

        let sumaDebe = 0;
        let sumaHaber = 0;

        filas.forEach((tr) => {
            const d = parseFloat(tr.querySelector('.input-debe')?.value || '0') || 0;
            const h = parseFloat(tr.querySelector('.input-haber')?.value || '0') || 0;
            sumaDebe = Math.round((sumaDebe + d) * 100) / 100;
            sumaHaber = Math.round((sumaHaber + h) * 100) / 100;
        });

        const diferencia = Math.round(Math.abs(sumaDebe - sumaHaber) * 100) / 100;
        const estaCuadrado = sumaDebe > 0 && sumaHaber > 0 && diferencia < 0.005;

        const lblDebe = document.getElementById('lblTotalDebeAsiento');
        const lblHaber = document.getElementById('lblTotalHaberAsiento');
        const lblDif = document.getElementById('lblDiferenciaAsiento');
        const badge = document.getElementById('badgeEstadoCuadre');
        const btnGuardar = document.getElementById('btnGuardarAsiento');

        if (lblDebe) lblDebe.textContent = formatVes(sumaDebe);
        if (lblHaber) lblHaber.textContent = formatVes(sumaHaber);
        if (lblDif) lblDif.textContent = formatVes(diferencia);

        if (badge) {
            if (sumaDebe === 0 && sumaHaber === 0) {
                badge.className = 'px-3.5 py-1.5 rounded-xl text-xs font-mono font-bold bg-slate-800 text-slate-300 border border-slate-700';
                badge.textContent = 'INGRESE MONTOS EN DEBE Y HABER (Bs. 0,00)';
            } else if (estaCuadrado) {
                badge.className = 'px-3.5 py-1.5 rounded-xl text-xs font-mono font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
                badge.textContent = `✓ PARTIDA DOBLE CUADRADA (${formatVes(sumaDebe)})`;
            } else {
                badge.className = 'px-3.5 py-1.5 rounded-xl text-xs font-mono font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40';
                badge.textContent = `✗ DESCUADRADO POR ${formatVes(diferencia)}`;
            }
        }

        if (btnGuardar) {
            btnGuardar.disabled = !estaCuadrado;
        }
    }

    /**
     * Completa automáticamente la diferencia de cuadre en la última línea del formulario de asiento
     */
    function cuadrarUltimaFila() {
        const filas = Array.from(document.querySelectorAll('#tbodyRenglonesAsiento tr'));
        if (filas.length < 2) return;

        const ultima = filas[filas.length - 1];
        let debePrevio = 0;
        let haberPrevio = 0;

        filas.slice(0, -1).forEach((tr) => {
            debePrevio += parseFloat(tr.querySelector('.input-debe')?.value || '0') || 0;
            haberPrevio += parseFloat(tr.querySelector('.input-haber')?.value || '0') || 0;
        });

        const diff = Math.round((debePrevio - haberPrevio) * 100) / 100;
        const inputDebeUlt = ultima.querySelector('.input-debe');
        const inputHaberUlt = ultima.querySelector('.input-haber');

        if (diff > 0) {
            inputDebeUlt.value = '0.00';
            inputHaberUlt.value = diff.toFixed(2);
        } else if (diff < 0) {
            inputHaberUlt.value = '0.00';
            inputDebeUlt.value = Math.abs(diff).toFixed(2);
        }

        recalcularTotalesAsiento();
    }

    function reiniciarFormularioAsiento() {
        const tbody = document.getElementById('tbodyRenglonesAsiento');
        if (!tbody) return;
        tbody.innerHTML = '';
        agregarFilaAsiento();
        agregarFilaAsiento();
    }

    function togglePanelNuevoAsiento() {
        const panel = document.getElementById('panelNuevoAsiento');
        if (!panel) return;
        panel.classList.toggle('hidden');
    }

    async function verDetalleAsientoModal(id) {
        const modal = document.getElementById('modalDetalleAsiento');
        if (!modal) return;

        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=contabilidad&a=verAsiento`, {
                method: 'POST',
                body: { id: Number(id) }
            });
            const a = res.asiento;

            document.getElementById('modalAsiNumero').textContent = `Comprobante ${a.numero_asiento} (${a.estado})`;
            document.getElementById('modalAsiFecha').textContent = `Fecha: ${a.fecha} | Período: ${a.periodo} | Ref: ${a.referencia || 'N/A'}`;
            document.getElementById('modalAsiConcepto').textContent = a.concepto;
            document.getElementById('modalAsiTotalDebe').textContent = formatVes(a.total_debe);
            document.getElementById('modalAsiTotalHaber').textContent = formatVes(a.total_haber);

            const tbody = document.getElementById('modalAsiTbody');
            tbody.innerHTML = (a.detalles || []).map((d) => `
                <tr>
                    <td class="py-2 px-4 font-mono text-emerald-400">${escapeHtml(d.cuenta_codigo)}</td>
                    <td class="py-2 px-4 text-slate-200">${escapeHtml(d.cuenta_nombre)}</td>
                    <td class="py-2 px-4 text-slate-400">${escapeHtml(d.descripcion || '')}</td>
                    <td class="py-2 px-4 font-mono text-right">${formatVes(d.debe, false)}</td>
                    <td class="py-2 px-4 font-mono text-right">${formatVes(d.haber, false)}</td>
                </tr>
            `).join('');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } catch (err) {
            alertaSwal({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    function cerrarModalDetalleAsiento() {
        const modal = document.getElementById('modalDetalleAsiento');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function anularAsiento(id, numeroAsiento) {
        const confirmacion = await alertaSwal({
            icon: 'warning',
            title: `¿Anular Comprobante ${numeroAsiento}?`,
            text: 'El asiento pasará a estado ANULADO excluyendo sus saldos de los libros y estados financieros, sin borrar el registro histórico.',
            input: 'text',
            inputPlaceholder: 'Motivo de la anulación (ej. Error en imputación de cuenta)',
            showCancelButton: true,
            confirmButtonText: 'Sí, anular asiento',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=contabilidad&a=anularAsiento`, {
                method: 'POST',
                body: {
                    id,
                    motivo: confirmacion.value || 'Anulación autorizada'
                }
            });

            await alertaSwal({
                icon: 'success',
                title: 'Asiento Anulado',
                text: res.message,
                timer: 1800,
                showConfirmButton: false
            });

            window.location.reload();
        } catch (err) {
            alertaSwal({ icon: 'error', title: 'No se pudo anular', text: err.message });
        }
    }

    // =========================================================================
    // MÓDULO 2.4: ESTADOS FINANCIEROS (CAMBIO DE PESTAÑAS)
    // =========================================================================
    function cambiarTabEstados(tab) {
        document.querySelectorAll('.seccion-estado').forEach((sec) => sec.classList.add('hidden'));
        document.getElementById(`seccionEstado_${tab}`)?.classList.remove('hidden');

        document.querySelectorAll('.btn-tab-estado').forEach((btn) => {
            if (btn.getAttribute('data-tab') === tab) {
                btn.className = 'btn-tab-estado px-4 py-2 rounded-xl text-xs font-semibold transition-all border bg-emerald-700 text-white border-emerald-700 shadow-sm';
            } else {
                btn.className = 'btn-tab-estado px-4 py-2 rounded-xl text-xs font-semibold transition-all border bg-white text-slate-700 border-slate-300 hover:bg-slate-50';
            }
        });

        const inputTab = document.getElementById('inputTabActivaEstados');
        if (inputTab) inputTab.value = tab;

        const titulos = {
            comprobacion: 'BALANCE DE COMPROBACIÓN DE SUMAS Y SALDOS',
            resultados: 'ESTADO DE RESULTADOS (GANANCIAS Y PÉRDIDAS)',
            general: 'BALANCE GENERAL (ESTADO DE SITUACIÓN FINANCIERA)'
        };
        const lblMembrete = document.getElementById('tituloMembreteReporte');
        if (lblMembrete && titulos[tab]) {
            lblMembrete.textContent = titulos[tab];
        }

        const linkCsv = document.getElementById('linkExportarEstadosCsv');
        if (linkCsv) {
            const url = new URL(linkCsv.href, window.location.origin);
            url.searchParams.set('tab', tab);
            linkCsv.href = url.toString();
        }
    }

    function notificarFase(nombreModulo, numeroFase) {
        alertaSwal({
            icon: 'info',
            title: `Módulo Programado — Fase ${numeroFase}`,
            html: `<p class="text-xs leading-relaxed">
                    El módulo <strong>${escapeHtml(nombreModulo)}</strong> ya cuenta con sus tablas relacionales creadas en
                    <code>database/juris_contable.sql</code> y se activará en la <strong>Fase ${numeroFase}</strong>.
                   </p>`,
            confirmButtonText: 'Entendido'
        });
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initRifFormatter() {
        document.querySelectorAll('#emp_rif, .input-rif-format').forEach((rifInput) => {
            rifInput.addEventListener('blur', () => {
                const raw = rifInput.value.toUpperCase().replace(/[^VEJGP0-9]/g, '');
                if (/^[VEJGP]\d{9}$/.test(raw)) {
                    rifInput.value = `${raw.slice(0, 1)}-${raw.slice(1, 9)}-${raw.slice(9, 10)}`;
                }
            });
        });
    }

    // =========================================================================
    // MÓDULO 3: TRIBUTARIO SENIAT (LIBROS IVA Y RETENCIONES IVA / ISLR)
    // =========================================================================
    function cambiarTabLibros(tab) {
        document.querySelectorAll('.seccion-libro').forEach((sec) => sec.classList.add('hidden'));
        document.getElementById(`seccionLibro_${tab}`)?.classList.remove('hidden');

        document.querySelectorAll('.btn-tab-libro').forEach((btn) => {
            if (btn.getAttribute('data-tab') === tab) {
                btn.className = 'btn-tab-libro px-4 py-2 rounded-xl text-xs font-semibold transition-all border bg-emerald-700 text-white border-emerald-700 shadow-sm';
            } else {
                btn.className = 'btn-tab-libro px-4 py-2 rounded-xl text-xs font-semibold transition-all border bg-white text-slate-700 border-slate-300 hover:bg-slate-50';
            }
        });

        const inputTab = document.getElementById('inputTabActivaLibros');
        if (inputTab) inputTab.value = tab;

        const lblTitulo = document.getElementById('lblTituloLibroMembrete');
        if (lblTitulo) {
            if (tab === 'ventas') lblTitulo.textContent = 'LIBRO AUXILIAR DE VENTAS';
            else if (tab === 'resumen72') lblTitulo.textContent = 'RESUMEN FISCAL ARTÍCULO 72 (FORMA 30 SENIAT)';
            else lblTitulo.textContent = 'LIBRO AUXILIAR DE COMPRAS NACIONALES';
        }

        const linkCsv = document.getElementById('linkExportarLibroCsv');
        if (linkCsv && (tab === 'compras' || tab === 'ventas')) {
            const url = new URL(linkCsv.href, window.location.origin);
            url.searchParams.set('libro', tab);
            linkCsv.href = url.toString();
        }
    }

    function cambiarTabRetenciones(tab) {
        document.querySelectorAll('.seccion-ret').forEach((sec) => sec.classList.add('hidden'));
        document.getElementById(`seccionRet_${tab}`)?.classList.remove('hidden');

        document.querySelectorAll('.btn-tab-ret').forEach((btn) => {
            if (btn.getAttribute('data-tab') === tab) {
                btn.className = 'btn-tab-ret px-4 py-2 rounded-xl text-xs font-semibold transition-all border bg-emerald-700 text-white border-emerald-700 shadow-sm';
            } else {
                btn.className = 'btn-tab-ret px-4 py-2 rounded-xl text-xs font-semibold transition-all border bg-white text-slate-700 border-slate-300 hover:bg-slate-50';
            }
        });

        const inputTab = document.getElementById('inputTabActivaRet');
        if (inputTab) inputTab.value = tab;
    }

    function abrirModalCompra() {
        const modal = document.getElementById('modalRegistrarCompra');
        if (!modal) return;
        document.getElementById('formRegistrarCompra')?.reset();
        recalcularModalCompra();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalCompra() {
        const modal = document.getElementById('modalRegistrarCompra');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function recalcularModalCompra() {
        const exentas = parseFloat(document.getElementById('comp_exentas')?.value || '0') || 0;
        const baseGen = parseFloat(document.getElementById('comp_base_gen')?.value || '0') || 0;
        const baseRed = parseFloat(document.getElementById('comp_base_red')?.value || '0') || 0;
        const baseAdi = parseFloat(document.getElementById('comp_base_adi')?.value || '0') || 0;
        const baseIgtf = parseFloat(document.getElementById('comp_base_igtf')?.value || '0') || 0;
        const porcRet = parseFloat(document.getElementById('comp_porc_ret')?.value || '0') || 0;

        const ivaGen = Math.round(baseGen * 16) / 100;
        const ivaRed = Math.round(baseRed * 8) / 100;
        const ivaAdi = Math.round(baseAdi * 31) / 100;
        const montoIgtf = Math.round(baseIgtf * 3) / 100;

        const ivaTotal = Math.round((ivaGen + ivaRed + ivaAdi) * 100) / 100;
        const totalCompra = Math.round((exentas + baseGen + baseRed + baseAdi + ivaTotal + montoIgtf) * 100) / 100;
        const ivaRetenido = Math.round(ivaTotal * (porcRet / 100) * 100) / 100;

        const elIvaGen = document.getElementById('comp_iva_gen');
        const elRetVista = document.getElementById('comp_iva_retenido_vista');
        const elTotVista = document.getElementById('comp_total_vista');

        if (elIvaGen) elIvaGen.value = formatVes(ivaGen, false);
        if (elRetVista) elRetVista.value = formatVes(ivaRetenido);
        if (elTotVista) elTotVista.textContent = formatVes(totalCompra);
    }

    function abrirModalVenta() {
        const modal = document.getElementById('modalRegistrarVenta');
        if (!modal) return;
        document.getElementById('formRegistrarVenta')?.reset();
        recalcularModalVenta();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalVenta() {
        const modal = document.getElementById('modalRegistrarVenta');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function recalcularModalVenta() {
        const exentas = parseFloat(document.getElementById('ven_exentas')?.value || '0') || 0;
        const baseGen = parseFloat(document.getElementById('ven_base_gen')?.value || '0') || 0;
        const baseIgtf = parseFloat(document.getElementById('ven_base_igtf')?.value || '0') || 0;

        const ivaGen = Math.round(baseGen * 16) / 100;
        const montoIgtf = Math.round(baseIgtf * 3) / 100;
        const totalVenta = Math.round((exentas + baseGen + ivaGen + montoIgtf) * 100) / 100;

        const elIvaGen = document.getElementById('ven_iva_gen');
        const elTotVista = document.getElementById('ven_total_vista');

        if (elIvaGen) elIvaGen.value = formatVes(ivaGen, false);
        if (elTotVista) elTotVista.textContent = formatVes(totalVenta);
    }

    function abrirModalConfigFiscal() {
        const modal = document.getElementById('modalConfigFiscal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalConfigFiscal() {
        const modal = document.getElementById('modalConfigFiscal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function recalcularSeleccionRetIva() {
        const checks = Array.from(document.querySelectorAll('.chk-factura-ret-iva:checked'));
        const porc = parseFloat(document.getElementById('ret_iva_porcentaje')?.value || '75') || 75;
        let sumaIva = 0;
        let direccionSugerida = '';

        checks.forEach((chk) => {
            sumaIva += parseFloat(chk.getAttribute('data-iva') || '0') || 0;
            if (!direccionSugerida && chk.getAttribute('data-dir')) {
                direccionSugerida = chk.getAttribute('data-dir');
            }
        });

        const inputDir = document.getElementById('ret_iva_direccion');
        if (inputDir && !inputDir.value && direccionSugerida) {
            inputDir.value = direccionSugerida;
        }

        const montoRetener = Math.round(sumaIva * (porc / 100) * 100) / 100;
        const preview = document.getElementById('ret_iva_monto_preview');
        if (preview) {
            preview.value = formatVes(montoRetener);
        }
    }

    function seleccionarConceptoIslr() {
        const select = document.getElementById('islr_select_concepto');
        if (!select) return;
        const opt = select.options[select.selectedIndex];
        if (!opt) return;

        document.getElementById('islr_codigo_concepto').value = opt.value;
        document.getElementById('islr_descripcion_concepto').value = opt.getAttribute('data-nombre') || '';
        document.getElementById('islr_tipo_persona').value = opt.getAttribute('data-persona') || 'PJD';
        document.getElementById('islr_porcentaje').value = parseFloat(opt.getAttribute('data-tarifa') || '5').toFixed(2);

        recalcularIslr();
    }

    function recalcularIslr() {
        const form = document.getElementById('formEmitirRetencionIslr');
        if (!form) return;

        const ut = parseFloat(form.getAttribute('data-ut') || '9') || 9;
        const factor = parseFloat(form.getAttribute('data-factor') || '83.3334') || 83.3334;
        const base = parseFloat(document.getElementById('islr_base_imponible')?.value || '0') || 0;
        const porc = parseFloat(document.getElementById('islr_porcentaje')?.value || '0') || 0;
        const tipoPersona = document.getElementById('islr_tipo_persona')?.value || 'PJD';

        const sustraendo = tipoPersona === 'PNR'
            ? Math.round(ut * factor * (porc / 100) * 100) / 100
            : 0;

        const impuestoBruto = Math.round(base * (porc / 100) * 100) / 100;
        const retenido = Math.max(0, Math.round((impuestoBruto - sustraendo) * 100) / 100);

        const elSust = document.getElementById('islr_sustraendo_vista');
        const elRet = document.getElementById('islr_retenido_vista');
        const elMontoTot = document.getElementById('islr_monto_total');

        if (elSust) elSust.value = formatVes(sustraendo, false);
        if (elRet) elRet.value = formatVes(retenido);
        if (elMontoTot && (parseFloat(elMontoTot.value) || 0) === 0 && base > 0) {
            elMontoTot.value = (Math.round(base * 1.16 * 100) / 100).toFixed(2);
        }
    }

    // =========================================================================
    // INICIALIZACIÓN DOM
    // =========================================================================
    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initRifFormatter();

        // Selector desplegable global de empresa activa
        const selectorGlobal = document.getElementById('selectorEmpresaGlobal');
        if (selectorGlobal) {
            selectorGlobal.addEventListener('change', (e) => {
                cambiarEmpresaActiva(e.target.value);
            });
        }

        // Vista previa instantánea del logo en modal de Empresa Cliente
        const inputLogo = document.getElementById('emp_logo');
        if (inputLogo) {
            inputLogo.addEventListener('change', (e) => {
                const file = e.target.files?.[0];
                const imgPreview = document.getElementById('emp_logo_preview');
                const placeholder = document.getElementById('emp_logo_placeholder');
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (ev) => {
                    if (imgPreview) {
                        imgPreview.src = String(ev.target?.result || '');
                        imgPreview.classList.remove('hidden');
                    }
                    placeholder?.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            });
        }

        // Formulario de Empresa Cliente (FormData para soportar carga de logo)
        const formEmpresa = document.getElementById('formEmpresaCliente');
        if (formEmpresa) {
            formEmpresa.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btnSubmit = document.getElementById('btnGuardarEmpresa');
                const textoOriginal = btnSubmit.textContent;
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Procesando...';

                const formData = new FormData(formEmpresa);

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=empresa&a=guardar`, {
                        method: 'POST',
                        body: formData
                    });

                    cerrarModalEmpresa();
                    await alertaSwal({
                        icon: 'success',
                        title: 'Operación Exitosa',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Validación Fiscal', text: err.message });
                } finally {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = textoOriginal;
                }
            });
        }

        // Formulario CRUD de Cuenta Contable
        const formCuenta = document.getElementById('formCrudCuenta');
        if (formCuenta) {
            // Auto-seleccionar naturaleza según el grupo contable
            const selectTipo = document.getElementById('cta_tipo');
            const selectNat = document.getElementById('cta_naturaleza');
            selectTipo?.addEventListener('change', () => {
                const deudoras = ['ACTIVO', 'COSTO', 'GASTOS'];
                selectNat.value = deudoras.includes(selectTipo.value) ? 'DEUDORA' : 'ACREEDORA';
            });

            formCuenta.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnGuardarCuenta');
                btn.disabled = true;

                const payload = Object.fromEntries(new FormData(formCuenta).entries());

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=plan_cuentas&a=guardar`, {
                        method: 'POST',
                        body: payload
                    });

                    cerrarModalCuenta();
                    await alertaSwal({
                        icon: 'success',
                        title: 'Plan de Cuentas Actualizado',
                        text: res.message,
                        timer: 1600,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Error de Validación', text: err.message });
                } finally {
                    btn.disabled = false;
                }
            });
        }

        // Buscador en tiempo real en la vista dedicada del Plan de Cuentas
        document.getElementById('inputBuscarPlanPage')?.addEventListener('input', aplicarFiltroPlanPage);

        // Inicializar 2 líneas por defecto en el formulario de Nuevo Asiento Contable
        if (document.getElementById('tbodyRenglonesAsiento')) {
            reiniciarFormularioAsiento();
        }

        // Envío del formulario de Nuevo Asiento Contable (Partida Doble)
        const formAsiento = document.getElementById('formNuevoAsiento');
        if (formAsiento) {
            formAsiento.addEventListener('submit', async (e) => {
                e.preventDefault();

                const renglones = [];
                document.querySelectorAll('#tbodyRenglonesAsiento tr').forEach((tr) => {
                    const cuentaId = Number(tr.querySelector('.input-cuenta')?.value || 0);
                    const descripcion = tr.querySelector('.input-descripcion')?.value || '';
                    const debe = parseFloat(tr.querySelector('.input-debe')?.value || '0') || 0;
                    const haber = parseFloat(tr.querySelector('.input-haber')?.value || '0') || 0;

                    if (cuentaId > 0 && (debe > 0 || haber > 0)) {
                        renglones.push({
                            cuenta_id: cuentaId,
                            descripcion,
                            debe,
                            haber
                        });
                    }
                });

                const btn = document.getElementById('btnGuardarAsiento');
                btn.disabled = true;

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=contabilidad&a=guardarAsiento`, {
                        method: 'POST',
                        body: {
                            fecha: document.getElementById('asi_fecha')?.value || '',
                            referencia: document.getElementById('asi_referencia')?.value || '',
                            concepto: document.getElementById('asi_concepto')?.value || '',
                            renglones
                        }
                    });

                    await alertaSwal({
                        icon: 'success',
                        title: 'Asiento Contable Registrado',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    window.location.reload();
                } catch (err) {
                    alertaSwal({
                        icon: 'error',
                        title: 'Validación de Partida Doble',
                        text: err.message
                    });
                    recalcularTotalesAsiento();
                }
            });
        }

        // Eventos del modal visor rápido del Plan de Cuentas
        document.getElementById('inputBuscarCuentaModal')?.addEventListener('input', renderizarTablaPlanCuentasModal);
        document.getElementById('selectTipoCuentaModal')?.addEventListener('change', renderizarTablaPlanCuentasModal);

        // Eventos de cálculo en vivo en los modales de Compra y Venta (Fase 3)
        document.querySelectorAll('.calc-compra').forEach((el) => {
            el.addEventListener('input', recalcularModalCompra);
            el.addEventListener('change', recalcularModalCompra);
        });
        document.querySelectorAll('.calc-venta').forEach((el) => {
            el.addEventListener('input', recalcularModalVenta);
            el.addEventListener('change', recalcularModalVenta);
        });

        // Envío Formulario Registrar Factura de Compra
        const formCompra = document.getElementById('formRegistrarCompra');
        if (formCompra) {
            formCompra.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnSubmitCompra');
                btn.disabled = true;
                const payload = Object.fromEntries(new FormData(formCompra).entries());

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=tributario&a=guardarCompra`, {
                        method: 'POST',
                        body: payload
                    });
                    cerrarModalCompra();
                    await alertaSwal({
                        icon: 'success',
                        title: 'Compra Registrada',
                        text: res.message,
                        timer: 2200,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Validación SENIAT', text: err.message });
                } finally {
                    btn.disabled = false;
                }
            });
        }

        // Envío Formulario Registrar Factura de Venta
        const formVenta = document.getElementById('formRegistrarVenta');
        if (formVenta) {
            formVenta.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnSubmitVenta');
                btn.disabled = true;
                const payload = Object.fromEntries(new FormData(formVenta).entries());

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=tributario&a=guardarVenta`, {
                        method: 'POST',
                        body: payload
                    });
                    cerrarModalVenta();
                    await alertaSwal({
                        icon: 'success',
                        title: 'Venta Registrada',
                        text: res.message,
                        timer: 2200,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Validación SENIAT', text: err.message });
                } finally {
                    btn.disabled = false;
                }
            });
        }

        // Envío Formulario Configuración Tributaria (UT, Sustraendo, Excedente)
        const formConfigFiscal = document.getElementById('formConfigFiscal');
        if (formConfigFiscal) {
            formConfigFiscal.addEventListener('submit', async (e) => {
                e.preventDefault();
                const payload = Object.fromEntries(new FormData(formConfigFiscal).entries());

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=tributario&a=guardarConfigFiscal`, {
                        method: 'POST',
                        body: payload
                    });
                    cerrarModalConfigFiscal();
                    await alertaSwal({
                        icon: 'success',
                        title: 'Parámetros Actualizados',
                        text: res.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Error', text: err.message });
                }
            });
        }

        // Envío Formulario Emitir Comprobante de Retención de IVA
        const formRetIva = document.getElementById('formEmitirRetencionIva');
        if (formRetIva) {
            formRetIva.addEventListener('submit', async (e) => {
                e.preventDefault();
                const checks = Array.from(document.querySelectorAll('.chk-factura-ret-iva:checked'));
                if (checks.length === 0) {
                    alertaSwal({
                        icon: 'warning',
                        title: 'Seleccione Facturas',
                        text: 'Debe marcar al menos una factura del Libro de Compras para emitir su comprobante de retención.'
                    });
                    return;
                }

                const rifs = new Set(checks.map((c) => c.getAttribute('data-rif')));
                if (rifs.size > 1) {
                    alertaSwal({
                        icon: 'error',
                        title: 'Múltiples Proveedores',
                        text: 'Solo puede agrupar en un mismo comprobante facturas pertenecientes a un mismo RIF de proveedor.'
                    });
                    return;
                }

                const formData = new FormData(formRetIva);
                const payload = {
                    fecha_emision: formData.get('fecha_emision'),
                    periodo_fiscal: formData.get('periodo_fiscal'),
                    porcentaje_retencion: formData.get('porcentaje_retencion'),
                    direccion_proveedor: formData.get('direccion_proveedor'),
                    compras_ids: checks.map((c) => Number(c.value))
                };

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=tributario&a=emitirRetencionIva`, {
                        method: 'POST',
                        body: payload
                    });

                    await alertaSwal({
                        icon: 'success',
                        title: 'Comprobante IVA Emitido',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Error al emitir retención', text: err.message });
                }
            });
        }

        // Envío Formulario Emitir Comprobante de Retención de ISLR
        const formRetIslr = document.getElementById('formEmitirRetencionIslr');
        if (formRetIslr) {
            formRetIslr.addEventListener('submit', async (e) => {
                e.preventDefault();
                const payload = Object.fromEntries(new FormData(formRetIslr).entries());

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=tributario&a=emitirRetencionIslr`, {
                        method: 'POST',
                        body: payload
                    });

                    await alertaSwal({
                        icon: 'success',
                        title: 'Comprobante ISLR Emitido',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Error al emitir ISLR', text: err.message });
                }
            });
        }

        // Inicialización de Fase 4: Nómina y Beneficios LOTTT
        if (document.getElementById('tablaPreNomina')) {
            recalcularTotalesGeneralesNomina();
        }
        if (document.getElementById('tablaPreCestaticket')) {
            recalcularTodasLasFilasCestaticket();
        }

        // Formulario Crear / Editar Trabajador (Fase 4)
        const formTrabajador = document.getElementById('formTrabajador');
        if (formTrabajador) {
            formTrabajador.addEventListener('submit', async (e) => {
                e.preventDefault();
                const payload = {
                    id: Number(document.getElementById('trab_id')?.value || 0),
                    cedula: document.getElementById('trab_cedula')?.value || '',
                    nombres: document.getElementById('trab_nombres')?.value || '',
                    apellidos: document.getElementById('trab_apellidos')?.value || '',
                    telefono: document.getElementById('trab_telefono')?.value || '',
                    email: document.getElementById('trab_email')?.value || '',
                    fecha_nacimiento: document.getElementById('trab_fecha_nacimiento')?.value || '',
                    cargo: document.getElementById('trab_cargo')?.value || '',
                    departamento: document.getElementById('trab_departamento')?.value || 'Administración',
                    tipo_trabajador: document.getElementById('trab_tipo_trabajador')?.value || 'EMPLEADO',
                    tipo_nomina: document.getElementById('trab_tipo_nomina')?.value || 'QUINCENAL',
                    salario_mensual: parseFloat(document.getElementById('trab_salario_mensual')?.value || '0') || 0,
                    fecha_ingreso: document.getElementById('trab_fecha_ingreso')?.value || '',
                    estatus_laboral: document.getElementById('trab_estatus_laboral')?.value || 'ACTIVO',
                    banco: document.getElementById('trab_banco')?.value || '',
                    numero_cuenta: document.getElementById('trab_numero_cuenta')?.value || '',
                    porcentaje_islr: parseFloat(document.getElementById('trab_porcentaje_islr')?.value || '0') || 0,
                    aplica_sso: document.getElementById('trab_aplica_sso')?.checked ? 1 : 0,
                    aplica_rpe: document.getElementById('trab_aplica_rpe')?.checked ? 1 : 0,
                    aplica_faov: document.getElementById('trab_aplica_faov')?.checked ? 1 : 0,
                    aplica_cestaticket: document.getElementById('trab_aplica_cestaticket')?.checked ? 1 : 0
                };

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=nomina&a=guardarTrabajador`, {
                        method: 'POST',
                        body: payload
                    });
                    cerrarModalTrabajador();
                    await alertaSwal({
                        icon: 'success',
                        title: 'Ficha Laboral Guardada',
                        text: res.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Validación LOTTT', text: err.message });
                }
            });
        }

        // Formulario Configuración Laboral (Fase 4)
        const formConfigLaboral = document.getElementById('formConfigLaboral');
        if (formConfigLaboral) {
            formConfigLaboral.addEventListener('submit', async (e) => {
                e.preventDefault();
                const payload = {
                    monto_cestaticket_mensual: parseFloat(document.getElementById('cfg_monto_cestaticket')?.value || '1800') || 1800,
                    salario_minimo_referencia: parseFloat(document.getElementById('cfg_salario_minimo')?.value || '15000') || 15000,
                    tope_salarios_minimos_sso: parseInt(document.getElementById('cfg_tope_sso')?.value || '5', 10) || 5,
                    porcentaje_sso_empleado: parseFloat(document.getElementById('cfg_sso_emp')?.value || '4') || 4,
                    porcentaje_rpe_empleado: parseFloat(document.getElementById('cfg_rpe_emp')?.value || '0.5') || 0.5,
                    porcentaje_faov_empleado: parseFloat(document.getElementById('cfg_faov_emp')?.value || '1') || 1,
                    porcentaje_sso_patronal: parseFloat(document.getElementById('cfg_sso_pat')?.value || '9') || 9,
                    porcentaje_rpe_patronal: parseFloat(document.getElementById('cfg_rpe_pat')?.value || '2') || 2,
                    porcentaje_faov_patronal: parseFloat(document.getElementById('cfg_faov_pat')?.value || '2') || 2,
                    porcentaje_inces_patronal: parseFloat(document.getElementById('cfg_inces_pat')?.value || '2') || 2,
                    dias_base_calculo: parseInt(document.getElementById('cfg_dias_base')?.value || '30', 10) || 30,
                    factor_hora_extra_diurna: parseFloat(document.getElementById('cfg_factor_he')?.value || '1.5') || 1.5,
                    factor_bono_nocturno: parseFloat(document.getElementById('cfg_factor_bn')?.value || '0.3') || 0.3
                };

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=nomina&a=guardarConfigLaboral`, {
                        method: 'POST',
                        body: payload
                    });
                    cerrarModalConfigLaboral();
                    await alertaSwal({
                        icon: 'success',
                        title: 'Parámetros Actualizados',
                        text: res.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Error', text: err.message });
                }
            });
        }

        // Formulario Global Tasa Oficial Dólar BCV
        const formTasaBcv = document.getElementById('formTasaBcvGlobal');
        if (formTasaBcv) {
            formTasaBcv.addEventListener('submit', async (e) => {
                e.preventDefault();
                const tasa = parseFloat(document.getElementById('inputTasaBcvGlobal')?.value || '0') || 0;
                const montoUsd = parseFloat(document.getElementById('inputMontoUsdCestaticket')?.value || '40') || 40;
                const recalcular = document.getElementById('chkRecalcularCestaticketBcv')?.checked ? 1 : 0;

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=empresa&a=actualizarTasaBcv`, {
                        method: 'POST',
                        body: {
                            tasa_bcv_usd: tasa,
                            monto_usd_cestaticket: montoUsd,
                            recalcular_cestaticket: recalcular
                        }
                    });
                    cerrarModalTasaBcv();
                    await alertaSwal({
                        icon: 'success',
                        title: 'Tasa BCV Actualizada',
                        text: res.message,
                        timer: 2200,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Error', text: err.message });
                }
            });
        }

        // Sincronización automática en segundo plano del Dólar BCV al cargar cualquier pantalla
        autoSincronizarTasaBcvSilencioso();
    });

    /**
     * Consulta automáticamente en segundo plano la Tasa Oficial del Dólar BCV al abrir el sistema,
     * actualiza el indicador superior en vivo y guarda la tasa en la base de datos si cambió.
     */
    async function autoSincronizarTasaBcvSilencioso() {
        const lblHeader = document.getElementById('lblTasaBcvHeader');
        const inpModal = document.getElementById('inputTasaBcvGlobal');
        if (!lblHeader || !inpModal) return;

        try {
            let tasaOficial = null;

            // 1. Consulta silenciosa a API pública BCV desde el navegador
            try {
                const resp = await fetch('https://ve.dolarapi.com/v1/dolares/oficial', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                if (resp.ok) {
                    const json = await resp.json();
                    if (json && Number(json.promedio) > 0) {
                        tasaOficial = Number(json.promedio);
                    }
                }
            } catch (_) {
                // Fallback silencioso
            }

            if (!tasaOficial) {
                try {
                    const resPhp = await requestJson(`${BASE_URL}/index.php?c=empresa&a=consultarBcvOnline`, {
                        method: 'POST',
                        body: {}
                    });
                    if (resPhp && Number(resPhp.tasa_bcv_usd) > 0) {
                        tasaOficial = Number(resPhp.tasa_bcv_usd);
                    }
                } catch (_) {
                    return;
                }
            }

            if (!tasaOficial || tasaOficial <= 0) return;

            const tasaActualGuardada = parseFloat(inpModal.value || '0') || 0;

            // Actualizar visualmente la barra superior y el modal de inmediato
            lblHeader.textContent = formatVes(tasaOficial, false);
            inpModal.value = tasaOficial.toFixed(4);
            previsualizarCestaticketBcv();

            // Si la tasa en la base de datos es distinta a la tasa oficial vigente del BCV,
            // persistirla automáticamente en segundo plano (junto con el Cestaticket $40 x BCV)
            if (Math.abs(tasaActualGuardada - tasaOficial) > 0.005) {
                await requestJson(`${BASE_URL}/index.php?c=empresa&a=actualizarTasaBcv`, {
                    method: 'POST',
                    body: {
                        tasa_bcv_usd: tasaOficial,
                        monto_usd_cestaticket: 40,
                        recalcular_cestaticket: 1
                    }
                });

                // Si el usuario está en la pantalla de Cestaticket, actualizar también la tabla en vivo
                const inpCest = document.getElementById('cest_monto_mensual');
                if (inpCest) {
                    inpCest.value = (Math.round(tasaOficial * 40 * 100) / 100).toFixed(2);
                    recalcularTodasLasFilasCestaticket();
                }

                // Si está en el modal de parámetros laborales, actualizar el input de Cestaticket
                const inpCfgCest = document.getElementById('cfg_monto_cestaticket');
                if (inpCfgCest) {
                    inpCfgCest.value = (Math.round(tasaOficial * 40 * 100) / 100).toFixed(2);
                }
            }
        } catch (_) {
            // Fallo de red silencioso: mantiene la última tasa guardada en base de datos
        }
    }

    function abrirModalTasaBcv() {
        const modal = document.getElementById('modalTasaBcvGlobal');
        if (!modal) return;
        previsualizarCestaticketBcv();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalTasaBcv() {
        const modal = document.getElementById('modalTasaBcvGlobal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function previsualizarCestaticketBcv() {
        const tasa = parseFloat(document.getElementById('inputTasaBcvGlobal')?.value || '0') || 0;
        const montoUsd = parseFloat(document.getElementById('inputMontoUsdCestaticket')?.value || '40') || 40;
        const resultante = Math.round(tasa * montoUsd * 100) / 100;
        const lbl = document.getElementById('lblPreviewCestaticketBcv');
        if (lbl) lbl.textContent = formatVes(resultante);
    }

    async function consultarTasaBcvEnLinea() {
        const btn = document.getElementById('btnConsultarBcvOnline');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Consultando...';
        }
        try {
            let tasaObtenida = null;

            // 1. Intentar directamente desde el navegador contra API pública BCV (CORS habilitado)
            try {
                const respDirecta = await fetch('https://ve.dolarapi.com/v1/dolares/oficial', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                if (respDirecta.ok) {
                    const dataDirecta = await respDirecta.json();
                    if (dataDirecta && Number(dataDirecta.promedio) > 0) {
                        tasaObtenida = Number(dataDirecta.promedio);
                    }
                }
            } catch (_) {
                // Continuar al fallback del servidor PHP
            }

            // 2. Fallback por el backend PHP si la consulta directa no respondió
            if (!tasaObtenida) {
                const res = await requestJson(`${BASE_URL}/index.php?c=empresa&a=consultarBcvOnline`, {
                    method: 'POST',
                    body: {}
                });
                if (res && Number(res.tasa_bcv_usd) > 0) {
                    tasaObtenida = Number(res.tasa_bcv_usd);
                }
            }

            if (!tasaObtenida) {
                throw new Error('No se pudo obtener la tasa en línea. Puede ingresarla manualmente.');
            }

            const inp = document.getElementById('inputTasaBcvGlobal');
            if (inp) {
                inp.value = tasaObtenida.toFixed(4);
                previsualizarCestaticketBcv();
            }

            alertaSwal({
                icon: 'success',
                title: 'Tasa BCV Sincronizada',
                text: `Tasa Oficial BCV obtenida en línea: ${formatVes(tasaObtenida)} / USD. Haga clic en "Aplicar Tasa BCV" para guardarla.`,
                timer: 2600,
                showConfirmButton: false
            });
        } catch (err) {
            alertaSwal({
                icon: 'info',
                title: 'Ingreso Manual Disponible',
                text: err.message
            });
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Consultar BCV Hoy';
            }
        }
    }

    function aplicarCestaticketPorTasaBcv(tasaBcv) {
        const tasaLive = parseFloat(document.getElementById('inputTasaBcvGlobal')?.value || '0') || 0;
        const tasa = tasaLive > 0 ? tasaLive : (parseFloat(tasaBcv || '45') || 45);
        const inp = document.getElementById('cest_monto_mensual');
        if (inp) {
            inp.value = (Math.round(tasa * 40 * 100) / 100).toFixed(2);
            recalcularTodasLasFilasCestaticket();
        }
    }

    function calcularSalarioBsDesdeUsd(montoUsd, tasaBcv) {
        const usd = parseFloat(montoUsd || '0') || 0;
        const tasaLive = parseFloat(document.getElementById('inputTasaBcvGlobal')?.value || '0') || 0;
        const tasa = tasaLive > 0 ? tasaLive : (parseFloat(tasaBcv || '45') || 45);
        const inpBs = document.getElementById('trab_salario_mensual');
        if (inpBs && usd > 0) {
            inpBs.value = (Math.round(usd * tasa * 100) / 100).toFixed(2);
        }
    }

    // =========================================================================
    // FASE 4: FUNCIONES DEL MÓDULO LABORAL LOTTT (TRABAJADORES, NÓMINA Y CESTATICKET)
    // =========================================================================

    async function abrirModalTrabajador(id = 0) {
        const modal = document.getElementById('modalTrabajador');
        const form = document.getElementById('formTrabajador');
        if (!modal || !form) return;

        form.reset();
        document.getElementById('trab_id').value = String(id);
        document.getElementById('modalTrabajadorTitulo').textContent = id > 0 ? 'Editar Ficha de Trabajador' : 'Registrar Nuevo Trabajador';

        if (id > 0) {
            try {
                const res = await requestJson(`${BASE_URL}/index.php?c=nomina&a=obtenerTrabajador`, {
                    method: 'POST',
                    body: { id: Number(id) }
                });
                const t = res.trabajador;
                document.getElementById('trab_cedula').value = t.cedula || '';
                document.getElementById('trab_nombres').value = t.nombres || '';
                document.getElementById('trab_apellidos').value = t.apellidos || '';
                document.getElementById('trab_telefono').value = t.telefono || '';
                document.getElementById('trab_email').value = t.email || '';
                document.getElementById('trab_fecha_nacimiento').value = t.fecha_nacimiento || '';
                document.getElementById('trab_cargo').value = t.cargo || '';
                document.getElementById('trab_departamento').value = t.departamento || 'Administración';
                document.getElementById('trab_tipo_trabajador').value = t.tipo_trabajador || 'EMPLEADO';
                document.getElementById('trab_tipo_nomina').value = t.tipo_nomina || 'QUINCENAL';
                document.getElementById('trab_salario_mensual').value = t.salario_mensual || '';
                document.getElementById('trab_fecha_ingreso').value = t.fecha_ingreso || '';
                document.getElementById('trab_estatus_laboral').value = t.estatus_laboral || 'ACTIVO';
                document.getElementById('trab_banco').value = t.banco || '';
                document.getElementById('trab_numero_cuenta').value = t.numero_cuenta || '';
                document.getElementById('trab_porcentaje_islr').value = t.porcentaje_islr || '0.00';
                document.getElementById('trab_aplica_sso').checked = Number(t.aplica_sso) === 1;
                document.getElementById('trab_aplica_rpe').checked = Number(t.aplica_rpe) === 1;
                document.getElementById('trab_aplica_faov').checked = Number(t.aplica_faov) === 1;
                document.getElementById('trab_aplica_cestaticket').checked = Number(t.aplica_cestaticket) === 1;
            } catch (err) {
                alertaSwal({ icon: 'error', title: 'Error', text: err.message });
                return;
            }
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalTrabajador() {
        const modal = document.getElementById('modalTrabajador');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function abrirModalConfigLaboral() {
        const modal = document.getElementById('modalConfigLaboral');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalConfigLaboral() {
        const modal = document.getElementById('modalConfigLaboral');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function obtenerConfigNominaDOM() {
        const panel = document.getElementById('panelGeneradorNomina');
        if (!panel) return {};
        try {
            return JSON.parse(panel.getAttribute('data-config') || '{}');
        } catch (e) {
            return {};
        }
    }

    function recalcularPorFrecuencia() {
        const frec = document.getElementById('nom_frecuencia')?.value || 'PRIMERA_QUINCENA';
        const periodo = document.getElementById('nom_periodo')?.value || '';
        const inpDesde = document.getElementById('nom_fecha_desde');
        const inpHasta = document.getElementById('nom_fecha_hasta');
        const inpDesc = document.getElementById('nom_descripcion');

        if (periodo) {
            if (frec === 'PRIMERA_QUINCENA') {
                if (inpDesde) inpDesde.value = `${periodo}-01`;
                if (inpHasta) inpHasta.value = `${periodo}-15`;
                if (inpDesc) inpDesc.value = `Nómina 1ra Quincena ${periodo}`;
            } else if (frec === 'SEGUNDA_QUINCENA') {
                if (inpDesde) inpDesde.value = `${periodo}-16`;
                if (inpHasta) inpHasta.value = `${periodo}-30`;
                if (inpDesc) inpDesc.value = `Nómina 2da Quincena ${periodo}`;
            } else {
                if (inpDesde) inpDesde.value = `${periodo}-01`;
                if (inpHasta) inpHasta.value = `${periodo}-30`;
                if (inpDesc) inpDesc.value = `Nómina Mensual ${periodo}`;
            }
        }

        const habilesDef = frec === 'MENSUAL' ? 22 : 11;
        const descansoDef = frec === 'MENSUAL' ? 8 : 4;

        document.querySelectorAll('#tablaPreNomina tbody tr.fila-prenomina').forEach((tr) => {
            const inpH = tr.querySelector('.inp-dias-habiles');
            const inpD = tr.querySelector('.inp-dias-descanso');
            if (inpH) inpH.value = String(habilesDef);
            if (inpD) inpD.value = String(descansoDef);
            recalcularFilaNomina(tr);
        });
    }

    function recalcularTodasLasFilasNomina() {
        document.querySelectorAll('#tablaPreNomina tbody tr.fila-prenomina').forEach((tr) => {
            recalcularFilaNomina(tr);
        });
    }

    function recalcularFilaNomina(tr) {
        if (!tr) return;
        const cfg = obtenerConfigNominaDOM();
        const frec = document.getElementById('nom_frecuencia')?.value || 'PRIMERA_QUINCENA';
        const lunesMes = parseInt(document.getElementById('nom_lunes_mes')?.value || '4', 10) || 4;
        const factorPeriodoSso = frec === 'MENSUAL' ? 1.0 : 0.5;

        const salarioMensual = parseFloat(tr.getAttribute('data-salario-mensual') || '0') || 0;
        const sueldoDiario = parseFloat(tr.getAttribute('data-sueldo-diario') || '0') || 0;
        const aplicaSso = Number(tr.getAttribute('data-aplica-sso') || 1) === 1;
        const aplicaRpe = Number(tr.getAttribute('data-aplica-rpe') || 1) === 1;
        const aplicaFaov = Number(tr.getAttribute('data-aplica-faov') || 1) === 1;
        const porcIslr = parseFloat(tr.getAttribute('data-porc-islr') || '0') || 0;

        const diasHabiles = parseFloat(tr.querySelector('.inp-dias-habiles')?.value || '0') || 0;
        const diasDescanso = parseFloat(tr.querySelector('.inp-dias-descanso')?.value || '0') || 0;
        const diasFeriados = parseFloat(tr.querySelector('.inp-dias-feriados')?.value || '0') || 0;

        const montoHabiles = Math.round(sueldoDiario * diasHabiles * 100) / 100;
        const montoDescanso = Math.round(sueldoDiario * diasDescanso * 100) / 100;
        const montoFeriados = Math.round(sueldoDiario * diasFeriados * 1.5 * 100) / 100;
        const sueldoBasico = Math.round((montoHabiles + montoDescanso + montoFeriados) * 100) / 100;

        const horasExtras = parseFloat(tr.querySelector('.inp-horas-extras')?.value || '0') || 0;
        const bonificaciones = parseFloat(tr.querySelector('.inp-bonificaciones')?.value || '0') || 0;
        const totalAsignaciones = Math.round((sueldoBasico + horasExtras + bonificaciones) * 100) / 100;

        const topeMensual = (parseFloat(cfg.salario_minimo_referencia || 15000) || 15000) * (parseInt(cfg.tope_salarios_minimos_sso || 5, 10) || 5);
        const baseSso = topeMensual > 0 ? Math.min(salarioMensual, topeMensual) : salarioMensual;
        const baseSemanal = (baseSso * 12) / 52;

        const porcSso = parseFloat(cfg.porcentaje_sso_empleado || 4.0) || 4.0;
        const porcRpe = parseFloat(cfg.porcentaje_rpe_empleado || 0.5) || 0.5;
        const porcFaov = parseFloat(cfg.porcentaje_faov_empleado || 1.0) || 1.0;

        const retSso = aplicaSso ? Math.round(baseSemanal * lunesMes * (porcSso / 100) * factorPeriodoSso * 100) / 100 : 0;
        const retRpe = aplicaRpe ? Math.round(baseSemanal * lunesMes * (porcRpe / 100) * factorPeriodoSso * 100) / 100 : 0;
        const retFaov = aplicaFaov ? Math.round(totalAsignaciones * (porcFaov / 100) * 100) / 100 : 0;
        const retIslr = porcIslr > 0 ? Math.round(totalAsignaciones * (porcIslr / 100) * 100) / 100 : 0;

        const inpSso = tr.querySelector('.inp-ret-sso');
        const inpRpe = tr.querySelector('.inp-ret-rpe');
        const inpFaov = tr.querySelector('.inp-ret-faov');
        const inpOtras = tr.querySelector('.inp-otras-ded');

        if (inpSso) inpSso.value = retSso.toFixed(2);
        if (inpRpe) inpRpe.value = retRpe.toFixed(2);
        if (inpFaov) inpFaov.value = retFaov.toFixed(2);
        if (inpOtras && (parseFloat(inpOtras.value) || 0) === 0 && retIslr > 0) {
            inpOtras.value = retIslr.toFixed(2);
        }

        const lblBasico = tr.querySelector('.lbl-sueldo-basico');
        if (lblBasico) lblBasico.textContent = formatVes(sueldoBasico, false);

        recalcularNetoManualFila(tr);
    }

    function recalcularNetoManualFila(tr) {
        if (!tr) return;
        const sueldoDiario = parseFloat(tr.getAttribute('data-sueldo-diario') || '0') || 0;
        const diasHabiles = parseFloat(tr.querySelector('.inp-dias-habiles')?.value || '0') || 0;
        const diasDescanso = parseFloat(tr.querySelector('.inp-dias-descanso')?.value || '0') || 0;
        const diasFeriados = parseFloat(tr.querySelector('.inp-dias-feriados')?.value || '0') || 0;

        const sueldoBasico = Math.round((sueldoDiario * diasHabiles + sueldoDiario * diasDescanso + sueldoDiario * diasFeriados * 1.5) * 100) / 100;
        const horasExtras = parseFloat(tr.querySelector('.inp-horas-extras')?.value || '0') || 0;
        const bonificaciones = parseFloat(tr.querySelector('.inp-bonificaciones')?.value || '0') || 0;
        const totalAsignaciones = Math.round((sueldoBasico + horasExtras + bonificaciones) * 100) / 100;

        const retSso = parseFloat(tr.querySelector('.inp-ret-sso')?.value || '0') || 0;
        const retRpe = parseFloat(tr.querySelector('.inp-ret-rpe')?.value || '0') || 0;
        const retFaov = parseFloat(tr.querySelector('.inp-ret-faov')?.value || '0') || 0;
        const otrasDed = parseFloat(tr.querySelector('.inp-otras-ded')?.value || '0') || 0;
        const totalDeducciones = Math.round((retSso + retRpe + retFaov + otrasDed) * 100) / 100;

        const neto = Math.round((totalAsignaciones - totalDeducciones) * 100) / 100;
        const lblNeto = tr.querySelector('.lbl-neto-fila');
        if (lblNeto) lblNeto.textContent = formatVes(neto);

        recalcularTotalesGeneralesNomina();
    }

    function recalcularTotalesGeneralesNomina() {
        let sumaAsig = 0;
        let sumaDed = 0;
        let sumaNeto = 0;

        document.querySelectorAll('#tablaPreNomina tbody tr.fila-prenomina').forEach((tr) => {
            const sueldoDiario = parseFloat(tr.getAttribute('data-sueldo-diario') || '0') || 0;
            const diasHabiles = parseFloat(tr.querySelector('.inp-dias-habiles')?.value || '0') || 0;
            const diasDescanso = parseFloat(tr.querySelector('.inp-dias-descanso')?.value || '0') || 0;
            const diasFeriados = parseFloat(tr.querySelector('.inp-dias-feriados')?.value || '0') || 0;
            const sueldoBasico = Math.round((sueldoDiario * diasHabiles + sueldoDiario * diasDescanso + sueldoDiario * diasFeriados * 1.5) * 100) / 100;
            const horasExtras = parseFloat(tr.querySelector('.inp-horas-extras')?.value || '0') || 0;
            const bonificaciones = parseFloat(tr.querySelector('.inp-bonificaciones')?.value || '0') || 0;
            const asig = Math.round((sueldoBasico + horasExtras + bonificaciones) * 100) / 100;

            const retSso = parseFloat(tr.querySelector('.inp-ret-sso')?.value || '0') || 0;
            const retRpe = parseFloat(tr.querySelector('.inp-ret-rpe')?.value || '0') || 0;
            const retFaov = parseFloat(tr.querySelector('.inp-ret-faov')?.value || '0') || 0;
            const otrasDed = parseFloat(tr.querySelector('.inp-otras-ded')?.value || '0') || 0;
            const ded = Math.round((retSso + retRpe + retFaov + otrasDed) * 100) / 100;

            sumaAsig += asig;
            sumaDed += ded;
            sumaNeto += (asig - ded);
        });

        const elAsig = document.getElementById('lblTotalAsignacionesLote');
        const elDed = document.getElementById('lblTotalDeduccionesLote');
        const elNeto = document.getElementById('lblTotalNetoLote');

        if (elAsig) elAsig.textContent = formatVes(sumaAsig);
        if (elDed) elDed.textContent = formatVes(sumaDed);
        if (elNeto) elNeto.textContent = formatVes(sumaNeto);
    }

    async function procesarYGuardarNomina() {
        const filas = [];
        document.querySelectorAll('#tablaPreNomina tbody tr.fila-prenomina').forEach((tr) => {
            filas.push({
                trabajador_id: Number(tr.getAttribute('data-trabajador-id') || 0),
                dias_habiles: parseFloat(tr.querySelector('.inp-dias-habiles')?.value || '0') || 0,
                dias_descanso: parseFloat(tr.querySelector('.inp-dias-descanso')?.value || '0') || 0,
                dias_feriados: parseFloat(tr.querySelector('.inp-dias-feriados')?.value || '0') || 0,
                horas_extras: parseFloat(tr.querySelector('.inp-horas-extras')?.value || '0') || 0,
                bonificaciones: parseFloat(tr.querySelector('.inp-bonificaciones')?.value || '0') || 0,
                retencion_sso: parseFloat(tr.querySelector('.inp-ret-sso')?.value || '0') || 0,
                retencion_rpe: parseFloat(tr.querySelector('.inp-ret-rpe')?.value || '0') || 0,
                retencion_faov: parseFloat(tr.querySelector('.inp-ret-faov')?.value || '0') || 0,
                otras_deducciones: parseFloat(tr.querySelector('.inp-otras-ded')?.value || '0') || 0
            });
        });

        if (filas.length === 0) {
            alertaSwal({ icon: 'warning', title: 'Sin Trabajadores', text: 'No hay trabajadores activos para procesar.' });
            return;
        }

        const payload = {
            periodo: document.getElementById('nom_periodo')?.value || '',
            tipo_frecuencia: document.getElementById('nom_frecuencia')?.value || 'PRIMERA_QUINCENA',
            fecha_desde: document.getElementById('nom_fecha_desde')?.value || '',
            fecha_hasta: document.getElementById('nom_fecha_hasta')?.value || '',
            lunes_del_mes: parseInt(document.getElementById('nom_lunes_mes')?.value || '4', 10) || 4,
            descripcion: document.getElementById('nom_descripcion')?.value || '',
            generar_asiento: document.getElementById('nom_generar_asiento')?.checked ? 1 : 0,
            filas
        };

        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=nomina&a=guardarNomina`, {
                method: 'POST',
                body: payload
            });

            await alertaSwal({
                icon: 'success',
                title: 'Nómina Procesada',
                text: res.message,
                timer: 2200,
                showConfirmButton: false
            });

            window.location.href = `${BASE_URL}/index.php?c=nomina&a=index&lote_id=${res.resultado.nomina_id}`;
        } catch (err) {
            alertaSwal({ icon: 'error', title: 'Error al procesar nómina', text: err.message });
        }
    }

    async function anularLoteNomina(nominaId, codigo) {
        const confirmacion = await alertaSwal({
            icon: 'warning',
            title: `¿Anular lote ${codigo}?`,
            text: 'Se anulará el lote de nómina y el asiento contable vinculado en el Libro Diario.',
            showCancelButton: true,
            confirmButtonText: 'Sí, anular lote',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=nomina&a=anularNomina`, {
                method: 'POST',
                body: { nomina_id: nominaId }
            });
            await alertaSwal({
                icon: 'success',
                title: 'Lote Anulado',
                text: res.message,
                timer: 1800,
                showConfirmButton: false
            });
            window.location.reload();
        } catch (err) {
            alertaSwal({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    function recalcularTodasLasFilasCestaticket() {
        const montoBase = parseFloat(document.getElementById('cest_monto_mensual')?.value || '1800') || 1800;
        const valorDiario = Math.round((montoBase / 30) * 100) / 100;
        const lblDiario = document.getElementById('lblValorDiarioCest');
        if (lblDiario) lblDiario.textContent = formatVes(valorDiario);

        document.querySelectorAll('#tablaPreCestaticket tbody tr.fila-precestaticket').forEach((tr) => {
            recalcularFilaCestaticket(tr);
        });
    }

    function recalcularFilaCestaticket(tr) {
        if (!tr) return;
        const montoBase = parseFloat(document.getElementById('cest_monto_mensual')?.value || '1800') || 1800;
        const valorDiario = Math.round((montoBase / 30) * 100) / 100;

        const habiles = parseFloat(tr.querySelector('.inp-cest-habiles')?.value || '0') || 0;
        const descanso = parseFloat(tr.querySelector('.inp-cest-descanso')?.value || '0') || 0;
        const feriados = parseFloat(tr.querySelector('.inp-cest-feriados')?.value || '0') || 0;
        const inasistencias = parseFloat(tr.querySelector('.inp-cest-inasistencias')?.value || '0') || 0;

        const brutos = Math.min(30, habiles + descanso + feriados);
        const efectivos = Math.max(0, brutos - inasistencias);
        const descuento = Math.round(inasistencias * valorDiario * 100) / 100;
        const total = Math.round(efectivos * valorDiario * 100) / 100;

        const elEfectivos = tr.querySelector('.lbl-cest-efectivos');
        const elDiario = tr.querySelector('.lbl-cest-diario');
        const elDescuento = tr.querySelector('.lbl-cest-descuento');
        const elTotal = tr.querySelector('.lbl-cest-total');

        if (elEfectivos) elEfectivos.textContent = String(efectivos);
        if (elDiario) elDiario.textContent = formatVes(valorDiario, false);
        if (elDescuento) elDescuento.textContent = formatVes(descuento, false);
        if (elTotal) elTotal.textContent = formatVes(total);

        let sumaTotal = 0;
        document.querySelectorAll('#tablaPreCestaticket tbody tr.fila-precestaticket').forEach((row) => {
            const h = parseFloat(row.querySelector('.inp-cest-habiles')?.value || '0') || 0;
            const d = parseFloat(row.querySelector('.inp-cest-descanso')?.value || '0') || 0;
            const f = parseFloat(row.querySelector('.inp-cest-feriados')?.value || '0') || 0;
            const ina = parseFloat(row.querySelector('.inp-cest-inasistencias')?.value || '0') || 0;
            const ef = Math.max(0, Math.min(30, h + d + f) - ina);
            sumaTotal += Math.round(ef * valorDiario * 100) / 100;
        });

        const lblGen = document.getElementById('lblTotalGeneralCestaticket');
        if (lblGen) lblGen.textContent = formatVes(sumaTotal);
    }

    async function procesarYGuardarCestaticket() {
        const filas = [];
        document.querySelectorAll('#tablaPreCestaticket tbody tr.fila-precestaticket').forEach((tr) => {
            filas.push({
                trabajador_id: Number(tr.getAttribute('data-trabajador-id') || 0),
                dias_habiles: parseFloat(tr.querySelector('.inp-cest-habiles')?.value || '0') || 0,
                dias_descanso: parseFloat(tr.querySelector('.inp-cest-descanso')?.value || '0') || 0,
                dias_feriados: parseFloat(tr.querySelector('.inp-cest-feriados')?.value || '0') || 0,
                dias_inasistencia: parseFloat(tr.querySelector('.inp-cest-inasistencias')?.value || '0') || 0
            });
        });

        if (filas.length === 0) {
            alertaSwal({ icon: 'warning', title: 'Sin Trabajadores', text: 'No hay trabajadores con Cestaticket activo.' });
            return;
        }

        const payload = {
            periodo: document.getElementById('cest_periodo')?.value || '',
            fecha_emision: document.getElementById('cest_fecha_emision')?.value || '',
            monto_mensual_base: parseFloat(document.getElementById('cest_monto_mensual')?.value || '1800') || 1800,
            observaciones: document.getElementById('cest_observaciones')?.value || '',
            generar_asiento: document.getElementById('cest_generar_asiento')?.checked ? 1 : 0,
            filas
        };

        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=nomina&a=guardarCestaticket`, {
                method: 'POST',
                body: payload
            });
            await alertaSwal({
                icon: 'success',
                title: 'Bono de Alimentación Registrado',
                text: res.message,
                timer: 2000,
                showConfirmButton: false
            });
            window.location.href = `${BASE_URL}/index.php?c=nomina&a=cestaticket&lote_id=${res.resultado.lote_id}`;
        } catch (err) {
            alertaSwal({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    // Exponer APIs públicas
    window.JurisApp = {
        cambiarEmpresaActiva,
        abrirModalNuevaEmpresa,
        abrirModalEditarEmpresa,
        cerrarModalEmpresa,
        cambiarEstadoEmpresa,
        abrirModalPlanCuentas,
        cerrarModalPlanCuentas,
        abrirModalTasaBcv,
        cerrarModalTasaBcv,
        previsualizarCestaticketBcv,
        consultarTasaBcvEnLinea,
        notificarFase
    };

    window.JurisContabilidad = {
        filtrarPlanPorGrupo,
        abrirModalNuevaCuenta,
        abrirModalSubcuenta,
        abrirModalEditarCuenta,
        cerrarModalCuenta,
        eliminarCuenta,
        agregarFilaAsiento,
        cuadrarUltimaFila,
        reiniciarFormularioAsiento,
        togglePanelNuevoAsiento,
        verDetalleAsientoModal,
        cerrarModalDetalleAsiento,
        anularAsiento,
        cambiarTabEstados
    };

    window.JurisTributario = {
        cambiarTabLibros,
        cambiarTabRetenciones,
        abrirModalCompra,
        cerrarModalCompra,
        recalcularModalCompra,
        abrirModalVenta,
        cerrarModalVenta,
        recalcularModalVenta,
        abrirModalConfigFiscal,
        cerrarModalConfigFiscal,
        recalcularSeleccionRetIva,
        seleccionarConceptoIslr,
        recalcularIslr
    };

    // =========================================================================
    // MÓDULO DE GESTIÓN DE USUARIOS, ROLES Y PERMISOS (SOLO ADMIN, TODO POST)
    // =========================================================================

    document.addEventListener('DOMContentLoaded', () => {
        const formUsuario = document.getElementById('formUsuarioCrud');
        if (formUsuario) {
            formUsuario.addEventListener('submit', async (e) => {
                e.preventDefault();
                const payload = {
                    id: Number(document.getElementById('usr_id')?.value || 0),
                    nombre: document.getElementById('usr_nombre')?.value || '',
                    email: document.getElementById('usr_email')?.value || '',
                    rol: document.getElementById('usr_rol')?.value || 'contador',
                    password: document.getElementById('usr_password')?.value || '',
                    activo: Number(document.getElementById('usr_activo')?.value || 1),
                    permisos: {
                        empresas: document.getElementById('perm_empresas')?.checked ? 1 : 0,
                        contabilidad: document.getElementById('perm_contabilidad')?.checked ? 1 : 0,
                        tributario: document.getElementById('perm_tributario')?.checked ? 1 : 0,
                        nomina: document.getElementById('perm_nomina')?.checked ? 1 : 0,
                        anular: document.getElementById('perm_anular')?.checked ? 1 : 0
                    }
                };

                try {
                    const res = await requestJson(`${BASE_URL}/index.php?c=usuarios&a=guardar`, {
                        method: 'POST',
                        body: payload
                    });
                    cerrarModalUsuario();
                    await alertaSwal({
                        icon: 'success',
                        title: 'Usuario Guardado',
                        text: res.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } catch (err) {
                    alertaSwal({ icon: 'error', title: 'Error de Validación', text: err.message });
                }
            });
        }
    });

    async function abrirModalUsuario(id = 0) {
        const modal = document.getElementById('modalUsuarioForm');
        const form = document.getElementById('formUsuarioCrud');
        if (!modal || !form) return;

        form.reset();
        document.getElementById('usr_id').value = String(id);

        const lblReq = document.getElementById('lblPassRequerida');
        const lblOpc = document.getElementById('lblPassOpcional');

        if (id > 0) {
            document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuario, Correo, Clave y Permisos';
            if (lblReq) lblReq.classList.add('hidden');
            if (lblOpc) lblOpc.classList.remove('hidden');

            try {
                const res = await requestJson(`${BASE_URL}/index.php?c=usuarios&a=obtener`, {
                    method: 'POST',
                    body: { id: Number(id) }
                });
                const u = res.usuario;
                document.getElementById('usr_nombre').value = u.nombre || '';
                document.getElementById('usr_email').value = u.email || '';
                document.getElementById('usr_rol').value = u.rol || 'contador';
                document.getElementById('usr_activo').value = String(u.activo ?? 1);
                document.getElementById('usr_password').value = '';

                const p = u.permisos || {};
                document.getElementById('perm_empresas').checked = Boolean(p.empresas);
                document.getElementById('perm_contabilidad').checked = Boolean(p.contabilidad);
                document.getElementById('perm_tributario').checked = Boolean(p.tributario);
                document.getElementById('perm_nomina').checked = Boolean(p.nomina);
                document.getElementById('perm_anular').checked = Boolean(p.anular);
            } catch (err) {
                alertaSwal({ icon: 'error', title: 'Error', text: err.message });
                return;
            }
        } else {
            document.getElementById('modalUsuarioTitulo').textContent = 'Registrar Nuevo Usuario';
            if (lblReq) lblReq.classList.remove('hidden');
            if (lblOpc) lblOpc.classList.add('hidden');
            document.getElementById('usr_rol').value = 'contador';
            aplicarPermisosPorRol();
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalUsuario() {
        const modal = document.getElementById('modalUsuarioForm');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function aplicarPermisosPorRol() {
        const rol = document.getElementById('usr_rol')?.value || 'contador';
        if (rol === 'admin') {
            document.getElementById('perm_empresas').checked = true;
            document.getElementById('perm_contabilidad').checked = true;
            document.getElementById('perm_tributario').checked = true;
            document.getElementById('perm_nomina').checked = true;
            document.getElementById('perm_anular').checked = true;
        } else if (rol === 'contador') {
            document.getElementById('perm_empresas').checked = true;
            document.getElementById('perm_contabilidad').checked = true;
            document.getElementById('perm_tributario').checked = true;
            document.getElementById('perm_nomina').checked = true;
            document.getElementById('perm_anular').checked = true;
        } else {
            document.getElementById('perm_empresas').checked = false;
            document.getElementById('perm_contabilidad').checked = true;
            document.getElementById('perm_tributario').checked = true;
            document.getElementById('perm_nomina').checked = false;
            document.getElementById('perm_anular').checked = false;
        }
    }

    async function cambiarEstadoUsuario(id, nuevoEstado, nombre) {
        const accion = nuevoEstado === 1 ? 'activar' : 'inactivar';
        const confirmacion = await alertaSwal({
            icon: 'warning',
            title: `¿Desea ${accion} a ${nombre}?`,
            showCancelButton: true,
            confirmButtonText: `Sí, ${accion}`,
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const res = await requestJson(`${BASE_URL}/index.php?c=usuarios&a=cambiarEstado`, {
                method: 'POST',
                body: { id: Number(id), activo: Number(nuevoEstado) }
            });
            await alertaSwal({
                icon: 'success',
                title: 'Estado Actualizado',
                text: res.message,
                timer: 1600,
                showConfirmButton: false
            });
            window.location.reload();
        } catch (err) {
            alertaSwal({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    window.JurisNomina = {
        abrirModalTrabajador,
        cerrarModalTrabajador,
        abrirModalConfigLaboral,
        cerrarModalConfigLaboral,
        recalcularPorFrecuencia,
        recalcularTodasLasFilasNomina,
        recalcularFilaNomina,
        recalcularNetoManualFila,
        procesarYGuardarNomina,
        anularLoteNomina,
        recalcularTodasLasFilasCestaticket,
        recalcularFilaCestaticket,
        procesarYGuardarCestaticket,
        aplicarCestaticketPorTasaBcv,
        calcularSalarioBsDesdeUsd
    };

    window.JurisUsuarios = {
        abrirModalUsuario,
        cerrarModalUsuario,
        aplicarPermisosPorRol,
        cambiarEstadoUsuario
    };
})();
