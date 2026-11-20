<?php
declare(strict_types=1);
?>
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Cabecera del Manual de Usuario -->
    <div class="bg-white border border-emerald-200 rounded-2xl p-6 text-slate-900 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-800 text-xs font-mono font-bold mb-2">
                    DOCUMENTACIÓN OFICIAL INTERNA • VERSIÓN <?= e(APP_VERSION) ?>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Manual de Usuario y Guía de Reportes — Juris Contable
                </h1>
                <p class="text-sm text-slate-700 mt-1 max-w-3xl">
                    Guía paso a paso para operar el sistema contable, tributario (SENIAT) y laboral (LOTTT), emitir todos los comprobantes oficiales e imprimir los libros legales en formato de Registro Mercantil.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 no-print shrink-0">
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir Manual en PDF
                </button>
            </div>
        </div>

        <!-- Buscador Rápido dentro del Manual -->
        <div class="mt-5 pt-5 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print">
            <div class="relative flex-1 max-w-xl">
                <input type="text"
                       id="inputBuscarManual"
                       oninput="filtrarSeccionesManual(this.value)"
                       placeholder="Buscar en el manual (ej. retención IVA, libro diario, cestaticket, BCV, folio, asiento)..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl text-xs bg-slate-50 border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <div class="flex flex-wrap gap-1.5 text-xs">
                <a href="#sec-multiempresa" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 transition">1. Multi-Empresa y BCV</a>
                <a href="#sec-contabilidad" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 transition">2. Contabilidad y Libros</a>
                <a href="#sec-tributario" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 transition">3. SENIAT (IVA / ISLR)</a>
                <a href="#sec-nomina" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 transition">4. Nómina LOTTT</a>
                <a href="#sec-reportes" class="px-2.5 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-800 border border-emerald-500/30 font-semibold transition">5. Guía de los 19 Reportes</a>
                <a href="#sec-cierre" class="px-2.5 py-1.5 rounded-lg bg-amber-500/20 text-amber-800 border border-amber-500/30 font-semibold transition">6. Rutina de Cierre Mensual</a>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- SECCIÓN 1: ARQUITECTURA MULTI-EMPRESA, TEMA VISUAL Y DÓLAR BCV        -->
    <!-- ===================================================================== -->
    <section id="sec-multiempresa" class="bloque-manual bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-200">
            <div>
                <span class="text-xs font-mono font-bold uppercase text-emerald-600">Capítulo 1</span>
                <h2 class="text-xl font-bold text-slate-900">
                    Funcionamiento Multi-Empresa (Escritorio Contable) y Dólar BCV Automático
                </h2>
            </div>
            <a href="<?= e(route_url('empresa', 'index')) ?>"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition no-print self-start">
                Ir a Empresas Clientes →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-xs leading-relaxed text-slate-600">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <h3 class="font-bold text-sm text-slate-900 mb-2 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-500/15 text-emerald-600 font-mono flex items-center justify-center font-bold">1.1</span>
                    Selector de Empresa Activa (Barra Superior)
                </h3>
                <p>
                    El sistema es <strong>Multi-Tenant (Multi-Empresa)</strong>: puedes llevar la contabilidad de 1, 10 o 100 compañías distintas sin que sus datos se mezclen jamás.
                </p>
                <ul class="list-disc pl-4 mt-2 space-y-1">
                    <li>En la parte superior izquierda siempre verás el selector <strong>"Empresa Activa"</strong>.</li>
                    <li>Al cambiar de empresa en ese desplegable, todo el sistema (Plan de Cuentas, Libro Diario, Mayor, Libros de IVA, Retenciones y Nómina) cambia instantáneamente a la empresa seleccionada.</li>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <h3 class="font-bold text-sm text-slate-900 mb-2 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-500/15 text-emerald-600 font-mono flex items-center justify-center font-bold">1.2</span>
                    Creación de Nueva Empresa y Clonación de 220 Cuentas
                </h3>
                <p>
                    Cuando registras un nuevo cliente en <strong>Empresas Clientes → Nueva Empresa Cliente</strong>:
                </p>
                <ul class="list-disc pl-4 mt-2 space-y-1">
                    <li>El sistema valida el formato del RIF SENIAT (`J-12345678-9`, `V-...`, `G-...`).</li>
                    <li>Define si es <strong>Contribuyente Especial</strong> (Retención IVA `75%` o `100%`) u <strong>Ordinario</strong> (`0%`).</li>
                    <li><strong>Automáticamente clona las 220 cuentas del Plan de Cuentas Venezolano</strong> para esa nueva empresa en una sola transacción segura.</li>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <h3 class="font-bold text-sm text-slate-900 mb-2 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-500/15 text-emerald-600 font-mono flex items-center justify-center font-bold">1.3</span>
                    Tasa Oficial Dólar BCV Automática
                </h3>
                <p>
                    Al abrir cualquier pantalla del sistema, el indicador superior <strong>`BCV: Bs. XX,XX / $`</strong> consulta automáticamente en segundo plano la tasa oficial vigente del Banco Central de Venezuela:
                </p>
                <ul class="list-disc pl-4 mt-2 space-y-1">
                    <li>Actualiza automáticamente el monto mensual del <strong>Cestaticket (`$40 USD × Tasa BCV`)</strong>.</li>
                    <li>Permite convertir sueldos pactados en dólares a Bolívares al registrar trabajadores.</li>
                    <li>Si trabajas sin internet, puedes hacer clic sobre el botón `BCV` para colocar una tasa manual.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- SECCIÓN 2: CONTABILIDAD GENERAL Y LIBROS LEGALES                      -->
    <!-- ===================================================================== -->
    <section id="sec-contabilidad" class="bloque-manual bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-200">
            <div>
                <span class="text-xs font-mono font-bold uppercase text-emerald-600">Capítulo 2</span>
                <h2 class="text-xl font-bold text-slate-900">
                    Contabilidad General, Partida Doble y Libros de Registro Mercantil
                </h2>
            </div>
            <div class="flex flex-wrap gap-2 no-print">
                <a href="<?= e(route_url('plan_cuentas', 'index')) ?>" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Plan de Cuentas</a>
                <a href="<?= e(route_url('contabilidad', 'diario')) ?>" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-600 text-white">Libro Diario</a>
                <a href="<?= e(route_url('contabilidad', 'mayor')) ?>" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Libro Mayor</a>
                <a href="<?= e(route_url('contabilidad', 'estados')) ?>" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Estados Financieros</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs leading-relaxed text-slate-600">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <h3 class="font-bold text-sm text-slate-900">2.1. Plan de Cuentas Jerárquico (`?c=plan_cuentas&a=index`)</h3>
                <p>Organizado en 4 niveles jerárquicos según las normas contables venezolanas:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong>Nivel 1 (Rubro):</strong> `1` ACTIVO, `2` PASIVO, `3` CAPITAL, `4` INGRESOS, `5` COSTO DE VENTAS, `6` GASTOS.</li>
                    <li><strong>Niveles 2 y 3 (Grupo y Subgrupo):</strong> Cuentas totalizadoras (no reciben asientos directos).</li>
                    <li><strong>Nivel 4 (Cuentas de Detalle / Movimiento):</strong> Ej. `1.1.02.001 BANCO PROVINCIAL`. Son las cuentas imputables en los asientos.</li>
                    <li>Puedes crear nuevas subcuentas haciendo clic en el botón <strong>"+ Subcuenta"</strong> al lado de cualquier grupo; el sistema sugiere automáticamente el siguiente código correlativo.</li>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <h3 class="font-bold text-sm text-slate-900">2.2. Registro de Asientos en el Libro Diario (`?c=contabilidad&a=diario`)</h3>
                <p>Permite registrar asientos manuales o consultar los asientos generados automáticamente por Compras, Ventas y Nómina:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li>Haz clic en <strong>"Nuevo Asiento Contable"</strong>, indica Fecha, Referencia y Concepto/Glosa.</li>
                    <li>Agrega tantas líneas como necesites y usa el botón <strong>"Cuadrar Última Línea"</strong> para igualar automáticamente el Debe y el Haber.</li>
                    <li><strong>Validación Estricta de Partida Doble:</strong> El botón de guardar y el servidor bloquean cualquier asiento donde `Total Debe ≠ Total Haber`.</li>
                    <li>Los asientos nunca se borran físicamente para preservar la trazabilidad de auditoría; si hubo un error, se usa el botón <strong>"Anular"</strong>.</li>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/30 space-y-2">
                <h3 class="font-bold text-sm text-emerald-700">2.3. Impresión para Folios Sellados del Registro Mercantil</h3>
                <p>Tanto en el <strong>Libro Diario</strong> como en el <strong>Libro Mayor</strong> dispones de dos formatos oficiales de impresión para hojas foliadas:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong>Formato Folio Clásico (Estilo Auto-Eléctrico Ítalo):</strong> Encabezado centrado con RIF, número de folio arriba a la derecha, líneas punteadas y cierre con <strong>doble subrayado (`=======`)</strong>. En el Mayor permite agrupar todo el mes bajo una sola línea <code>MOVIMIENTO DEL MES</code>.</li>
                    <li><strong>Formato Tabla Registro Mercantil (Estilo Inversiones Charq'Carnes):</strong> Con recuadro de Identificación Fiscal, Período (Inicio/Cierre), Cuadre del Debe y Haber (`Diferencia: 0,00`) y tabla cuadriculada completa.</li>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <h3 class="font-bold text-sm text-slate-900">2.4. Estados Financieros (`?c=contabilidad&a=estados`)</h3>
                <p>Se calculan en tiempo real a partir de los asientos publicados en el rango de fechas elegido:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong>Pestaña 1 — Balance de Comprobación (8 Columnas):</strong> Saldos Anteriores (Deudor/Acreedor), Movimientos del Período (Debe/Haber) y Saldos Actuales (Deudor/Acreedor).</li>
                    <li><strong>Pestaña 2 — Estado de Resultados (Ganancias y Pérdidas):</strong> Ingresos (`4`) menos Costos (`5`) = Utilidad Bruta, menos Gastos (`6`) = Utilidad o Pérdida del Ejercicio.</li>
                    <li><strong>Pestaña 3 — Balance General (Situación Financiera):</strong> Comprueba automáticamente la ecuación patrimonial `Total Activo = Total Pasivo + Patrimonio`.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- SECCIÓN 3: MÓDULO TRIBUTARIO SENIAT (IVA, ISLR, ART. 72)              -->
    <!-- ===================================================================== -->
    <section id="sec-tributario" class="bloque-manual bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-200">
            <div>
                <span class="text-xs font-mono font-bold uppercase text-amber-600">Capítulo 3</span>
                <h2 class="text-xl font-bold text-slate-900">
                    Módulo Tributario SENIAT: Libros de IVA, Artículo 72 y Retenciones IVA / ISLR
                </h2>
            </div>
            <div class="flex flex-wrap gap-2 no-print">
                <a href="<?= e(route_url('tributario', 'libros')) ?>" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-amber-600 text-white">Libros Compras y Ventas</a>
                <a href="<?= e(route_url('tributario', 'retenciones')) ?>" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Retenciones IVA / ISLR</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-xs leading-relaxed text-slate-600">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <h3 class="font-bold text-sm text-slate-900">3.1. Libro de Compras y Libro de Ventas</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li>Haz clic en <strong>"Registrar Factura de Compra"</strong> o <strong>"Registrar Factura de Venta"</strong>.</li>
                    <li>Ingresa el RIF, Razón Social, N° de Factura, N° de Control y los montos en Base Imponible General (`16%`), Reducida (`8%`), Adicional (`31%`) o Exenta.</li>
                    <li>Si la factura fue pagada en divisas, coloca la base en <strong>IGTF (3%)</strong> y el sistema calcula el impuesto automáticamente.</li>
                    <li>En Compras, puedes marcar la casilla para <strong>emitir en el mismo paso el Comprobante de Retención de IVA (75% o 100%) y el Asiento Contable automático</strong>.</li>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <h3 class="font-bold text-sm text-slate-900">3.2. Cuadro Resumen Art. 72 (Forma 30 SENIAT)</h3>
                <p>En la tercera pestaña de <strong>Libros de IVA</strong> se genera automáticamente el resumen mensual exigido por el Art. 72 del Reglamento de la Ley de IVA:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li>Suma todos los <strong>Débitos Fiscales</strong> (Ventas) y resta los <strong>Créditos Fiscales</strong> (Compras).</li>
                    <li>Aplica el <strong>Excedente de Crédito Fiscal del mes anterior</strong> (configurable en el botón <em>Parámetros UT / Sustraendo</em>).</li>
                    <li>Descuenta las <strong>Retenciones de IVA soportadas</strong> (aplicadas por clientes especiales) y muestra la <strong>Cuota Tributaria a Pagar o Excedente para el próximo mes</strong>.</li>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <h3 class="font-bold text-sm text-slate-900">3.3. Comprobantes de Retención IVA e ISLR</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong>Retención de IVA (Providencia SNAT/2015/0049):</strong> Genera el correlativo inalterable de 14 dígitos (`AAAAMM00000001`). Permite agrupar varias facturas de un mismo proveedor en un solo comprobante.</li>
                    <li><strong>Retención de ISLR (Decreto 1.808):</strong> Incluye el catálogo oficial de conceptos (`001 Honorarios Profesionales`, `053 Servicios Contratistas`, `013 Comisiones`, `041 Alquileres`, etc.).</li>
                    <li>Para Personas Naturales Residentes (`PNR`), aplica automáticamente la fórmula legal del <strong>Sustraendo (`UT × 83,3334 × % Tarifa`)</strong>.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- SECCIÓN 4: MÓDULO LABORAL LOTTT, NÓMINA, CESTATICKET Y RECIBOS        -->
    <!-- ===================================================================== -->
    <section id="sec-nomina" class="bloque-manual bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-200">
            <div>
                <span class="text-xs font-mono font-bold uppercase text-indigo-600">Capítulo 4</span>
                <h2 class="text-xl font-bold text-slate-900">
                    Módulo Laboral LOTTT: Trabajadores, Prestaciones, Nómina, Cestaticket y Recibos
                </h2>
            </div>
            <div class="flex flex-wrap gap-2 no-print">
                <a href="<?= e(route_url('nomina', 'trabajadores')) ?>" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Trabajadores</a>
                <a href="<?= e(route_url('nomina', 'index')) ?>" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-600 text-white">Procesar Nómina</a>
                <a href="<?= e(route_url('nomina', 'cestaticket')) ?>" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-600 text-white">Cestaticket</a>
                <a href="<?= e(route_url('nomina', 'recibos')) ?>" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700">Recibos</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs leading-relaxed text-slate-600">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <h3 class="font-bold text-sm text-slate-900">4.1. Directorio de Trabajadores y Prestaciones (Art. 142 LOTTT)</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li>Registra Cédula, Nombres, Cargo, Fecha de Ingreso, Cuenta Bancaria de 20 dígitos y Salario Base Mensual (puedes escribir el monto en dólares en <em>Ref. en USD</em> y el sistema calcula los Bolívares a tasa BCV).</li>
                    <li>En la tabla principal verás calculada al día la <strong>Antigüedad exacta</strong> (años, meses y días), los trimestres cumplidos, el <strong>Salario Integral Diario</strong> y el monto acumulado de <strong>Prestaciones Sociales (15 días por trimestre + 2 días adicionales por año)</strong>.</li>
                    <li>Desde el botón <strong>"Parámetros LOTTT / IVSS"</strong> puedes ajustar los porcentajes de IVSS (`4%`), RPE (`0,5%`), FAOV (`1%`), aportes patronales y tope de salarios mínimos.</li>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <h3 class="font-bold text-sm text-slate-900">4.2. Procesamiento de Nómina Quincenal / Mensual</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li>En <strong>Nómina Quincenal / Mes</strong>, elige el período, si es 1ra Quincena, 2da Quincena o Mensual, y selecciona si el mes tiene <strong>4 o 5 lunes</strong> (para el cálculo exacto del IVSS y Paro Forzoso).</li>
                    <li>La hoja de trabajo carga a todos los trabajadores activos desglosando <strong>Días Hábiles (11), Días de Descanso (4) y Feriados</strong>.</li>
                    <li>Puedes escribir en vivo montos de <strong>Horas Extras, Bono de Producción / Bonificaciones u Otras Deducciones</strong> y verás cómo cambia el Neto a Pagar al instante.</li>
                    <li>Al marcar <strong>"Generar asiento contable de nómina automáticamente"</strong> y aprobar el lote, el sistema crea el asiento de partida doble en el Libro Diario cargando Sueldos y Aportes Patronales contra Retenciones por Pagar y Sueldos por Pagar.</li>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <h3 class="font-bold text-sm text-slate-900">4.3. Bono de Alimentación / Cestaticket Socialista</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li>Toma automáticamente el monto mensual de <strong>`$40 USD × Tasa Oficial BCV`</strong> y calcula la alícuota diaria (`Monto Mensual / 30`).</li>
                    <li>Si un trabajador faltó injustificadamente, coloca la cantidad de días en la columna <strong>Inasistencias</strong>: el sistema descuenta automáticamente la proporción diaria (`Alícuota Diaria × Días de Inasistencia`).</li>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <h3 class="font-bold text-sm text-slate-900">4.4. Emisión Masiva e Individual de Recibos de Pago</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li>En <strong>Recibos de Pago LOTTT</strong> puedes imprimir el recibo de un trabajador específico o usar el botón <strong>"Imprimir Todos los Recibos del Lote"</strong>.</li>
                    <li>Los recibos de Nómina cumplen con el <strong>Art. 106 LOTTT</strong> y los de Cestaticket incluyen el párrafo legal íntegro del <strong>Art. 4 Parágrafo Primero</strong> con el monto expresado en números y en letras mayúsculas.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- SECCIÓN 5: TABLA MAESTRA DE LOS 19 REPORTES Y CÓMO EMITIRLOS          -->
    <!-- ===================================================================== -->
    <section id="sec-reportes" class="bloque-manual bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="pb-4 mb-4 border-b border-slate-200">
            <span class="text-xs font-mono font-bold uppercase text-emerald-600">Capítulo 5</span>
            <h2 class="text-xl font-bold text-slate-900">
                Directorio Completo de los 19 Reportes del Sistema (Dónde están y cómo imprimirlos)
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-300 text-[10px] font-bold uppercase text-slate-600">
                        <th class="py-3 px-3">N°</th>
                        <th class="py-3 px-3">Nombre del Reporte Oficial</th>
                        <th class="py-3 px-3">Base Legal / Formato</th>
                        <th class="py-3 px-3">Ubicación en el Menú</th>
                        <th class="py-3 px-3">Formatos Disponibles</th>
                        <th class="py-3 px-3 text-right no-print">Acceso Directo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">01</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Catálogo del Plan de Cuentas</td>
                        <td class="py-2.5 px-3">VEN-NIF (4 Niveles)</td>
                        <td class="py-2.5 px-3">2. Contabilidad → Plan de Cuentas</td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Pantalla / Impresión PDF</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('plan_cuentas', 'index')) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">02</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Libro Diario Oficial (Formato Tabla Mercantil)</td>
                        <td class="py-2.5 px-3">Art. 34 Código de Comercio</td>
                        <td class="py-2.5 px-3">Libro Diario → Botón <em>Libro Diario (Registro Mercantil)</em></td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Hoja Foliada / PDF / Excel CSV</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('contabilidad', 'diarioPrint', ['formato' => 'detallado'])) ?>" target="_blank" class="text-emerald-600 font-bold hover:underline">Imprimir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">03</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Resumen Mensual de Diario por Cuenta</td>
                        <td class="py-2.5 px-3">Folio Sellado (Doble Subrayado)</td>
                        <td class="py-2.5 px-3">Libro Diario → Botón <em>Resumen de Diario (Folio)</em></td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Hoja Foliada / PDF</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('contabilidad', 'diarioPrint', ['formato' => 'resumen'])) ?>" target="_blank" class="text-emerald-600 font-bold hover:underline">Imprimir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">04</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Mayor Analítico Resumido por Mes</td>
                        <td class="py-2.5 px-3">Folio Sellado (<code>MOVIMIENTO DEL MES</code>)</td>
                        <td class="py-2.5 px-3">Libro Mayor → Botón <em>Mayor Analítico (Formato Folio)</em></td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Hoja Foliada / PDF</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('contabilidad', 'mayorPrint', ['formato' => 'clasico', 'agrupar_mes' => '1'])) ?>" target="_blank" class="text-emerald-600 font-bold hover:underline">Imprimir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">05</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Mayor Analítico Detallado por Asiento</td>
                        <td class="py-2.5 px-3">Art. 32 Código de Comercio</td>
                        <td class="py-2.5 px-3">Libro Mayor → Botón <em>Mayor Analítico Detallado</em></td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Hoja Foliada / PDF / Excel CSV</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('contabilidad', 'mayorPrint', ['formato' => 'clasico', 'agrupar_mes' => '0'])) ?>" target="_blank" class="text-emerald-600 font-bold hover:underline">Imprimir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">06</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Libro Mayor en Tabla de Registro Mercantil</td>
                        <td class="py-2.5 px-3">Cuadro Fiscal por Cuenta</td>
                        <td class="py-2.5 px-3">Libro Mayor → Botón <em>Libro Mayor (Tabla Mercantil)</em></td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Hoja Foliada / PDF</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('contabilidad', 'mayorPrint', ['formato' => 'tabla'])) ?>" target="_blank" class="text-emerald-600 font-bold hover:underline">Imprimir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">07</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Balance de Comprobación de 8 Columnas</td>
                        <td class="py-2.5 px-3">Saldos Anteriores, Mes y Finales</td>
                        <td class="py-2.5 px-3">Estados Financieros → Pestaña <em>Comprobación</em></td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">PDF / Excel CSV</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('contabilidad', 'estados', ['tab' => 'comprobacion'])) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">08</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Estado de Resultados (Ganancias y Pérdidas)</td>
                        <td class="py-2.5 px-3">Ingresos - Costos - Gastos</td>
                        <td class="py-2.5 px-3">Estados Financieros → Pestaña <em>Estado de Resultados</em></td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">PDF / Excel CSV</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('contabilidad', 'estados', ['tab' => 'resultados'])) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">09</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Balance General (Situación Financiera)</td>
                        <td class="py-2.5 px-3">Activo = Pasivo + Patrimonio</td>
                        <td class="py-2.5 px-3">Estados Financieros → Pestaña <em>Balance General</em></td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">PDF / Excel CSV</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('contabilidad', 'estados', ['tab' => 'general'])) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">10</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Libro Auxiliar de Compras SENIAT</td>
                        <td class="py-2.5 px-3">Art. 75 Reglamento Ley IVA</td>
                        <td class="py-2.5 px-3">3. Tributario → Libros Compras y Ventas</td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Horizontal PDF / Excel CSV</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('tributario', 'libros', ['tab' => 'compras'])) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">11</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Libro Auxiliar de Ventas SENIAT</td>
                        <td class="py-2.5 px-3">Art. 76–77 Reglamento Ley IVA</td>
                        <td class="py-2.5 px-3">3. Tributario → Libros Compras y Ventas</td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Horizontal PDF / Excel CSV</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('tributario', 'libros', ['tab' => 'ventas'])) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">12</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Cuadro Resumen Art. 72 (Borrador Forma 30 IVA)</td>
                        <td class="py-2.5 px-3">Art. 72 Reglamento Ley IVA</td>
                        <td class="py-2.5 px-3">Libros Compras y Ventas → Pestaña <em>Resumen Art. 72</em></td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">PDF / Incluido al final del CSV</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('tributario', 'libros', ['tab' => 'resumen72'])) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">13</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Comprobante de Retención de IVA (14 dígitos)</td>
                        <td class="py-2.5 px-3">Providencia SNAT/2015/0049</td>
                        <td class="py-2.5 px-3">3. Tributario → Retenciones IVA / ISLR</td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Vista Oficial Imprimible PDF</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('tributario', 'retenciones', ['tab' => 'iva'])) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">14</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Comprobante de Retención de ISLR</td>
                        <td class="py-2.5 px-3">Decreto 1.808 (Art. 24)</td>
                        <td class="py-2.5 px-3">3. Tributario → Retenciones IVA / ISLR</td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Vista Oficial Imprimible PDF</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('tributario', 'retenciones', ['tab' => 'islr'])) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">15</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Reporte de Antigüedad y Prestaciones Sociales</td>
                        <td class="py-2.5 px-3">Art. 142 LOTTT (Garantía Trimestral)</td>
                        <td class="py-2.5 px-3">4. Laboral → Directorio Trabajadores</td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Pantalla / Impresión</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('nomina', 'trabajadores')) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">16</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Sábana General de Nómina Quincenal / Mensual</td>
                        <td class="py-2.5 px-3">LOTTT / IVSS / RPE / FAOV</td>
                        <td class="py-2.5 px-3">4. Laboral → Nómina Quincenal / Mes</td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Sábana con Firma / Excel CSV</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('nomina', 'index')) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">17</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Relación Mensual de Bono de Alimentación</td>
                        <td class="py-2.5 px-3">Ley Cestaticket Socialista</td>
                        <td class="py-2.5 px-3">4. Laboral → Bono de Alimentación</td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Relación PDF / Excel CSV</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('nomina', 'cestaticket')) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">18</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Recibos de Pago de Nómina (Individual o Lote)</td>
                        <td class="py-2.5 px-3">Art. 106 LOTTT (Monto en Letras)</td>
                        <td class="py-2.5 px-3">4. Laboral → Recibos de Pago LOTTT</td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Impresión Individual y Masiva</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('nomina', 'recibos')) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-3 font-mono font-bold">19</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">Recibos Legales de Cestaticket (Individual o Lote)</td>
                        <td class="py-2.5 px-3">Art. 4 Parágrafo Primero</td>
                        <td class="py-2.5 px-3">4. Laboral → Recibos de Pago LOTTT</td>
                        <td class="py-2.5 px-3 font-mono text-[11px]">Impresión Individual y Masiva</td>
                        <td class="py-2.5 px-3 text-right no-print"><a href="<?= e(route_url('nomina', 'recibos')) ?>" class="text-emerald-600 font-bold hover:underline">Abrir →</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- SECCIÓN 6: FLUJO RECOMENDADO DE CIERRE MENSUAL                        -->
    <!-- ===================================================================== -->
    <section id="sec-cierre" class="bloque-manual bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="pb-4 mb-5 border-b border-slate-200">
            <span class="text-xs font-mono font-bold uppercase text-amber-600">Capítulo 6</span>
            <h2 class="text-xl font-bold text-slate-900">
                Lista de Chequeo: ¿Qué orden seguir cada quincena y cada fin de mes?
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs text-slate-600">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-600 font-mono font-bold text-[11px]">PASO 1 • DIARIO</span>
                <h3 class="font-bold text-slate-900 mt-2 mb-1">Cargar Compras y Ventas</h3>
                <p>Selecciona la empresa en la barra superior, registra las facturas en <strong>Libros de Compras y Ventas</strong> marcando la casilla de generar asiento contable y emite al instante los comprobantes de retención de IVA e ISLR.</p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="px-2 py-0.5 rounded bg-indigo-500/15 text-indigo-600 font-mono font-bold text-[11px]">PASO 2 • DÍA 15 Y 30</span>
                <h3 class="font-bold text-slate-900 mt-2 mb-1">Procesar Nómina y Cestaticket</h3>
                <p>Procesa la 1ra y 2da quincena en <strong>Nómina Quincenal / Mes</strong> con asiento contable automático, liquida el lote mensual de <strong>Bono de Alimentación</strong> e imprime todos los recibos para firma de los trabajadores.</p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-600 font-mono font-bold text-[11px]">PASO 3 • DECLARACIÓN IVA</span>
                <h3 class="font-bold text-slate-900 mt-2 mb-1">Revisar Cuadro Resumen Art. 72</h3>
                <p>Abre la pestaña <strong>Resumen Art. 72 (Forma 30)</strong>, verifica los débitos, créditos y retenciones del período y exporta a Excel los Libros de Compras y Ventas.</p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="px-2 py-0.5 rounded bg-sky-500/15 text-sky-600 font-mono font-bold text-[11px]">PASO 4 • CIERRE CONTABLE</span>
                <h3 class="font-bold text-slate-900 mt-2 mb-1">Emitir Estados y Folios</h3>
                <p>Registra en el <strong>Libro Diario</strong> cualquier asiento de ajuste (depreciaciones, comisiones bancarias), verifica el cuadre en <strong>Estados Financieros</strong> e imprime el <strong>Resumen de Diario y Mayor Analítico</strong> para los libros sellados.</p>
            </div>
        </div>
    </section>

</div>

<script>
function filtrarSeccionesManual(termino) {
    const q = (termino || '').trim().toLowerCase();
    document.querySelectorAll('.bloque-manual').forEach((sec) => {
        if (!q) {
            sec.style.display = '';
            return;
        }
        const texto = (sec.textContent || '').toLowerCase();
        sec.style.display = texto.includes(q) ? '' : 'none';
    });
}
</script>
