---
name: pos-architecture
description: POS System Architecture, Conventions, Executive UI Components, and Frontend Standards. Always apply when developing, modifying, or reviewing modules in this POS application.
---

# POS System Architecture & Conventions Guide

## 🌟 Role & Profile: Senior Full Stack Frontend Engineer & UI/UX Design Specialist
Elite Senior Full Stack Engineer and Frontend UI/UX Specialist with advanced mastery in Laravel, Vue.js, Blade components, Bootstrap 5, Tailwind CSS, and enterprise-grade Single Page/Multi-Page Application architectures.
Every designed, written, reviewed, or refactored module MUST be **magnificent, highly aesthetic, clean, modern, executive, and production-ready**.

### 🎨 Mandatory UI/UX & Aesthetic Design Directives
1. **No Gray Containers Policy (MANDATORY)**: NEVER use `bg-light`, `alert-light`, `bg-light-subtle`, or dull gray panels for sub-cards, switch toggles, preview containers, or form sub-sections. Always use crisp, clean white containers with soft borders and subtle elevation: `bg-white shadow-xs border rounded-4` (or `rounded-3`).
2. **Rounded Borders**: Every UI element, card, button, modal, badge, input, and container MUST feature smooth, polished, rounded borders (e.g., Bootstrap `.rounded-4`, `.rounded-pill`, `.rounded-3`, or custom clean curves). No sharp square edges are allowed unless explicitly specified.
3. **Visual Hierarchy & Executive Feel**: Prioritize clean layouts, high contrast, proper whitespace, smooth micro-interactions, subtle shadows (`shadow-sm`, `shadow-hover`, `shadow-xs`), and modern color harmonies.
4. **Dark Mode & Palette Alignment**: Prefer sleek dark visual themes for code editors, previews, or specialized interfaces when requested, maintaining a pristine, professional enterprise aesthetic.
5. **Consistency**: Always leverage reusable design tokens, font weights (`fw-bold`, `fw-semibold`), and standard FontAwesome 6 icon sets according to the module domain.
6. **Modern Typography**: Use clean, modern fonts like **Plus Jakarta Sans** or **Inter** with legible weights (400 to 800) and avoid forced micro-uppercase in form labels.
7. **Flawless Code & Component Reuse (MANDATORY)**: Write clean, maintainable code. NEVER rewrite repetitive custom `$.ajax` calls or build ad-hoc dialogs. ALWAYS reuse the standard Blade components (`<x-modal>`, `<x-select2>`, `<x-section-header>`, `<x-table-dynamic>`) and JavaScript helpers (`enviarFormulario`, `cambiarEstadoRegistro`, `consultarRegistro`, `peticionAjax`, `crearSelect2`, `crearDataTable`).
8. **🚨 Strict Database Preservation Policy (MANDATORY)**: NEVER execute `php artisan migrate:fresh` or wipe/reset the database for routine/small changes, styling fixes, or standard tests. Always preserve the user's active session, configured companies, and registered data. You may ONLY run `migrate:fresh` when strictly unavoidable AND you have asked the user for explicit permission and received their confirmation ("pídeme permiso antes").
9. **📖 Mandatory Skill Consultation**: Always review and strictly follow this skill (`pos-architecture`) and `AGENTS.md` before executing any architectural decision or terminal command.
10. **🏢 Multi-Tenancy & Empresa Scoping (MANDATORY)**: All transactional and catalog entities (`Proveedores`, `Clientes`, `Productos`, `Ventas`, `Cajas`, etc.) MUST include `empresa_id`. All CRUD queries, unique validation rules (`Rule::unique`), and logical reactivations must be scoped to the active company `empresa_id` (`Auth::user()->empresaActiva()->id`).

---

## 1. System Bootstrap, Clean Database & Setup Wizard

### 1.1 Initial Setup Wizard (`/configuracion-inicial`)
- **Purpose**: When the system is fresh or unconfigured (no active SuperAdmin or Empresa exists), all web requests automatically redirect to `/configuracion-inicial`.
- **Middleware**: `App\Http\Middleware\VerificarConfiguracionInicial` appended to the `web` middleware group in `bootstrap/app.php`. Excludes static assets (`estilos/*`, `storage/*`, `build/*`, `favicon.ico`) and `configuracion-inicial*`.
- **Database Seeder Standard**:
  - `database/seeders/DatabaseSeeder.php` ONLY calls `RolesYPermisosSeeder::class`.
  - **NEVER** seed mock users or companies in seeders. The Empresa Matriz and root SuperAdmin are created dynamically during initial setup.
- **Setup Flow**:
  1. **Paso 1: Empresa Matriz** (Nombre comercial, razón social, RIF con selector `J-, V-, G-, E-`, teléfono, correo, dirección fiscal, logo y **Switch de Giro de Negocio / Módulo de Motos y Seriales Únicos `maneja_motos`**).
  2. **Paso 2: SuperAdministrador** (Cédula `name`, email, nombre, apellido, contraseña min 8 caracteres y confirmación).
  3. **Paso 3: Métodos de Pago** (Selección interactiva de formas de cobro iniciales).
  - Executed in a single atomic `DB::transaction`, auto-logging the SuperAdmin and setting `session(['empresa_activa_id' => $empresa->id])`.

### 1.2 Multi-Company Feature Flags (`maneja_motos`)
- Every company (`Empresa`) contains a `maneja_motos` boolean flag.
- **Standard Retail / Service Companies (`maneja_motos = false`)**: The sidebar completely hides all motorcycle/vehicle reception and catalog routes. The tenant operates as a clean, traditional POS.
- **Dealership / Motorcycle Companies (`maneja_motos = true`)**: The sidebar dynamically unveils **Compras ➔ Recepción de Motos** and **Inventario & Catálogo ➔ Motos & Seriales**.
- **Secondary Companies (`/empresas`)**: In the company creation and edit modal, administrators can toggle `maneja_motos` to tailor each company's specialized workflow.

### 1.3 Authentication & Login Standard
- **Identity / Username**: `User.name` holds the Cédula / Document ID (e.g., `V-12345678`).
- **Login Request**: Validates `name` and `password` with active status `estado = true`.
- **Password Rules**: Minimum 8 characters on installation/setup, min 6 characters on standard user management.

---

## 2. Controller & Form Request Architectural Standards

### 2.1 Mandatory Form Requests (NO INLINE VALIDATION IN CONTROLLERS)
- **NEVER use inline `$request->validate([...])` inside controller methods.**
- Every endpoint receiving payload data MUST use a dedicated `FormRequest` class under `app/Http/Requests/{Domain}/{Modulo}/` or `app/Http/Requests/{Modulo}/` (e.g. `CrearRequest.php`, `ActualizarRequest.php`, `AbonarFacturaRequest.php`, `AbonarGeneralRequest.php`).
- Controller methods must inject the strongly-typed `FormRequest` in the method signature and retrieve sanitized data using `$request->validated()` or `$request->validated('key')`. **Never pass raw `$request->all()` on validated endpoints**.

### 2.2 Controller Architecture & Dependency Injection Conventions
1. **Constructor Property Promotion**: Always type-hint service classes with `private` constructor property promotion using standard naming `${module}Class` (e.g., `private ProveedorClass $proveedorClass`, `private CuentaPorCobrarClass $cuentaPorCobrarClass`).
2. **Centralized Active Company Scoping (`App\Traits\HasEmpresaActiva`)**:
   - The base controller `App\Http\Controllers\Controller` uses `App\Traits\HasEmpresaActiva`.
   - **DO NOT redefine `obtenerEmpresaId()` in individual controllers**. It is inherited directly:
     - `$this->obtenerEmpresaId(): int` (resolves active company ID or aborts 403).
     - `$this->obtenerEmpresaActiva(): ?Empresa` (resolves active Empresa model instance).
3. **HTTP Responses**: Return consistent JSON payloads (`{ success: bool, message?: string, data?: mixed }`) and use proper status codes (e.g., 200, 403, 404, 422, 500).

### 2.3 Form Request Standards & BaseRequest Architecture
- **Base Form Request (`App\Http\Requests\BaseRequest`)**:
  - All company-scoped Form Requests must extend `App\Http\Requests\BaseRequest`.
  - Inherits `$this->empresaId()` and `$this->obtenerEmpresaId()` directly.
- **Rule Syntax Convention**: Always write validation rules using **array format** `['required', 'string', 'min:2', 'max:100']`. Never use pipe strings (avoid `required|string|max:100`).
- **Spanish Error Messages**: Always override `messages(): array` in every `FormRequest` to return clear, user-friendly Spanish error messages.
- **Tenant-Scoped Entities vs Global Catalogs**:
  - **Tenant Entities** (`proveedores`, `cuentas_por_cobrar`, `cuentas_por_pagar`, `productos`, `almacenes`, `ventas`, `kardex`, `motos`):
    `Rule::unique` and `Rule::exists` MUST include `where('empresa_id', $this->empresaId())` and `where('estado', true)`.
    ```php
    // In CrearRequest / AbonarRequest:
    'cuenta_id' => [
        'required',
        'integer',
        Rule::exists('cuentas_por_cobrar', 'id')->where(fn ($q) => $q->where('empresa_id', $this->empresaId())),
    ],
    'proveedor_id' => [
        'required',
        'integer',
        Rule::exists('proveedores', 'id')->where(fn ($q) => $q->where('empresa_id', $this->empresaId())->where('estado', true)),
    ],
    ```
  - **Global Catalogs** (`metodos_pago`, `clientes`):
    These tables do not contain an `empresa_id` column. Scope uniqueness and existence with `where('estado', true)` without querying `empresa_id`:
    ```php
    'metodo_pago_id' => [
        'required',
        'integer',
        Rule::exists('metodos_pago', 'id')->where(fn ($q) => $q->where('estado', true)),
    ],
    'cliente_id' => [
        'required',
        'integer',
        Rule::exists('clientes', 'id')->where(fn ($q) => $q->where('estado', true)),
    ],
    ```
- **Uniqueness & Reactivation Scoping**:
  Always scope uniqueness against active records only (`where estado = true`), so soft-deleted / inactive records can be seamlessly reactivated upon creation:
  ```php
  // In CrearRequest:
  'rif' => [
      'required',
      'string',
      'max:20',
      Rule::unique('proveedores', 'rif')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
  ],
  // In ActualizarRequest:
  'rif' => [
      'required',
      'string',
      'max:20',
      Rule::unique('proveedores', 'rif')
          ->ignore($this->route('id'))
          ->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
  ],
  ```

---

## 3. Service Layer & Logical Deletion / Reactivation

In service classes (`app/Service/{Dominio}/{Modulo}Class.php`):
1. **Active-Only Queries**: In `lista()`, ALWAYS filter by active records (`->where('estado', true)`). Inactive/soft-deleted records do not appear in the default list.
   ```php
   public function lista()
   {
       return Modelo::select('id', 'rif', 'nombre', ...)->where('estado', true);
   }
   ```
2. **`guardar(array $datos)`**: If a record with the same unique identifier (e.g., `rif`, `cedula`, `nombre`, `email`) exists in inactive state (`estado = false`), update its data with the new input and reactivate it to `estado = true`. Otherwise, create a new record:
   ```php
   public function guardar(array $datos)
   {
       $existenteInactivo = Modelo::where('rif', $datos['rif'])
           ->orWhere('nombre', $datos['nombre'])
           ->first();

       if ($existenteInactivo) {
           $datos['estado'] = true;
           $existenteInactivo->update($datos);
           return $existenteInactivo;
       }

       $datos['estado'] = true;
       return Modelo::create($datos);
   }
   ```
3. **`eliminar($id)`**: Performs soft-deletion by setting `estado = false`:
   ```php
   public function eliminar($id)
   {
       $registro = Modelo::findOrFail($id);
       $registro->estado = false;
       $registro->save();

       return $registro;
   }
   ```

---

## 4. Single Source of Truth (`config/pos.php`)

All POS constants (identity document prefixes, international country phone codes, currency formats) live in `config/pos.php`:
```php
return [
    'prefijos_cedula' => ['V-' => 'V-', 'J-' => 'J-', 'E-' => 'E-', 'G-' => 'G-', 'P-' => 'P-'],
    'codigos_pais' => [
        ['codigo' => '+58', 'pais' => 'Venezuela', 'bandera' => '🇻🇪'],
        ['codigo' => '+1', 'pais' => 'Estados Unidos / Canadá', 'bandera' => '🇺🇸'],
        ['codigo' => '+57', 'pais' => 'Colombia', 'bandera' => '🇨🇴'],
        // ...
    ],
];
```
This configuration is automatically synchronized to:
1. Blade components (`<x-input-documento>`, `<x-input-telefono>`).
2. Frontend JavaScript via `window.CONFIG_POS` (injected in `js-components.blade.php`).

---

## 5. UI Architecture: Card Grids vs DataTables

### 5.1 When to Use Executive Card Grids
- **Modules**: `Empresas`, `Usuarios / Administradores`, `Almacenes`.
- **Reasoning**: These entities are created in low/moderate volumes where visual depth, logo/avatar identity, status badges, authorized company pills, and granular permission counters create a far superior executive user experience.
- **Card Grid Architecture**:
  1. **Search Header Card**: Clean input group with live text filter and dynamic counter badge.
  2. **Responsive Grid**: `row g-4` with `.card-executive`, `.rounded-4`, soft shadows, hover elevation.
  3. **Visual Header**:
     - Large Squircle Logo / Avatar (`avatar-executive-md`).
     - Entity name in bold (`fw-bold text-dark`), Document / Cédula badge (`badge-documento`).
     - Role Badge (`SuperAdmin` gradient, `Admin` blue, `Operador` teal).
  4. **Card Body**: Contact chips (email, phone, address) and assigned company pills.
  5. **Card Footer**: Direct rounded action buttons (Editar, Eliminar/Desactivar).
  6. **Skeleton Loading**: Visual placeholder animation while AJAX loads.

### 5.2 When to Use DataTables
- **Modules**: `Clientes`, `Proveedores`, `Productos / Inventario`, `Ventas`, `Kardex`, `Categorías`, `Servicios`, `Métodos de Pago`.
- **Reasoning**: Massive volume of transactional records requiring fast pagination, column sorting, and server-side processing.

---

## 6. Granular Permissions Model (Spatie Laravel Permission)

### 6.1 Role Hierarchy & Granular Authorization
1. **SuperAdministrador (`SuperAdmin`)**:
   - Global root platform access across all companies and warehouses.
   - All permissions synced automatically.
   - No company assignment restriction (sees all companies via Navbar switcher).
2. **Administrador (`Admin`)**:
   - Assigned to 1 or more specific companies by the SuperAdmin.
   - Standard administrative permissions for their assigned companies.
3. **Operador (`Operador`)**:
   - Operates POS, cash register, customer transactions, and sales.
   - **Granular Module Permissions**: Allows administrators to selectively grant or revoke specific module permissions (`ver`, `crear`, `editar`, `eliminar`) for active modules.

### 6.2 Active Modules Permission Matrix
```php
'permisos_modulos' => [
    'Clientes' => [
        ['name' => 'clientes.ver', 'label' => 'Ver clientes y detalles'],
        ['name' => 'clientes.crear', 'label' => 'Registrar clientes'],
        ['name' => 'clientes.editar', 'label' => 'Editar información'],
        ['name' => 'clientes.eliminar', 'label' => 'Eliminar / Desactivar'],
    ],
    'Proveedores' => [
        ['name' => 'proveedores.ver', 'label' => 'Ver proveedores y detalles'],
        ['name' => 'proveedores.crear', 'label' => 'Registrar proveedores'],
        ['name' => 'proveedores.editar', 'label' => 'Editar información'],
        ['name' => 'proveedores.eliminar', 'label' => 'Eliminar / Desactivar'],
    ],
    'Métodos de Pago' => [
        ['name' => 'metodos_pago.ver', 'label' => 'Ver métodos de pago'],
        ['name' => 'metodos_pago.crear', 'label' => 'Crear nuevas formas de pago'],
        ['name' => 'metodos_pago.editar', 'label' => 'Modificar métodos'],
        ['name' => 'metodos_pago.eliminar', 'label' => 'Eliminar formas de pago'],
    ],
]
```

---

## 7. Reusable Blade Components & Exact DOM Selectors

### 7.1 Component Catalog

| Component | Description | Example Usage |
|---|---|---|
| `<x-datatable>` | Card container + table-responsive + dark header | `<x-datatable id="datatable_x" :headers="['Col1', 'Col2']" />` |
| `<x-btn-action>` | Gradient executive header button / link | `<x-btn-action icon="fas fa-plus" text="Nuevo" onclick="crear()" />` |
| `<x-input-documento>` | Identity / Cédula / RIF select + input + numeric sanitize | `<x-input-documento selectName="tipo_cedula" inputName="cedula_numero" required />` |
| `<x-input-telefono>` | Flag country code select + phone input | `<x-input-telefono selectName="codigo_pais" inputName="telefono_numero" />` |
| `<x-input>` | Standard executive input with icon, addons & prefix/suffix | `<x-input name="nombre" label="Nombre" icon="fas fa-user" addonText="$" required />` |
| `<x-select>` | Executive dropdown select | `<x-select name="tipo" label="Tipo" icon="fas fa-tag"><option>...</option></x-select>` |
| `<x-select2>` | Executive Select2 dropdown with search & modal-parent support | `<x-select2 name="categoria_id" label="Categoría" modalParent="#modalX" placeholder="Seleccione..."><option>...</option></x-select2>` |
| `<x-section-header>` | Executive banner header for subforms/sections (replaces dull alerts) | `<x-section-header title="Códigos" description="..." icon="fas fa-qrcode"><button>...</button></x-section-header>` |
| `<x-table-dynamic>` | Executive dynamic subform table with clean header & rounded borders | `<x-table-dynamic id="tablaX" bodyId="contenedorX" :headers="[['label' => 'Col1', 'width' => '50%']]" />` |
| `<x-modal>` | Executive modal with dynamic header, icon, subtitle & action buttons | `<x-modal id="modalX" title="Título" subtitle="..." submitText="Guardar">...</x-modal>` |
| `<x-search-filter>` | Executive live search & counter filter bar | `<x-search-filter inputId="buscador" counterId="contador" placeholder="Buscar..." />` |

### 7.2 Strict `<x-modal>` Generated DOM Selectors (MANDATORY)
When `<x-modal id="modalX">` is rendered, the component automatically generates the following child element IDs:
- **Title Element**: `#modalXTitulo` (e.g. `$('#modalEmpresaTitulo').text('Editar Empresa')`)
- **Subtitle Element**: `#modalXSubtitulo` (e.g. `$('#modalEmpresaSubtitulo').text('Modifique los campos...')`)
- **Header Icon**: `#modalXIcono` (e.g. `$('#modalEmpresaIcono').attr('class', 'fas fa-building')`)
- **Submit Button**: `#modalXBtnGuardar` (e.g. `$('#modalEmpresaBtnGuardar')`)
- **Submit Button Text**: `#modalXTextoGuardar` (e.g. `$('#modalEmpresaTextoGuardar').text('Guardar Cambios')`)

> [!CAUTION]
> **NEVER** invent ad-hoc selector names like `#modalXTituloTexto`, `#modalXHeaderTitle`, or `#btnGuardarX`. Always use the exact IDs defined above.

### 7.3 Native Bootstrap 5 Modal Management
Always open and close modals using the official Bootstrap 5 API:
```javascript
const modalEl = document.getElementById('modalX');
const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
modalInstance.show(); // or modalInstance.hide();
```

---

## 8. Frontend JavaScript Standards & Mandatory Component Reuse

### 8.1 Code Cleanliness & Strict Zero-Comments Policy
- **Zero Comments in JS (MANDATORY)**: All JavaScript module files under `public/estilos/jsPropios/` MUST BE 100% comment-free (NO `//` or `/* */`). Code must be self-explanatory with clean, descriptive variable and function names.
- **No Artificial Global Window Pollution**: Do NOT add `window.xxx = xxx` assignments inside module JS files. Declare functions locally with `const funcion = function () { ... }` or `const funcion = async function () { ... }`.
- **Global Toast Notification**: `toast.blade.php` exposes `window.notificacion = notificacion;` globally so that `formulario.component.js` and all helpers trigger SweetAlert2 toasts seamlessly.

### 8.2 Mandatory Reuse of Frontend Helper Functions
**NEVER write repetitive raw `$.ajax` calls for standard operations.** Always use the established helpers:

1. **`enviarFormulario(opciones)` (Submitting Forms / AJAX POST & PUT)**:
   ```javascript
   enviarFormulario({
       formId: 'formX',
       url: urlGuardar,
       esActualizacion: Boolean(id),
       urlActualizar: urlActualizar,
       modalId: 'modalX',
       tablaId: 'datatable_x', // or funcionActualizar: cargarCards
       antesDeEnviar: function (formData) {
           // Optional: custom mutations or appending extra fields to formData
           return formData;
       },
       onSuccess: function (res) {
           // Optional post-success logic
       }
   });
   ```
2. **`cambiarEstadoRegistro(opciones)` (Soft Deleting / Deactivating Records)**:
   ```javascript
   cambiarEstadoRegistro({
       url: urlEliminar,
       id: id,
       tablaId: 'datatable_x', // or funcionActualizar: cargarCards
       titulo: '¿Está seguro de eliminar este registro?',
       texto: 'El registro quedará inactivo en el sistema.'
   });
   ```
3. **`consultarRegistro(urlDetalles, id)` (Fetching Single Record Data)**:
   ```javascript
   const res = await consultarRegistro(urlDetalles, id);
   if (res && res.success && res.data) {
       const datos = res.data;
       // Populate form fields...
   }
   ```
4. **`peticionAjax(opciones)` (Generic GET / POST / PUT / DELETE Calls)**:
   ```javascript
   const res = await peticionAjax({
       url: urlEndpoint,
       type: 'POST',
       data: payload,
       mensajeCarga: 'Procesando información...'
   });
   ```
5. **Select2 Helpers (`select2.component.js`)**:
   - `crearSelect2({ selector: '#selectX', modalParent: '#modalX', placeholder: 'Seleccione...' })`
   - `limpiarSelect2('#selectX')`
   - `establecerValorSelect2('#selectX', valor)`
   - `destruirSelect2('#selectX')`
6. **DataTable Helper (`datatable.component.js`)**:
   - `crearDataTable({ tablaId: 'datatable_x', url: urlLista, columnas: [...] })`
7. **Document & Phone Utilities**:
   - `desglosarCedula(cedula)` / `desglosarTelefono(telefono)`
   - `aplicarRestriccionesInput()`

---

## 9. Standard FontAwesome 6 Icons

- `fas fa-users` / `fas fa-user-plus` / `fas fa-user-edit` / `fas fa-user-shield` / `fas fa-user-tag` (Usuarios / Clientes)
- `fas fa-building` / `fas fa-store` / `fas fa-landmark` (Empresas / Sedes)
- `fas fa-truck-moving` / `fas fa-dolly` (Proveedores / Recepción)
- `fas fa-credit-card` / `fas fa-money-bill-wave` / `fas fa-dollar-sign` (Métodos de Pago)
- `fas fa-boxes-stacked` / `fas fa-warehouse` (Inventario)
- `fas fa-cash-register` / `fas fa-receipt` (Punto de Venta / Facturación)
- `fas fa-chart-line` / `fas fa-sliders-h` (Reportes / Configuración)

---

## 10. Step-by-Step Implementation Roadmap

1. **Fase 1 - Fundaciones Multi-Empresa & Asistente de Instalación**:
   - [x] Módulo de Empresas en Cards ejecutivas (Superadmin).
   - [x] Asistente de Configuración Inicial (`/configuracion-inicial`) y Middleware de redirección.
   - [x] Módulo de Almacenes en Cards ejecutivas (Asignación por Empresa).
   - [x] Módulo de Configuración de Empresa (`/configuracion`): Panel multimoneda de tasas de cambio (USD $, EUR €, COP $ vs VES Bs.) y datos de empresa.
2. **Fase 2 - Seguridad, Roles & Permisos Granulares (Spatie)**:
   - [x] Instalación y Migraciones de Spatie Permission.
   - [x] Tabla pivote `empresa_user` y relaciones Eloquent.
   - [x] Seeder de Roles (`SuperAdmin`, `Admin`, `Operador`) y Matriz de Permisos.
   - [x] Módulo de Usuarios en Cards Ejecutivas con asignación granular de permisos para Operadores.
   - [x] Selector de Empresa Activa en el Navbar (con `session('empresa_activa_id')`).
   - [ ] Módulo de Auditoría y Logs de Actividad.
3. **Fase 3 - Catálogos de Artículos, Clientes, Proveedores & Métodos de Pago**:
   - [x] **Módulo de Clientes** (DataTable + Modal Ejecutivo con documento/teléfono/tipo de cliente).
   - [x] **Módulo de Proveedores** (DataTable + Modal Ejecutivo + Multi-tenancy por empresa_id).
   - [x] **Módulo de Métodos de Pago** (DataTable + Modal Ejecutivo).
   - [x] **Módulo de Categorías de Productos** (DataTable + Modal Ejecutivo con Ver, Editar y Desactivar).
   - [x] **Módulo de Servicios** (Módulo independiente: sin stock, sin almacenes, cálculo bidireccional USD <-> Bs. según tasa activa).
   - [x] **Módulo de Productos** (Catálogo con Códigos de Barra múltiples, Vinculación de Proveedores, IVA/IGTF, Stock por Almacén y Ficha Técnica 360°).
4. **Fase 4 - Compras, Recepción de Mercancía & Kardex**:
   - [x] Recepción de Mercancía / Compras con Proveedores (asignación de costos, precios mayorista/detal, tasas compra/venta independientes y entrada de stock).
   - [x] Motor de Kardex multi-almacén (entradas, salidas, devoluciones).
   - [x] Comprobante de Recepción imprimible térmico/carta.
5. **Fase 5 - Módulo Especializado de Motos / Vehículos**:
   - [x] Catálogo y Recepción por lotes de Motos con seriales únicos (NIV, Chasis, Motor, Certificado de Origen, Placa, Color, Cilindrada).
   - [x] Feature flag modular por empresa (`maneja_motos`) con ocultamiento limpio en Sidebar para comercios tradicionales.
6. **Fase 6 - Punto de Venta (POS) & Facturación**:
   - [x] Interfaz POS rápida y táctil para Productos, Servicios y Motos.
   - [x] Búsqueda predictiva por nombre, código interno, códigos de barra y seriales de motos.
   - [x] Pagos mixtos / multimoneda con Métodos de Pago (Efectivo USD/VES, Zelle, Tarjeta, Pago Móvil, Crédito).
   - [x] Ventas en Espera (Carritos suspendidos) y Devoluciones con reversión a Kardex/Stock.
   - [x] Ticket de Venta térmico (80mm) con desglose fiscal y tasas.
7. **Fase 7 - Créditos & Finanzas (CXC & CXP)**:
   - [x] Gestión de Cuentas por Cobrar (CXC) de clientes y registro de abonos parciales/totales.
   - [x] Gestión de Cuentas por Pagar (CXP) a proveedores y registro de abonos.
   - [x] Emisión de comprobantes térmicos de abono con saldo pendiente dinámico en USD y Bs.

---

## 11. Global System Interconnection & Operational Blueprint

This section defines the end-to-end operational flow and relational contract between all system modules for future development and multi-agent alignment.

### 11.1 Entity-Relationship & Modular Flow Matrix

```
[EMPRESAS] (Multi-Tenancy Root)
   │
   ├── [USUARIOS & ROLES] (Spatie Permissions + empresa_user pivot)
   │
   ├── [ALMACENES] (Bodegas, Depósitos, Piso de Venta / Mostrador)
   │        │
   │        ▼
   ├── [CATEGORIAS] ──────┐ (1 a N)
   │                      ▼
   ├── [PROVEEDORES] ─── [PRODUCTO_PROVEEDORES] ◄──┐
   │                                                │
   ├── [PRODUCTOS & SERVICIOS] ─────────────────────┤
   │        │  ├── [PRODUCTO_CODIGOS_BARRA] (N por producto)
   │        │  ├── [PRODUCTO_STOCK_ALMACENES] (Stock por almacén)
   │        │  └── Fiscal: IVA (16%/0%) + IGTF (3% divisas)
   │        │
   │        ├──► [RECEPCIONES / COMPRAS] (Entrada ➔ Stock Almacén Central + Nuevos CB + Kardex)
   │        │
   │        ├──► [TRASLADOS] (Movimiento Atómico: Bodega ➔ Piso de Venta + Doble Asiento Kardex)
   │        │
   │        ├──► [PUNTO DE VENTA (POS) / FACTURAS] (Caja en Piso de Venta ➔ Descuento Stock + Kardex)
   │        │
   │        └──► [KARDEX] (Libro Mayor Inmutable de Movimientos de Inventario)
   │
   ├── [CAJAS & TURNOS] (Aperturas, Arqueos, Cierres vinculados a Almacén Piso de Venta)
   │
   ├── [METODOS DE PAGO] (Efectivo, Transferencia, Pago Móvil, Zelle, Divisas USD/EUR)
   │
   ├── [CLIENTES] (Detal / Mayorista para ventas y créditos)
   │
   └── [CREDITOS & FINANZAS] (CXC Clientes y CXP Proveedores)
```

### 11.2 Module-by-Module Operational Contracts

1. **Empresas (`empresas`)**:
   - Master entity for multi-tenancy. Every catalog and transaction is scoped via `empresa_id`.
2. **Usuarios & Permisos (`users`, `roles`, `permissions`, `empresa_user`)**:
   - SuperAdmin (global), Admin (per-company), Operador (granular permissions per module: `ver`, `crear`, `editar`, `eliminar`).
3. **Almacenes (`almacenes`)**:
   - Physical locations (`Bodega Central`, `Piso de Venta / Mostrador`, `Depósito Secundario`).
   - Essential foundation for multi-warehouse stock, reception, and transfers.
4. **Categorías (`categorias`)**:
   - Single categorization per product (`categoria_id`).
5. **Proveedores (`proveedores`)**:
   - Suppliers linked to products via `producto_proveedores` with supplier SKU and last purchase cost.
6. **Productos & Servicios (`productos`)**:
   - **Type**: `producto` (physical, trackable in stock) vs `servicio` (intangible, billed without physical stock).
   - **Units of Measure**: `unidad`, `kilo`, `gramo`, `litro`, `bulto`, `caja`, `paquete`, `metro`.
   - **Multiple Barcodes (`producto_codigos_barra`)**: Multiple scannable EAN/UPC barcodes per product (individual pack, box x12, supplier barcode).
   - **Fiscal Configuration**:
     - `aplica_iva` (boolean) + `iva_porcentaje` (e.g., `16.00`, `8.00`, `0.00` exento).
     - `aplica_igtf` (boolean) + `igtf_porcentaje` (default `3.00`% for foreign currency payments).
   - **Stock per Warehouse (`producto_stock_almacenes`)**: Real-time balance and shelf location per warehouse.
   - **Pricing**: Cost base, Price Detal, Price Mayorista.
7. **Recepción de Mercancía / Compras (General)**:
   - Ingests physical products into designated warehouses with a 2-phase stepper workflow (Fase 1: Cabecera fiscal, proveedor y condición; Fase 2: Renglones, costos, márgenes y resumen).
   - Bi-monetary support ($ USD y Bs. VES con tasa oficial BCV congelada al momento de la compra).
   - Dynamic memory: preserves last applied profit margins (`ultimo_margen_detal`, `ultimo_margen_mayorista`) and automatically links the supplier to the product (`producto_proveedores`).
   - Generates physical printable voucher sheets (`/recepciones/{id}/imprimir`) and atomic Kardex entries (`entrada_recepcion`).
8. **Recepción & Control de Motos / Vehículos por Lotes y Seriales Únicos**:
   - Specialized module for vehicle reception with strict individual serial tracking per unit.
   - **Vehicular Attributes**: Marca, Modelo, Año, Color, Cilindrada (CC), Referencia (Ref).
   - **Unique Fiscal & Legal Identifiers per Unit**:
     - **N.I.V. / V.I.N.** (Número de Identificación Vehicular - 17 caracteres alfanuméricos únicos).
     - **Número de Chasis** (Serial de carrocería/bastidor).
     - **Número de Motor** (Serial de motor grabado).
     - **Número de Certificado de Origen** (Título legal/fiscal de importación o ensamblaje).
     - **Placa** (Asignada o en trámite).
   - **Batch Entry Flow**: Capability to define the base vehicle model & prices once, and rapidly scan/input multiple serial sets for batch shipments.
   - **Unit Lifecycle Status**: `disponible` (en inventario), `reservada` (en proceso de venta/crédito), `vendida` (facturada al cliente con acta de entrega), `en_mantenimiento` (taller/garantía).
9. **Traslados Internos**:
   - Moves physical inventory or serialized vehicles between warehouses (e.g., Bodega Central ➔ Piso de Venta).
   - Atomic transaction with double Kardex entry (`Salida por Traslado` and `Entrada por Traslado`).
10. **Cajas & Turnos**:
    - Physical cash registers tied to a specific warehouse (usually *Piso de Venta*).
    - Handles session openings, live reconciliations (arqueos), and closures.
11. **Punto de Venta (POS) & Facturación**:
    - Fast barcode scanner search matching ANY registered barcode of the product or vehicle serial.
    - Automatic deduction from the cash register's assigned warehouse (*Piso de Venta*).
    - Real-time tax calculation: Net Subtotal, IVA (16%), IGTF (3% on cash/foreign currency), and Total.
    - Multi-payment support (Mixed payments using Métodos de Pago).
    - Direct Kardex logging (`Salida por Venta`).
12. **Devoluciones & Anulaciones**:
    - Invoice voiding re-credits stock/serial to the originating warehouse and records `Entrada por Anulación` in Kardex.
13. **Ajustes de Inventario**:
    - Audit count discrepancies logged to Kardex as `Ajuste de Inventario` (Mermas / Sobrantes).

---

## 12. Standard Module Refactoring & Verification Protocol (MANDATORY)

Every module review, refactoring, or creation must strictly adhere to the following 3-step workflow:

### Step 1: Pre-Execution Diagnosis & User Alignment
1. **Deep Code Inspection**:
   - Blade View: Inspect container classes, check for prohibited gray backgrounds (`bg-light`, `alert-light`), verify `<x-modal>` configuration and input components.
   - JavaScript Module: Identify manual `$.ajax` calls, check `<x-modal>` generated child element selectors (`#modalX{Titulo,Subtitulo,Icono,BtnGuardar,TextoGuardar}`), verify comments presence (must be 0), and check for unnecessary `window.xxx` assignments.
   - Backend: Inspect Controller, FormRequests, and Service classes for Multi-Tenancy scoping (`empresa_id`) and absence of inline validation.
2. **User Alignment**:
   - Always formulate a concise diagnostic summary to the user before making large destructive changes, and ask for confirmation when appropriate.

### Step 2: Code Standard Refactoring
1. **Blade Architecture**:
   - Replace any gray backgrounds with `bg-white shadow-xs border rounded-4` (or `rounded-3`).
   - Ensure all subforms use `<x-section-header>` and `<x-table-dynamic>`.
   - Use `<x-input-documento>`, `<x-input-telefono>`, and `<x-select2>` where applicable.
2. **JavaScript Architecture**:
   - Replace raw form submissions with `enviarFormulario({ formId, url, modalId, tablaId/funcionActualizar, antesDeEnviar })`.
   - Replace soft-delete/deactivation calls with `cambiarEstadoRegistro({ url, id, tablaId/funcionActualizar })`.
   - Replace single record fetches with `const res = await consultarRegistro(urlDetalles, id)`.
   - Replace miscellaneous AJAX calls with `peticionAjax({ url, type, data, mensajeCarga })`.
   - Use native `bootstrap.Modal.getOrCreateInstance(document.getElementById('modalX'))`.
   - Strip **all** comments (100% comment-free JS).
3. **Backend Architecture**:
   - Ensure all validation lives inside dedicated `FormRequest` classes extending `BaseRequest`.
   - Ensure company scoping via `$this->obtenerEmpresaId()` / `$this->empresaId()`.

### Step 3: Automated Verification & Formatting
1. **Pint Code Formatting**:
   - Run `vendor/bin/pint --format agent` on modified PHP files.
2. **Feature & Unit Testing**:
   - Run targeted tests via `php artisan test --filter={ModuleName}Test`.
   - Ensure all assertions pass with 100% green status.


