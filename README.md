<div align="center">

# 📊 Juris Contable

**Sistema Web Integral de Contabilidad, Tributación (SENIAT) y Nómina (LOTTT) Multi-Empresa**

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MariaDB](https://img.shields.io/badge/MariaDB%20%2F%20MySQL-10.4%2B-003545?style=for-the-badge&logo=mariadb&logoColor=white)](https://mariadb.org/)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![JavaScript](https://img.shields.io/badge/Vanilla_JS-ES6%2B-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/)
[![Normativa](https://img.shields.io/badge/Normativa-SENIAT%20%7C%20LOTTT%20%7C%20C%C3%B3d.%20Comercio-10B981?style=for-the-badge)](#)

*Diseñado para escritorios contables y empresas en Venezuela bajo arquitectura **Multi-Tenant**, con estricto aislamiento de datos por `empresa_id`, partida doble transaccional y sincronización automática con la tasa oficial del Dólar BCV.*

</div>

---

## ✨ Características Principales

- 🏢 **Arquitectura Multi-Empresa (`Multi-Tenant`):** Administra múltiples compañías desde un único servidor local con cambio instantáneo de contexto (`Tenant Switcher`) y clonación automática del Plan de Cuentas Base (220 cuentas venezolanas).
- 💱 **Sincronización Automática Dólar BCV:** Consulta en segundo plano la tasa oficial del Banco Central de Venezuela, actualiza el indicador global y recalcula automáticamente el **Cestaticket (`$40 USD × BCV`)** y salarios referenciados en divisas.
- 📚 **Contabilidad General y Libros de Registro Mercantil:** Partida doble estricta (`SUM(debe) === SUM(haber)`), Libro Diario, Libro Mayor Analítico, Estados Financieros y **formatos oficiales imprimibles sobre hojas foliadas** (con doble subrayado `=======`).
- 🧾 **Módulo Tributario SENIAT:** Libros Auxiliares de Compras y Ventas (16%, 8%, 31%, Exentas e IGTF 3%), Cuadro Resumen Art. 72 (Forma 30) y emisión de Comprobantes de Retención de **IVA (`AAAAMM00000001` - SNAT/2015/0049)** e **ISLR (Decreto 1.808 con sustraendo)**.
- 👷 **Módulo Laboral LOTTT y Nómina:** Ficha de trabajadores, cálculo automático de Antigüedad y Prestaciones Sociales (Art. 142 LOTTT), procesamiento de nómina por 4 o 5 lunes (IVSS, RPE, FAOV, INCES), Bono de Alimentación (Cestaticket) e impresión individual/masiva de recibos con monto en letras.
- 🔐 **Gestión de Usuarios, Roles y Permisos:** Panel exclusivo para el Administrador para crear otros administradores, contadores o asistentes con permisos por módulo, y actualizar correos y contraseñas.
- 🛡️ **Seguridad Reforzada:** Consultas preparadas nativas (`PDO::ATTR_EMULATE_PREPARES => false`), transacciones atómicas, contraseñas con `password_hash()`, protección **CSRF** y flujo **100% `POST`** en formularios de filtro y endpoints AJAX.
- 🌓 **Modo Claro / Modo Oscuro:** Selector persistente sin parpadeo (`FOUC`).

---

## 🧩 Módulos del Sistema

| Módulo | Controlador | Funcionalidades Clave |
| :--- | :--- | :--- |
| **1. Multi-Empresa & BCV** | `EmpresaController` | CRUD de empresas clientes, clonación de catálogo contable, selector global y tasa BCV en vivo. |
| **2. Contabilidad General** | `PlanCuentasController`<br>`ContabilidadController` | Plan de cuentas de 4 niveles, Asientos de Partida Doble, Libro Diario, Mayor Analítico, Comprobación (8 col.), Resultados y Balance General. |
| **3. Tributario SENIAT** | `TributarioController` | Libro de Compras, Libro de Ventas, Resumen Art. 72, Retenciones IVA (14 dígitos) e ISLR con sustraendo UT × `83,3334`. |
| **4. Laboral LOTTT** | `NominaController` | Directorio de personal, Prestaciones Art. 142, Nómina Quincenal/Mensual con asiento contable automático, Cestaticket y Recibos. |
| **5. Usuarios y Manual** | `UsuarioController`<br>`ManualController` | Control de acceso por roles/permisos (`admin`, `contador`, `asistente`) y Manual de Usuario interactivo integrado. |

---

## 🖨️ Reportes Oficiales Incluidos (19 Formatos)

<details>
<summary><strong>Ver listado completo de los 19 reportes imprimibles y exportables a Excel (CSV)</strong></summary>

1. **Catálogo del Plan de Cuentas** (4 niveles jerárquicos con saldos).
2. **Libro Diario Legal** (Formato Tabla Registro Mercantil con cuadre Debe/Haber).
3. **Resumen Mensual de Diario** (Formato Folio Sellado con doble subrayado).
4. **Mayor Analítico Resumido por Mes** (`MOVIMIENTO DEL MES` para folios sellados).
5. **Mayor Analítico Detallado** (Asiento por asiento con saldo acumulado).
6. **Libro Mayor en Tabla Mercantil** (Formato cuadro por cuenta).
7. **Balance de Comprobación de 8 Columnas**.
8. **Estado de Resultados** (Ganancias y Pérdidas).
9. **Balance General** (Estado de Situación Financiera).
10. **Libro Auxiliar de Compras SENIAT** (Art. 75 Reglamento Ley IVA).
11. **Libro Auxiliar de Ventas SENIAT** (Art. 76–77 Reglamento Ley IVA).
12. **Cuadro Resumen Art. 72** (Borrador Forma 30 IVA).
13. **Comprobante de Retención de IVA** (Providencia SNAT/2015/0049).
14. **Comprobante de Retención de ISLR** (Decreto 1.808, Art. 24).
15. **Antigüedad y Prestaciones Sociales** (Art. 142 LOTTT).
16. **Sábana General de Nómina** (Con columna de firma y exportación CSV).
17. **Relación Mensual de Bono de Alimentación** (Cestaticket Socialista).
18. **Recibos de Pago de Nómina** (Individual y Masivo - Art. 106 LOTTT).
19. **Recibos Legales de Cestaticket** (Individual y Masivo - Art. 4 Parágrafo Primero).

</details>

---

## 🗂️ Estructura del Proyecto

```text
sistema_contable/
├── config/                 # Constantes globales, helper monto_en_letras_ves() y PDO
├── controllers/            # Controladores MVC (Auth, Dashboard, Empresa, Contabilidad, Tributario, Nomina, Usuario, Manual)
├── core/                   # Singleton Database, Session, Csrf y Controller base
├── database/               # Esquema integral (juris_contable.sql) y migraciones incrementales
├── models/                 # Lógica de negocio y consultas SQL preparadas Multi-Tenant
├── public/                 # Front Controller (index.php) y assets JS (ES6+ Fetch API)
├── views/                  # Vistas HTML5 + Tailwind CSS (layouts, reportes e impresión)
├── ARQUITECTURA.md         # Especificación técnica del proyecto
└── MANUAL_DE_USUARIO.md    # Guía completa de operación para el equipo contable
```

---

## 🚀 Instalación y Puesta en Marcha (XAMPP / Servidor Local)

1. **Clonar o copiar el proyecto** en el directorio web de Apache:
   ```text
   C:\xampp\htdocs\sistema_contable
   ```
2. **Iniciar servicios** de **Apache** y **MySQL / MariaDB** desde el Panel de Control de XAMPP.
3. **Base de datos (Auto-aprovisionamiento):**
   - Al abrir el sistema por primera vez, `Core\Database` detecta si `juris_contable` no existe y ejecuta automáticamente `database/juris_contable.sql`.
   - También puedes importar manualmente `database/juris_contable.sql` desde **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. **Acceder en el navegador:**
   ```text
   http://localhost/sistema_contable/public/
   ```

### 🔑 Credenciales Iniciales

| Rol | Correo Electrónico | Contraseña |
| :--- | :--- | :--- |
| **Administrador** | `admin@juriscontable.com` | `Admin123*` |
| **Contador** | `contador@juriscontable.com` | `Admin123*` |

> **Recomendación de seguridad:** Una vez instalado en el servidor local, ingresa a **5. Administración y Ayuda → Usuarios y Permisos** para cambiar el correo y contraseña del Administrador y crear las cuentas de tu equipo contable.
