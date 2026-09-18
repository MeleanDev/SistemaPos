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

---

## 9. Multi-Tenant, Multi-Almacén & User Role Hierarchy

The POS system supports both **a single holding company with multiple child branches/companies** and **an independent single company**.

```
                           ┌───────────────────────────┐
                           │   SUPERADMINISTRADOR      │
                           │ (Plataforma Global, Logs) │
                           └─────────────┬─────────────┘
                                         │ Crea / Administra
                    ┌────────────────────┴────────────────────┐
                    ▼                                         ▼
         ┌─────────────────────┐                   ┌─────────────────────┐
         │  EMPRESA 1 (Matriz) │                   │  EMPRESA 2 (Filial) │
         └──────────┬──────────┘                   └──────────┬──────────┘
                    │                                         │
       ┌────────────┴────────────┐               ┌────────────┴────────────┐
       ▼                         ▼               ▼                         ▼
┌──────────────┐          ┌──────────────┐┌──────────────┐          ┌──────────────┐
│  ALMACÉN A   │          │  ALMACÉN B   ││  ALMACÉN C   │          │  ALMACÉN D   │
└──────────────┘          └──────────────┘└──────────────┘          └──────────────┘
       │                         │               │                         │
       ▼                         ▼               ▼                         ▼
┌──────────────┐          ┌──────────────┐┌──────────────┐          ┌──────────────┐
│ CAJA / POS 1 │          │ CAJA / POS 2 ││ CAJA / POS 3 │          │ CAJA / POS 4 │
└──────────────┘          └──────────────┘└──────────────┘          └──────────────┘
```

### 9.1 Role Hierarchy & Multi-Company Authorization Rules

1. **Superadministrador (Global Root)**:
   - Único facultado para dar de alta y configurar **Empresas** y **Almacenes**.
   - **Autorización de Empresas a Administradores**: Es el único que puede asignar o conceder acceso a un usuario Administrador a una o varias empresas (`empresa_user`).
   - Acceso global a todas las empresas del sistema mediante el selector de empresa activa en el Navbar.
   - Acceso a logs globales de auditoría de la plataforma, estadísticas consolidadas y configuración general del sistema.
2. **Administrador (Nivel Empresa)**:
   - Pertenece a una empresa inicial y solo puede acceder a otras empresas **si el Superadministrador le ha otorgado los permisos explícitos**.
   - No puede auto-asignarse a otras empresas sin autorización del Superadministrador.
   - Administra los **Usuarios** de su empresa y gestiona la **Matriz Granular de Roles y Permisos**.
   - Gestiona sus propios **Proveedores** (`proveedores` con `empresa_id`), productos, precios, compras, inventario, reportes, CXC y CXP de su empresa activa.
3. **Usuarios / Operadores (Cajeros, Vendedores, Almacenistas, Despachadores)**:
   - Pertenecen estrictamente a **1 sola Empresa** y se les asigna uno o más `Almacenes` y `Cajas`.
   - No pueden cambiar de empresa ni ver el selector en el Navbar.
   - Permisos estrictos por módulo y por acción (`ver`, `crear`, `editar`, `eliminar`, `anular_factura`, `aplicar_descuento`, `hacer_traslado`, `aperturar_caja`, etc.).

---

## 10. Core Functional Domains & Database Architecture

### A. Catálogos & Multi-Empresa
- `empresas`: `id`, `rif`, `nombre`, `razon_social`, `logo`, `telefono`, `correo`, `direccion`, `configuracion_json`, `estado`.
- `almacenes`: `id`, `empresa_id`, `codigo`, `nombre`, `ubicacion`, `es_principal`, `estado`.
- `clientes`: `id`, `empresa_id` (opcional/global o por empresa), `cedula`, `nombre`, `apellido`, `telefono`, `correo`, `direccion`, `tipo_cliente`, `estado`.
- `proveedores`: `id`, `empresa_id`, `rif`, `nombre`, `razon_social`, `nombre_contacto`, `telefono`, `correo`, `direccion`, `estado`.
- `metodos_pago`: `id`, `nombre`, `descripcion`, `estado`.
- `categorias` & `marcas`: Clasificación organizada de artículos.

### B. Inventario, Productos & Kardex
- `productos`: `id`, `empresa_id`, `codigo_barra`, `codigo_interno`, `nombre`, `descripcion`, `tipo` (`producto`, `servicio`, `repuesto`, `vehiculo`), `categoria_id`, `marca_id`, `precio_costo`, `precio_venta_detal`, `precio_venta_mayorista`, `maneja_inventario`, `estado`.
- `inventario_almacen`: `id`, `empresa_id`, `almacen_id`, `producto_id`, `stock_actual`, `stock_minimo`, `stock_maximo`, `ubicacion_pasillo`.
- `kardex_movimientos`: `id`, `empresa_id`, `almacen_id`, `producto_id`, `tipo_movimiento` (`entrada_compra`, `salida_venta`, `traslado_origen`, `traslado_destino`, `ajuste_positivo`, `ajuste_negativo`, `anulacion`), `cantidad`, `costo_unitario`, `stock_anterior`, `stock_nuevo`, `referencia_tipo`, `referencia_id`, `usuario_id`, `created_at`.
- `traslados` & `traslado_detalles`: Movimientos controlados de mercancía entre almacenes (origen -> destino con estados `pendiente`, `en_transito`, `completado`, `cancelado`).
- `compras` / `recepcion_mercancia`: Registro de órdenes de entrada con proveedor, almacén receptor, factura fiscal y actualización directa de stock + Kardex.

### C. Punto de Venta (POS), Cajas & Facturación
- `cajas`: `id`, `empresa_id`, `almacen_id`, `nombre`, `codigo`, `estado`.
- `caja_sesiones`: `id`, `caja_id`, `usuario_id`, `fecha_apertura`, `monto_inicial_efectivo`, `fecha_cierre`, `monto_cierre_declarado`, `monto_cierre_sistema`, `diferencia`, `estado` (`abierta`, `cerrada`).
- `caja_movimientos`: `id`, `caja_sesion_id`, `tipo` (`ingreso`, `egreso`, `gasto_menor`), `monto`, `motivo`, `usuario_id`.
- `ventas`: `id`, `empresa_id`, `almacen_id`, `caja_sesion_id`, `cliente_id`, `usuario_id`, `numero_factura`, `tipo_documento` (`ticket`, `factura`, `nota_entrega`), `subtotal`, `descuento`, `iva`, `total`, `tipo_pago` (`contado`, `credito`), `estado` (`completada`, `anulada`).
- `venta_detalles`: `id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `descuento`, `subtotal`, `total`.
- `venta_pagos`: `id`, `venta_id`, `metodo_pago_id`, `monto`, `moneda`, `tasa_cambio`, `referencia`.

### D. Créditos & Finanzas (CXC & CXP)
- `cxc_cuentas`: Registro de cuentas por cobrar originadas por ventas a crédito (`saldo_total`, `saldo_pendiente`, `fecha_vencimiento`, `estado`: `pendiente`, `parcial`, `pagada`).
- `cxc_abonos`: Historial de pagos y abonos realizados por el cliente con recibo y método de pago.
- `cxp_cuentas`: Registro de cuentas por pagar a proveedores por compras a crédito.
- `cxp_abonos`: Historial de pagos emitidos al proveedor.

### E. Seguridad, Roles, Permisos Granulares & Logs
- Matriz RBAC de permisos por módulo y por acción (`modulo.accion`):
  - Ej: `ventas.ver`, `ventas.crear`, `ventas.anular`, `ventas.descuento`, `cajas.aperturar`, `cajas.cerrar`, `inventario.ajustar`, `traslados.autorizar`.
- `logs_actividad`: `id`, `empresa_id`, `usuario_id`, `modulo`, `accion`, `ip`, `dispositivo`, `datos_antes_json`, `datos_despues_json`, `created_at`.

---

## 11. Step-by-Step Implementation Roadmap

1. **Fase 1 - Fundaciones Multi-Empresa & Almacenes**:
   - Módulo de Empresas (Superadmin).
   - Módulo de Almacenes (Superadmin / Asignación a Empresas).
2. **Fase 2 - Seguridad, Roles & Permisos Granulares**:
   - Módulo de Usuarios y Administradores.
   - Matriz de Roles y Permisos por módulo y acción.
   - Módulo de Auditoría y Logs de Actividad.
3. **Fase 3 - Catálogos de Artículos & Configuración de Stock**:
   - Categorías y Marcas.
   - Productos y Servicios (con selector de tipo: repuesto, moto/vehículo, producto estándar, servicio).
   - Stock por Almacén y Control de Precios (Detal / Mayorista).
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

