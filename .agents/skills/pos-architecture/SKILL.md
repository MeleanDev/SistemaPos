---
name: pos-architecture
description: POS System Architecture, Conventions, Executive UI Components, and Frontend Standards. Always apply when developing, modifying, or reviewing modules in this POS application.
---

# POS System Architecture & Conventions Guide

This skill outlines the strict architecture, backend logic, and frontend UI/UX standards required for all modules within the POS system.

---

## 1. Directory & Request Conventions

For every module (e.g. `Cliente`, `Proveedor`, `Repuesto`, `Vehiculo`, `Producto`):
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

## 2. Service Layer & Logical Deletion / Reactivation

In service classes (`app/Service/Empresa/{Modulo}Class.php`):
1. **Active-Only DataTables Queries**: In `lista()`, ALWAYS filter by active records (`->where('estado', true)`). Inactive/soft-deleted records do not appear in the table.
   ```php
   public function lista()
   {
       return Modelo::select('id', 'rif', 'nombre', ...)->where('estado', true);
   }
   ```
2. **`guardar(array $datos)`**: If a record with the same unique identifier (e.g., `rif`, `cedula`, `nombre`) exists in inactive state (`estado = false`), update its data with the new input and reactivate it to `estado = true`. Otherwise, create a new record:
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
4. **Clean Tables (No Redundant "Estado" Column)**: Since all visible records in the table are active (`estado = true`), do not include an "Estado" column in the main DataTable to keep the table clean and spacious.

---

## 3. Controllers & Routes

- **Controllers**: Located in `app/Http/Controllers/Empresa/` (or domain subfolder).
  - Injected with service class (e.g., `ClienteClass`).
  - Returns `JsonResponse` with `{ success: true|false, message: string, data: mixed }`.
  - Returns DataTables JSON via `datatables()->of($query)->filter(...)->toJson()` for `lista()`.
- **Routes (`routes/admin.php`)**:
  ```php
  Route::controller(ModuloController::class)->group(function () {
      Route::get('/modulos', 'index')->name('modulo');
      Route::get('/modulos/lista', 'lista');
      Route::get('/modulos/{id}', 'detalle');
      Route::post('/modulos', 'guardar');
      Route::put('/modulos/actualizar/{id}', 'actualizar');
      Route::delete('/modulos/{id}', 'eliminar');
  });
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

## 5. Executive UI Components & View Structure

Every module view uses this concise, clean Blade layout:

```blade
@extends('Sistema.layouts.app')

@section('titulo', '👥 Clientes')
@section('subtitulo', 'Directorio y administración de clientes para compras al detal y mayoristas')

@section('rutas')
    <a href="{{ route('cliente') }}">Empresa</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Clientes</span>
@endsection

@section('acciones')
    <x-btn-action
        icon="fas fa-user-plus"
        text="Nuevo Cliente"
        onclick="crear()"
    />
@endsection

@section('contenido')
    <!-- Datatable Component -->
    <x-datatable
        id="datatable_clientes"
        :headers="[
            'Cliente / Documento',
            'Teléfono',
            'Correo',
            'Tipo Cliente',
            'Estado',
            'Acciones',
        ]"
    />

    <!-- Executive Modal Component -->
    <x-modal
        id="modalCliente"
        title="Nuevo Cliente"
        subtitle="Completa la información del cliente"
        icon="fas fa-user text-warning fs-5"
        size="modal-lg"
        headerColor="bg-dark text-white"
        formId="formularioCliente"
        submitText="Guardar"
    >
        <form id="formularioCliente">
            @csrf
            <div class="row g-3">
                <x-input name="nombre" label="Nombre / Razón Comercial" icon="fas fa-user" placeholder="Ej. Juan" required maxlength="100" col="col-md-6" />
                <x-input name="apellido" label="Apellido" icon="fas fa-user" placeholder="Ej. Pérez" required maxlength="100" col="col-md-6" />

                <!-- Modular Document & Phone Inputs -->
                <x-input-documento selectName="tipo_cedula" inputName="cedula_numero" required col="col-md-6" />
                <x-input-telefono selectName="codigo_pais" inputName="telefono_numero" col="col-md-6" />

                <x-select name="tipo_cliente" id="tipo_cliente" label="Tipo de Cliente" icon="fas fa-tag" required col="col-md-6">
                    <option value="detal">Detal / Particular</option>
                    <option value="mayorista">Mayorista / Empresa</option>
                </x-select>

                <x-input name="correo" id="correo" type="email" label="Correo Electrónico" icon="fas fa-envelope" placeholder="cliente@ejemplo.com" maxlength="150" col="col-md-6" optionalText="Opcional" />
                <x-input name="direccion" id="direccion" label="Dirección de Habitación / Fiscal" icon="fas fa-map-marker-alt" placeholder="Calle 123, Sector, Casa/Apto 456" maxlength="255" col="col-12" optionalText="Opcional" />
            </div>
        </form>
    </x-modal>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/cliente.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/cliente.js')) ?: time() }}"></script>
@endsection
```

### 5.1 Reusable Blade Components Summary

| Component | Description | Example Usage |
|---|---|---|
| `<x-datatable>` | Card container + table-responsive + dark header | `<x-datatable id="datatable_x" :headers="['Col1', 'Col2']" />` |
| `<x-btn-action>` | Gradient executive header button / link | `<x-btn-action icon="fas fa-plus" text="Nuevo" onclick="crear()" />` |
| `<x-input-documento>` | Identity / Cédula / RIF select + input + numeric sanitize | `<x-input-documento selectName="tipo_cedula" inputName="cedula_numero" required />` |
| `<x-input-telefono>` | Flag country code select + phone input | `<x-input-telefono selectName="codigo_pais" inputName="telefono_numero" />` |
| `<x-input>` | Standard executive input with icon | `<x-input name="nombre" label="Nombre" icon="fas fa-user" required />` |
| `<x-select>` | Executive dropdown select | `<x-select name="tipo" label="Tipo" icon="fas fa-tag"><option>...</option></x-select>` |
| `<x-modal>` | Modal with dynamic header, cancel & submit buttons | `<x-modal id="modalX" title="Título" submitText="Guardar">...</x-modal>` |

---

## 6. Standard Table Columns & Badges

1. **Dark Floating Header**: `<thead class="bg-dark text-white">` with rounded corners.
2. **Unified Column 1 (`Cliente / Documento` or `Producto / Código`)**:
   - Top: Full Name / Description in bold (`fw-bold text-dark`).
   - Avatar circle with user initials (`.avatar-executive-sm`).
   - Bottom: Monospace identity badge (`.badge-documento`).
3. **Interactive Contact Chips**:
   - Phone: `<a href="tel:${data}" class="contacto-item phone"><i class="fas fa-phone-alt"></i><span>${data}</span></a>`
   - Email: `<a href="mailto:${data}" class="contacto-item email"><i class="fas fa-envelope"></i><span>${data}</span></a>`
4. **Executive Badges**:
   - `.badge-detal` (Soft Sky Blue) / `.badge-mayorista` (Vibrant Amber / Gold)
   - `.badge-activo` (Fresh Emerald Green) / `.badge-inactivo` (Rose Crimson Red)
5. **Action Buttons in DataTable**:
   ```javascript
   <div class="d-flex justify-content-center gap-1">
       <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="ver(${row.id});" title="Ver detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
           <i class="fas fa-eye"></i>
       </button>
       <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
           <i class="fas fa-edit"></i>
       </button>
       <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${row.nombre}');" title="Eliminar registro" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
           <i class="fas fa-trash-alt"></i>
       </button>
   </div>
   ```
6. **DataTables Backend Search Optimization**: In Controller `lista()`, always combine name fields with `CONCAT()`:
   ```php
   $query->whereRaw("CONCAT(nombre, ' ', apellido) LIKE ?", ["%{$search}%"])
   ```

---

## 7. Frontend JavaScript Reusable Components

All module JS files consume standard components from `public/estilos/jsPropios/components/`:

1. **`crearDataTable(opciones)`**:
   ```javascript
   crearDataTable({
       selector: "#datatable_clientes",
       url: urlLista,
       searchPlaceholder: "Buscar cliente...",
       columns: [ ... ]
   });
   ```
2. **`consultarRegistro(urlDetalles, id)`**: Fetches record data via AJAX GET.
3. **`enviarFormulario(opciones)`**: Handles `FormData`, method `PUT`, spinner states, SweetAlert2 notifications, modal closing, table reloading, and 422 error highlighting:
   ```javascript
   enviarFormulario({
       form: this,
       url: urlAccion,
       isEditar: isEditar,
       modalSelector: "#modalCliente",
       tablaSelector: "#datatable_clientes",
       btnSubmit: "#modalClienteBtnGuardar",
       textoGuardarOriginal: $("#modalClienteTextoGuardar").text(),
       antesDeEnviar: function (formData) {
           formData.set("cedula", $("#tipo_cedula").val() + cedulaNum);
       }
   });
   ```
4. **`cambiarEstadoRegistro(opciones)`**: SweetAlert2 confirmation dialog with DELETE request and live table reload.
5. **`desglosarCedula(cedula)` & `desglosarTelefono(telefono)`**: Extracts prefix and clean numbers.
6. **`aplicarRestriccionesInput()`**: Automatically sanitizes input lengths and patterns in real-time.

---

## 8. Standard FontAwesome 6 Icons

- `fas fa-users` / `fas fa-user-plus` / `fas fa-user-edit` / `fas fa-user-tag` (Clientes / Usuarios)
- `fas fa-truck-moving` / `fas fa-dolly` (Proveedores / Recepción)
- `fas fa-cogs` / `fas fa-wrench` / `fas fa-tools` (Repuestos)
- `fas fa-motorcycle` / `fas fa-car` (Vehículos)
- `fas fa-boxes-stacked` / `fas fa-warehouse` (Inventario)
- `fas fa-exchange-alt` / `fas fa-clipboard-list` (Kardex)
- `fas fa-file-invoice-dollar` / `fas fa-receipt` (Facturación)
- `fas fa-hand-holding-usd` (CXP / CXC)
- `fas fa-chart-line` / `fas fa-chart-pie` (Reportes)
- `fas fa-sliders-h` / `fas fa-gear` (Configuración)
