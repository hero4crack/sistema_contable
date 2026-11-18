# Manual de Usuario Oficial — Juris Contable (v4.0.0-Integral)
**Sistema Integral Contable, Tributario (SENIAT) y Laboral (LOTTT) Multi-Empresa**

---

## 1. Acceso al Sistema y Credenciales Iniciales
- **Ruta Principal en el Navegador:** `http://localhost/sistema_contable/public/` (o `http://localhost/sistema_contable/`)
- **Ruta Directa al Manual Interactivo dentro del Sistema:** `http://localhost/sistema_contable/public/index.php?c=manual&a=index`
- **Credenciales por Defecto:**
  - **Administrador General:** `admin@juriscontable.com` | Contraseña: `Admin123*`
  - **Contador:** `contador@juriscontable.com` | Contraseña: `Admin123*`

---

## 2. Selector Multi-Empresa (Tenant Switcher), Tema Visual y Dólar BCV Automático

### 2.1. Cómo cambiar de Empresa Activa
En la barra superior izquierda verás siempre el selector **Empresa Activa**.
- Al seleccionar otra compañía (por ejemplo, cambiar de `J-40123456-7 — INVERSIONES Y ADMINISTRADORA EL ROSAL, C.A.` a `J-50987654-3 — DISTRIBUIDORA TECNOLÓGICA ORINOCO, S.A.`), **todo el sistema filtra automáticamente** su Plan de Cuentas, Libro Diario, Libro Mayor, Estados Financieros, Libros de Compras/Ventas, Retenciones y Nómina para mostrar únicamente los datos de esa empresa.

### 2.2. Cómo crear una nueva Empresa Cliente
1. En el menú izquierdo, entra en **1. Principal → Empresas Clientes** (`?c=empresa&a=index`).
2. Haz clic en el botón **"Nueva Empresa Cliente"**.
3. Llena el RIF con guiones (`J-12345678-9`), Razón Social, Dirección Fiscal, Representante Legal, Tipo de Contribuyente (`Ordinario`, `Especial` o `Formal`) y `% Retención IVA` (`75%`, `100%` o `0%`).
4. Al guardar, el sistema **clona automáticamente las 220 cuentas del Plan de Cuentas Venezolano** para esa empresa dentro de una transacción segura.

### 2.3. Sincronización Automática del Dólar BCV (`Bs. / $`)
- Al abrir cualquier pantalla del sistema, éste consulta automáticamente en segundo plano la **Tasa Oficial del Banco Central de Venezuela (BCV)** vigente del día.
- Actualiza automáticamente el botón superior **`BCV: Bs. XX,XX / $`** y recalcula en base de datos el monto mensual del **Bono de Alimentación (`$40 USD × Tasa BCV`)**.
- Si deseas ajustar la tasa manualmente (por ejemplo, si trabajas sin internet), haz clic sobre el botón **`BCV: Bs. XX,XX / $`** en la barra superior.

### 2.4. Modo Claro / Modo Oscuro
- En la esquina superior derecha dispones del botón **Tema (Sol / Luna)** para alternar entre el **Modo Oscuro** (descanso visual) y el **Modo Claro** (lectura en oficinas iluminadas). Tu preferencia queda guardada automáticamente.

---

## 3. Módulo de Contabilidad General y Libros Legales (Código de Comercio)

### 3.1. Plan de Cuentas Contable (`?c=plan_cuentas&a=index`)
- Muestra la estructura jerárquica de 4 niveles (`1 ACTIVO`, `2 PASIVO`, `3 CAPITAL`, `4 INGRESOS`, `5 COSTOS`, `6 GASTOS`).
- **Cuentas de Grupo (Niveles 1, 2 y 3):** Totalizan saldos pero no reciben asientos directos.
- **Cuentas Imputables / Detalle (Nivel 4):** Reciben movimientos en los asientos contables.
- Para agregar una cuenta hija dentro de un grupo (por ejemplo, un nuevo banco dentro de `1.1.02 Bancos`), haz clic en **"+ Subcuenta"** en la fila del grupo padre y el sistema sugerirá el código correlativo siguiente (`1.1.02.006`).
- **Regla de Integridad:** El sistema impide eliminar cuentas que ya posean movimientos en el Libro Diario o que tengan subcuentas hijas.

### 3.2. Libro Diario y Asientos de Partida Doble (`?c=contabilidad&a=diario`)
1. Haz clic en **"Nuevo Asiento Contable"**.
2. Selecciona la Fecha, Referencia (ej. `FAC-00120` o `TRANSF-991`) y escribe el Concepto/Glosa.
3. En las líneas del asiento, selecciona la cuenta imputable e ingresa el monto en **Debe** o en **Haber**.
4. Puedes agregar más filas con **"+ Agregar Línea"** y usar **"Cuadrar Última Línea"** para que el sistema calcule automáticamente la diferencia y cuadre el asiento.
5. **Regla de Partida Doble:** El sistema solo permite guardar asientos donde `Total Debe === Total Haber` (`Diferencia = Bs. 0,00`).

### 3.3. Cómo imprimir el Libro Diario para Folios Sellados del Registro Mercantil
En la parte superior de **Libro Diario** tienes dos botones especializados de impresión oficial:
- **Botón "Libro Diario (Registro Mercantil)":** Abre el formato en tabla cuadriculada oficial (estilo *Inversiones Charq'Carnes*) con recuadro de Identificación Fiscal, Período, Cuadre del Debe y Haber (`Diferencia: 0,00`) y numeración de operaciones.
- **Botón "Resumen de Diario (Folio)":** Abre el formato clásico resumido por cuenta (estilo *Auto-Eléctrico Ítalo*) con número de folio arriba a la derecha, línea punteada y **doble subrayado (`=======`)** en los totales.

### 3.4. Libro Mayor Analítico (`?c=contabilidad&a=mayor`)
- Calcula automáticamente para cada cuenta su **Saldo Anterior** (previo a la fecha de inicio consultada), sus movimientos cronológicos y su **Saldo Acumulado** línea por línea respetando su naturaleza:
  - Cuentas **Deudoras** (`Activo`, `Costos`, `Gastos`): `Saldo = Saldo Anterior + Debe - Haber`.
  - Cuentas **Acreedoras** (`Pasivo`, `Capital`, `Ingresos`): `Saldo = Saldo Anterior + Haber - Debe`.
- **Impresión Oficial para Folios Sellados:**
  - Haz clic en **"Mayor Analítico (Formato Folio)"** para abrir la vista de impresión con línea punteada y doble subrayado. En la barra superior de esa vista puedes alternar con 1 clic entre:
    - **Mayor Resumido por Mes (`MOVIMIENTO DEL MES`)** (ideal para ahorrar folios sellados).
    - **Mayor Analítico Detallado** (asiento por asiento).
    - **Libro Mayor (Tabla Mercantil)**.

### 3.5. Estados Financieros (`?c=contabilidad&a=estados`)
Cuenta con 3 pestañas calculadas en vivo y exportables a Excel (`.csv`) o PDF:
1. **Balance de Comprobación (8 Columnas):** Saldos Anteriores (Deudor/Acreedor), Movimientos del Período (Debe/Haber) y Saldos Actuales (Deudor/Acreedor).
2. **Estado de Resultados (Ganancias y Pérdidas):** Ingresos menos Costos y Gastos = Utilidad o Pérdida Neta del Ejercicio.
3. **Balance General (Situación Financiera):** Activos vs. Pasivos + Patrimonio (incorporando automáticamente el resultado del ejercicio).

---

## 4. Módulo Tributario SENIAT (IVA, ISLR, IGTF y Artículo 72)

### 4.1. Libro de Compras y Libro de Ventas (`?c=tributario&a=libros`)
- **Registrar Factura de Compra:**
  - Ingresa Fecha, RIF y Nombre del Proveedor, Tipo de Documento (`Factura`, `Nota de Crédito`, `Nota de Débito`), N° de Factura y N° de Control.
  - Ingresa los montos en **Compras Exentas**, **Base Imponible General (16%)**, **Base Reducida (8%)**, **Base Adicional (31%)** y **Base IGTF (3%)**.
  - Si la empresa es Contribuyente Especial, deja marcada la casilla **"Generar Comprobante de Retención de IVA automáticamente"** (`75%` o `100%`).
  - Deja marcada la casilla **"Generar asiento contable automático en el Libro Diario"** para que la compra quede contabilizada de inmediato.
- **Registrar Factura de Venta:**
  - Ingresa los datos del cliente y de la factura. Si un cliente Contribuyente Especial te aplicó retención de IVA, ingresa el número de comprobante y el monto retenido para que se descuente automáticamente en el Cuadro Resumen Art. 72.

### 4.2. Cuadro Resumen Artículo 72 — Borrador Forma 30 (`?c=tributario&a=libros&tab=resumen72`)
- Muestra la conciliación mensual exacta para declarar en el portal del SENIAT:
  - Total Débitos Fiscales menos Total Créditos Fiscales.
  - Aplicación del **Excedente de Crédito Fiscal del mes anterior**.
  - Descuento de **Retenciones de IVA soportadas en Ventas**.
  - Determinación de la **Cuota Tributaria a Pagar** o nuevo excedente para el mes siguiente.

### 4.3. Comprobantes de Retención de IVA e ISLR (`?c=tributario&a=retenciones`)
- **Pestaña Retenciones de IVA (Providencia SNAT/2015/0049):**
  - Genera el correlativo de 14 dígitos (`AAAAMM00000001`).
  - Permite seleccionar varias facturas de compra de un mismo proveedor para agruparlas en un único comprobante oficial e imprimirlo en formato horizontal.
- **Pestaña Retenciones de ISLR (Decreto 1.808):**
  - Selecciona el concepto del Decreto 1.808 (ej. `001 Honorarios Profesionales`, `053 Servicios Contratistas`, `013 Comisiones`, `041 Alquileres`).
  - Si es Persona Natural Residente (`PNR`), el sistema resta automáticamente el **Sustraendo (`UT × 83,3334 × % Tarifa`)** y genera el comprobante oficial imprimible.

---

## 5. Módulo Laboral LOTTT: Trabajadores, Nómina, Cestaticket y Recibos

### 5.1. Directorio de Trabajadores y Prestaciones Sociales (`?c=nomina&a=trabajadores`)
- Haz clic en **"Registrar Trabajador"** para cargar su ficha laboral (Cédula, Nombres, Cargo, Fecha de Ingreso, Cuenta Nómina de 20 dígitos y Salario Base).
- **Conversor USD → Bs. integrado:** Si escribes el sueldo acordado en dólares en la casilla **Ref. en USD ($ BCV)**, el sistema multiplica automáticamente por la tasa BCV activa y llena el Salario Base en Bolívares.
- En el listado se calcula automáticamente la **Antigüedad exacta** y el acumulado de **Prestaciones Sociales (Art. 142 LOTTT: 15 días por trimestre + 2 días adicionales por año)**.

### 5.2. Procesamiento de Nómina Quincenal / Mensual (`?c=nomina&a=index`)
1. Selecciona el Período, Tipo de Nómina (`1ra Quincena`, `2da Quincena` o `Mensual`) y si el mes tiene **4 o 5 lunes** (para el cálculo del IVSS y RPE).
2. Revisa o ajusta en vivo en la tabla los **Días Hábiles**, **Días de Descanso**, **Días Feriados**, **Horas Extras** o **Bonificaciones / Bono Producción**.
3. Deja marcada la opción **"Generar asiento contable de nómina automáticamente en el Libro Diario"** y haz clic en **"Procesar y Aprobar Nómina"**.
4. Una vez procesado el lote, puedes **Imprimir la Sábana Horizontal de Nómina** (con columna de firma), **Exportar a Excel (`.csv`)** o **Imprimir Todos los Recibos del Lote**.

### 5.3. Bono de Alimentación / Cestaticket (`?c=nomina&a=cestaticket`)
- Toma el monto mensual base (`$40 USD × Tasa BCV`, equivalente a `Valor Diario = Monto Mensual / 30`).
- Si un trabajador tuvo faltas injustificadas en el mes, coloca el número de días en la columna **Inasistencias** y el sistema descontará automáticamente la alícuota diaria.
- Haz clic en **"Registrar Lote de Cestaticket"** para guardar la relación y habilitar la impresión de los recibos legales.

### 5.4. Centro de Emisión de Recibos (`?c=nomina&a=recibos`)
- Permite consultar cualquier lote histórico de Nómina o de Cestaticket e imprimir:
  - **Un recibo individual** (para entregar o enviar en PDF a un trabajador).
  - **Todos los recibos del lote en un solo clic** (con salto de página automático, monto en números y en letras, y párrafo legal del Art. 106 LOTTT o Art. 4 de la Ley del Cestaticket Socialista).
