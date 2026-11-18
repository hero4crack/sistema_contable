# Arquitectura y Especificación Técnica — Juris Contable

## 1. Visión General y Contexto Normativo
**Juris Contable** es un sistema web integral diseñado para escritorios contables venezolanos, orientado a gestionar múltiples empresas clientes bajo una arquitectura **Multi-Tenant** en una única base de datos relacional, aislando estrictamente toda la información operativa, contable, tributaria y laboral mediante la llave foránea `empresa_id`.

Cumple con los lineamientos técnicos y legales vigentes en Venezuela:
- **SENIAT:** Providencia Administrativa SNAT/2011/0071 (Facturación), Providencia SNAT/2015/0049 (Retenciones de IVA 75% / 100%, correlativo `AAAAMM00000001`), Decreto 1.808 (Retenciones de ISLR con cálculo de sustraendo basado en Unidad Tributaria y factor 83.3334), y Libros Auxiliares de Compras y Ventas.
- **Código de Comercio Venezolano (Art. 32 al 44):** Contabilidad por partida doble estricta (`SUM(debe) === SUM(haber)`), Libro Diario, Libro Mayor, Balance de Comprobación y Estado de Resultados.
- **LOTTT / IVSS / RPE / FAOV / INCES:** Gestión de trabajadores, cálculo de nómina quincenal y mensual, retenciones del trabajador, aportes patronales, Cestaticket Socialista y emisión de recibos de pago.

---

## 2. Stack Tecnológico y Reglas de Arquitectura
- **Entorno de Ejecución:** PHP 8.2+ nativo orientado a objetos (POO) sobre XAMPP (Apache + MySQL/MariaDB), bajo patrón **MVC (Modelo-Vista-Controlador)** limpio, sin dependencias de frameworks externos pesados.
- **Motor de Base de Datos:** MySQL / MariaDB con cotejamiento `utf8mb4_unicode_ci`.
  - Conexión centralizada mediante singleton `PDO`.
  - `PDO::ATTR_EMULATE_PREPARES => false` y `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.
  - Uso obligatorio de transacciones (`beginTransaction()`, `commit()`, `rollBack()`) en operaciones críticas (clonación de plan de cuentas, registro de asientos, generación de comprobantes y nóminas).
- **Frontend:**
  - HTML5 semántico.
  - **Tailwind CSS** (vía CDN configurado con paleta corporativa *Slate/Indigo/Emerald*).
  - **JavaScript Vanilla (ES6+)** empleando `Fetch API` para peticiones asíncronas.
  - **SweetAlert2** para confirmaciones, notificaciones toast y alertas de validación.
  - Diseño administrativo sobrio/oscuro en barra lateral colapsable y área de trabajo clara de alto contraste para lectura financiera.
- **Seguridad y Sesiones:**
  - Autenticación con `password_hash()` (`PASSWORD_DEFAULT`) y `password_verify()`.
  - Control de acceso basado en roles (`admin`, `contador`, `asistente`).
  - Protección **CSRF** obligatoria en todas las peticiones `POST` (formularios y llamadas AJAX/Fetch).
  - Contexto Multi-Empresa dinámico mediante `$_SESSION['empresa_activa_id']` y `$_SESSION['empresa_activa_nombre']`, con selector global en la barra superior (`header.php`).

---

## 3. Estructura del Proyecto (`C:\xampp\htdocs\sistema_contable`)

```text
sistema_contable/
├── ARQUITECTURA.md
├── index.php                     # Redirección automática a public/
├── .htaccess                     # Reglas de reescritura hacia public/
├── config/
│   ├── app.php                   # Constantes globales, URL base, zona horaria (America/Caracas)
│   └── database.php              # Credenciales de conexión MySQL/MariaDB
├── core/
│   ├── Database.php              # Clase Singleton PDO y ayudantes transaccionales
│   ├── Controller.php            # Controlador base (renderizado de vistas, JSON, validaciones Auth/Rol)
│   ├── Session.php               # Gestión segura de sesiones, flash messages y tenant activo
│   └── Csrf.php                  # Generación y verificación de tokens CSRF
├── models/
│   ├── UsuarioModel.php          # Gestión de usuarios y autenticación
│   ├── EmpresaModel.php          # CRUD Multi-Empresa, métricas de dashboard y clonación de catálogo
│   ├── PlanCuentasModel.php      # Catálogo contable jerárquico, CRUD con integridad y clonación base
│   ├── AsientoModel.php          # Partida doble estricta, Libro Diario, Mayor Analítico y Estados Financieros
│   ├── TributarioModel.php       # Libros Compras/Ventas, Resumen Art. 72, Retenciones IVA (14 díg.) e ISLR (Dec. 1808)
│   └── NominaModel.php           # Trabajadores LOTTT, Antigüedad/Prestaciones Art. 142, Nómina, Cestaticket y Asiento Automático
├── controllers/
│   ├── AuthController.php        # Login, Logout y validación de credenciales
│   ├── DashboardController.php   # Panel principal con KPIs y ficha fiscal de la empresa activa
│   ├── EmpresaController.php     # Gestión de empresas clientes y cambio de contexto (Tenant Switcher)
│   ├── PlanCuentasController.php # CRUD jerárquico del Plan de Cuentas (?c=plan_cuentas&a=index)
│   ├── ContabilidadController.php# Libro Diario, Mayor Analítico y Estados Financieros (?c=contabilidad&a=...)
│   ├── TributarioController.php  # Libros IVA y Comprobantes de Retención IVA / ISLR (?c=tributario&a=...)
│   └── NominaController.php      # Directorio Personal, Nómina General, Cestaticket y Recibos (?c=nomina&a=...)
├── views/
│   ├── layouts/
│   │   ├── header.php            # Configuración Tailwind (darkMode: 'class'), estilos impresión y Modo Claro/Oscuro
│   │   ├── sidebar.php           # Menú lateral colapsable + Topbar con selector Multi-Empresa y botón Sol/Luna
│   │   └── footer.php            # Cierre de layout, scripts globales y configuración CSRF
│   ├── auth/
│   │   └── login.php             # Pantalla de acceso corporativo
│   ├── dashboard/
│   │   └── index.php             # Vista ejecutiva de la empresa seleccionada y resumen del escritorio
│   ├── empresas/
│   │   └── index.php             # Directorio y modal CRUD de empresas clientes + visor de Plan de Cuentas
│   ├── plan_cuentas/
│   │   └── index.php             # Vista jerárquica indentada con filtros en vivo y CRUD protegido
│   ├── contabilidad/
│   │   ├── diario.php            # Formulario dinámico de Asientos (Partida Doble) + Libro Diario Legal
│   │   ├── mayor.php             # Libro Mayor Analítico con Saldo Anterior y Saldo Acumulado por naturaleza
│   │   └── estados.php           # Balance de Comprobación, Estado de Resultados y Balance General
│   ├── tributario/
│   │   ├── libros.php            # Libro de Compras, Libro de Ventas y Cuadro Resumen Art. 72 (Forma 30 SENIAT)
│   │   ├── retenciones.php       # Emisión y consulta de Comprobantes de Retención de IVA e ISLR
│   │   ├── comprobante_iva_print.php  # Formato oficial imprimible SNAT/2015/0049 (SNAT/2025/000054)
│   │   └── comprobante_islr_print.php # Formato oficial imprimible Decreto 1.808 (Art. 24)
│   └── nomina/
│       ├── trabajadores.php      # Directorio de Trabajadores CRUD + Antigüedad y Prestaciones (Art. 142 LOTTT)
│       ├── index.php             # Generador de Nómina Quincenal/Mensual + Sábana Horizontal + Integración Contable
│       ├── cestaticket.php       # Control y Liquidación Mensual de Bono de Alimentación (Ley Cestaticket)
│       ├── recibos.php           # Centro de Consulta e Impresión Masiva/Individual de Recibos
│       └── recibo_print.php      # Vista Imprimible de Recibos de Nómina (Art. 106 LOTTT) y Cestaticket (Art. 4)
│   └── layouts/
│       ├── header.php            # Cabecera HTML institucional clara (Blanco + Verde Esmeralda) y @media print
│       ├── sidebar.php           # Navegación lateral con logotipo Juris Contable y selector de empresa activa
│       ├── footer.php            # Cierre de layout y carga de scripts
│       ├── membrete_reporte.php  # Membrete oficial reutilizable con logo del cliente, RIF y título de reporte
│       └── pie_reporte.php       # Pie institucional uniforme para reportes y recibos imprimibles
├── public/
│   ├── index.php                 # Front Controller y enrutador MVC (?c=controlador&a=accion)
│   ├── .htaccess                 # Protección y manejo de rutas en public/
│   ├── uploads/
│   │   └── logos/                # Logotipos cargados por cada empresa cliente (PNG/JPG/WEBP, máx. 2MB)
│   └── assets/
│       ├── img/
│       │   └── logo-juris-contable.png # Logotipo oficial del sistema Juris Contable
│       └── js/
│           └── app.js            # Lógica global: cambio de empresa activa, modales, Fetch wrapper con CSRF
└── database/
    └── juris_contable.sql        # Esquema maestro único de todos los módulos + Seed inicial + Plan de Cuentas base
```

---

## 4. Mapa de Módulos y Fases de Desarrollo

### Fase 1: Núcleo, Base de Datos, Autenticación y Multi-Empresa (Completada en esta sesión)
- Infraestructura MVC, seguridad CSRF, sesiones y manejo de errores.
- Esquema integral de base de datos para los 4 módulos (`database/juris_contable.sql`).
- Tabla `plan_cuentas_plantilla` y clonación automática a `plan_cuentas` al registrar cada nueva empresa.
- Selector superior de Empresa Activa (`Tenant Switcher`) vía AJAX.
- CRUD completo de Empresas Clientes y consulta rápida de su Plan de Cuentas clonado.
- Dashboard conectado en tiempo real a los indicadores de la empresa en sesión.

### Fase 2: Contabilidad General y Libros Legales
- Gestión completa del Plan de Cuentas por empresa (`PlanCuentasController`).
- Registro de Asientos Contables con validación estricta de partida doble (`SUM(debe) === SUM(haber)`).
- Reportes contables: Libro Diario, Libro Mayor Analítico, Balance de Comprobación de 8 columnas y Estado de Resultados.

### Fase 3: Módulo Tributario (SENIAT)
- Libro de Compras y Libro de Ventas con cálculo automático de Base Imponible, Alícuota General (16%), Reducida (8%), Adicional (31%) y Compras Exentas.
- Emisión de Comprobantes de Retención de IVA (`AAAAMM00000001`) y Retención de ISLR (con tabla de conceptos, porcentajes y sustraendo según UT configurada).

### Fase 4: Módulo de Nómina y Beneficios (LOTTT)
- Registro de trabajadores por empresa (Cédula, Cargo, Salario Base, Fecha de Ingreso, Riesgo IVSS).
- Procesamiento de Nómina Quincenal/Mensual con cálculo automático de SSO (4%), RPE (0.5%), FAOV (1%), Aportes Patronales y Cestaticket.
- Generación de Recibos de Pago e integración opcional con asiento contable de nómina.

---

## 5. Identidad Visual Institucional y Membrete Oficial
- **Paleta Institucional:** Modo claro único en **Blanco (`#FFFFFF` / `#F8FAFC`)** y **Verde Esmeralda (`emerald-700` / `#047857`)**, sin modo oscuro ni interruptores de tema.
- **Logotipo del Sistema:** `public/assets/img/logo-juris-contable.png` integrado en `views/auth/login.php` y `views/layouts/sidebar.php`.
- **Logotipos por Empresa Cliente:** Columna `empresas.logo_path` (`VARCHAR(255) NULL`), carga segura mediante `POST + CSRF` (`multipart/form-data`) en `EmpresaController::guardar()`, almacenamiento en `public/uploads/logos/` y previsualización en el directorio y selector de empresa activa.
- **Membrete Oficial Unificado:** `views/layouts/membrete_reporte.php` y `views/layouts/pie_reporte.php` integrados en los reportes contables, libros fiscales, comprobantes de retención IVA/ISLR, sábana de nómina y recibos imprimibles, con reglas `@media print` (`print-color-adjust: exact`).
