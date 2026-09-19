---
name: pos-architecture
description: POS System Architecture, Conventions, Executive UI Components, and Frontend Standards. Always apply when developing, modifying, or reviewing modules in this POS application.
---

# POS System Architecture & Conventions Guide

## 🌟 Role & Profile: Senior Full Stack Frontend Engineer & UI/UX Design Specialist
Elite Senior Full Stack Engineer and Frontend UI/UX Specialist with advanced mastery in Laravel, Vue.js, Blade components, Bootstrap 5, Tailwind CSS, and enterprise-grade Single Page/Multi-Page Application architectures.
Every designed, written, reviewed, or refactored module MUST be **magnificent, highly aesthetic, clean, modern, executive, and production-ready**.

### 🎨 Mandatory UI/UX & Aesthetic Design Directives
1. **Rounded Borders**: Every UI element, card, button, modal, badge, input, and container MUST feature smooth, polished, rounded borders (e.g., Bootstrap `.rounded-4`, `.rounded-pill`, `.rounded-3`, or custom clean curves). No sharp square edges are allowed unless explicitly specified.
2. **Visual Hierarchy & Executive Feel**: Prioritize clean layouts, high contrast, proper whitespace, smooth micro-interactions, subtle shadows (`shadow-sm`, `shadow-hover`), and modern color harmonies.
3. **Dark Mode & Palette Alignment**: Prefer sleek dark visual themes for code editors, previews, or specialized interfaces when requested, maintaining a pristine, professional enterprise aesthetic.
4. **Consistency**: Always leverage reusable design tokens, font weights (`fw-bold`, `fw-semibold`), and standard FontAwesome 6 icon sets according to the module domain.
5. **Modern Typography**: Use clean, modern fonts like **Plus Jakarta Sans** or **Inter** with legible weights (400 to 800) and avoid forced micro-uppercase in form labels.
6. **Flawless Code & Execution**: Write clean, maintainable code following established directory conventions, single source of truth (`config/pos.php`), and reusable JavaScript helpers (`enviarFormulario`, `crearDataTable`, `consultarRegistro`).
7. **🚨 Strict Database Preservation Policy (MANDATORY)**: NEVER execute `php artisan migrate:fresh` or wipe/reset the database for routine/small changes, styling fixes, or standard tests. Always preserve the user's active session, configured companies, and registered data. You may ONLY run `migrate:fresh` when strictly unavoidable AND you have asked the user for explicit permission and received their confirmation ("pídeme permiso antes").
8. **📖 Mandatory Skill Consultation**: Always review and strictly follow this skill (`pos-architecture`) and `AGENTS.md` before executing any architectural decision or terminal command.
9. **🏢 Multi-Tenancy & Empresa Scoping (MANDATORY)**: All transactional and catalog entities (`Proveedores`, `Clientes`, `Productos`, `Ventas`, `Cajas`, etc.) MUST include `empresa_id`. All CRUD queries, unique validation rules (`Rule::unique`), and logical reactivations must be scoped to the active company `empresa_id` (`Auth::user()->empresaActiva()->id`).

---

## 1. System Bootstrap, Clean Database & Setup Wizard

### 1.1 Initial Setup Wizard (`/configuracion-inicial`)
- **Purpose**: When the system is fresh or unconfigured (no active SuperAdmin or Empresa exists), all web requests automatically redirect to `/configuracion-inicial`.
- **Middleware**: `App\Http\Middleware\VerificarConfiguracionInicial` appended to the `web` middleware group in `bootstrap/app.php`. Excludes static assets (`estilos/*`, `storage/*`, `build/*`, `favicon.ico`) and `configuracion-inicial*`.
- **Database Seeder Standard**:
  - `database/seeders/DatabaseSeeder.php` ONLY calls `RolesYPermisosSeeder::class`.
  - **NEVER** seed mock users or companies in seeders. The Empresa Matriz and root SuperAdmin are created dynamically during initial setup.
- **Setup Flow**:
  1. **Paso 1: Empresa Matriz** (Nombre comercial, razón social, RIF con selector `J-, V-, G-, E-`, teléfono, correo, dirección fiscal y logo).
  2. **Paso 2: SuperAdministrador** (Cédula `name`, email, nombre, apellido, contraseña min 8 caracteres y confirmación).
  3. **Paso 3: Métodos de Pago** (Selección interactiva de formas de cobro iniciales).
  - Executed in a single atomic `DB::transaction`, auto-logging the SuperAdmin and setting `session(['empresa_activa_id' => $empresa->id])`.

### 1.2 Authentication & Login Standard
- **Identity / Username**: `User.name` holds the Cédula / Document ID (e.g., `V-12345678`).
- **Login Request**: Validates `name` and `password` with active status `estado = true`.
- **Password Rules**: Minimum 8 characters on installation/setup, min 6 characters on standard user management.

---

## 2. Directory & Form Request Conventions

For every module (e.g. `Cliente`, `Proveedor`, `Usuario`, `Empresa`, `MetodoPago`, `Producto`):
- **Form Requests**: Stored in `app/Http/Requests/{Modulo}/`
  - `CrearRequest.php`: Handles validation for creating records.
  - `ActualizarRequest.php`: Handles validation for updating records.
  - **Rule Syntax Convention**: Always write validation rules using **array format** `['required', 'string', 'min:2', 'max:100']`. Never use pipe strings (avoid `required|string|max:100`).
  - **Uniqueness & Reactivation Rule**: Always scope uniqueness against active records only (`where estado = true`), so soft-deleted / inactive records can be seamlessly reactivated upon creation:
    ```php
    use Illuminate\Validation\Rule;

    // In CrearRequest:
    'rif' => [
        'required',
        'string',
        'max:20',
        Rule::unique('proveedores', 'rif')->where(fn ($q) => $q->where('estado', true)),
    ],
    'nombre' => [
        'required',
        'string',
        'min:2',
        'max:150',
        Rule::unique('proveedores', 'nombre')->where(fn ($q) => $q->where('estado', true)),
    ],
    'razon_social' => [
        'required',
        'string',
        'min:2',
        'max:150',
        Rule::unique('proveedores', 'razon_social')->where(fn ($q) => $q->where('estado', true)),
    ],
    'nombre_contacto' => ['nullable', 'string', 'max:100'],
    'telefono' => ['nullable', 'string', 'max:25'],
    'correo' => ['nullable', 'email', 'max:150'],
    'direccion' => ['nullable', 'string', 'max:255'],

    // In ActualizarRequest:
    'rif' => [
        'required',
        'string',
        'max:20',
        Rule::unique('proveedores', 'rif')
            ->ignore($this->route('id'))
            ->where(fn ($q) => $q->where('estado', true)),
    ],
    'nombre' => [
        'required',
        'string',
        'min:2',
        'max:150',
        Rule::unique('proveedores', 'nombre')
            ->ignore($this->route('id'))
            ->where(fn ($q) => $q->where('estado', true)),
    ],
    'razon_social' => [
        'required',
        'string',
        'min:2',
        'max:150',
        Rule::unique('proveedores', 'razon_social')
            ->ignore($this->route('id'))
            ->where(fn ($q) => $q->where('estado', true)),
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
- **Modules**: `Empresas`, `Usuarios / Administradores`.
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
- **Modules**: `Clientes`, `Proveedores`, `Productos / Inventario`, `Ventas`, `Kardex`.
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

## 7. Reusable Blade Components Summary

| Component | Description | Example Usage |
|---|---|---|
| `<x-datatable>` | Card container + table-responsive + dark header | `<x-datatable id="datatable_x" :headers="['Col1', 'Col2']" />` |
| `<x-btn-action>` | Gradient executive header button / link | `<x-btn-action icon="fas fa-plus" text="Nuevo" onclick="crear()" />` |
| `<x-input-documento>` | Identity / Cédula / RIF select + input + numeric sanitize | `<x-input-documento selectName="tipo_cedula" inputName="cedula_numero" required />` |
| `<x-input-telefono>` | Flag country code select + phone input | `<x-input-telefono selectName="codigo_pais" inputName="telefono_numero" />` |
| `<x-input>` | Standard executive input with icon | `<x-input name="nombre" label="Nombre" icon="fas fa-user" required />` |
| `<x-select>` | Executive dropdown select | `<x-select name="tipo" label="Tipo" icon="fas fa-tag"><option>...</option></x-select>` |
| `<x-modal>` | Modal with dynamic header, cancel & submit buttons | `<x-modal id="modalX" title="Título" submitText="Guardar">...</x-modal>` |
| `<x-search-filter>` | Executive live search & counter filter bar | `<x-search-filter inputId="buscador" counterId="contador" placeholder="Buscar..." />` |

---

## 8. Frontend JavaScript Reusable Helpers

All module JS files consume standard components from `public/estilos/jsPropios/components/`:
1. **`crearDataTable(opciones)`**: Standard DataTables initializer.
2. **`consultarRegistro(urlDetalles, id)`**: Fetches record data via AJAX GET.
3. **`enviarFormulario(opciones)`**: Handles `FormData`, method `PUT`, spinner states, SweetAlert2 notifications, modal closing, table/card reloading, and 422 error highlighting.
4. **`cambiarEstadoRegistro(opciones)`**: SweetAlert2 confirmation dialog with DELETE request and live view reload.
5. **`desglosarCedula(cedula)` & `desglosarTelefono(telefono)`**: Extracts prefix and clean numbers.
6. **`aplicarRestriccionesInput()`**: Automatically sanitizes input lengths and patterns in real-time.

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
2. **Fase 2 - Seguridad, Roles & Permisos Granulares (Spatie)**:
   - [x] Instalación y Migraciones de Spatie Permission.
   - [x] Tabla pivote `empresa_user` y relaciones Eloquent.
   - [x] Seeder de Roles (`SuperAdmin`, `Admin`, `Operador`) y Matriz de Permisos.
   - [x] Módulo de Usuarios en Cards Ejecutivas con asignación granular de permisos para Operadores.
   - [x] Selector de Empresa Activa en el Navbar (con `session('empresa_activa_id')`).
   - [ ] Módulo de Auditoría y Logs de Actividad.
3. **Fase 3 - Catálogos de Artículos & Configuración de Stock**:
   - [ ] **Módulo de Categorías de Productos** (En Planificación).
   - [ ] **Módulo de Marcas**.
   - [ ] **Módulo de Productos & Servicios** (Control de precios Detal/Mayorista, código de barras, stock mínimo).
   - [ ] Stock por Almacén y Ajustes de Inventario.
4. **Fase 4 - Compras, Recepción de Mercancía, Kardex & Traslados**:
   - Recepción de Mercancía / Compras con Proveedores.
   - Motor de Kardex multi-almacén (entradas, salidas, ajustes).
   - Módulo de Traslados entre almacenes.
5. **Fase 5 - Cajas & Turnos**:
   - Cajas físicas por almacén.
   - Apertura, Arqueo en vivo, Cierre de Caja y Movimientos de Caja menor.
6. **Fase 6 - Punto de Venta (POS) & Facturación**:
   - Interfaz POS rápida y táctil.
   - Pagos mixtos / multimoneda con Métodos de Pago.
   - Historial de Facturas y Anulaciones con reversión a Kardex/Caja.
7. **Fase 7 - Créditos & Finanzas (CXC & CXP)**:
   - Gestión de Cuentas por Cobrar (CXC) y Abonos de Clientes.
   - Gestión de Cuentas por Pagar (CXP) y Pagos a Proveedores.
