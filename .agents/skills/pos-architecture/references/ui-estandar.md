# UI/UX: estándar visual ejecutivo

Aplica a toda vista `resources/views/Sistema/pages/**` y a los modales. El stack visual es **Bootstrap 5 + CSS propio** (no Tailwind). Cada pantalla debe verse limpia, moderna, ejecutiva y consistente.

## 1. Reglas obligatorias (una sola vez, sin duplicados)

1. **Sin fondos grises.** Prohibido `bg-light`, `bg-light-subtle`, `alert-light`, `table-light` y grises apagados en tarjetas, sub-tarjetas, previews, switches, badges y píldoras. Usa `bg-white border rounded-4 shadow-xs` (o `rounded-3`) y badges suaves (`bg-primary-subtle`, `bg-success-subtle`, `bg-warning-subtle`, `bg-danger-subtle`, `bg-info-subtle`).
2. **Sin bordes en entidades ni identificaciones.** Nombres de empresas/sedes, cédulas, RIFs y códigos de usuario se muestran como texto tipográfico con icono, sin caja, píldora ni borde:
   ```html
   <span class="d-inline-flex align-items-center gap-1.5 fw-semibold text-dark"><i class="fas fa-building text-primary"></i> Nombre</span>
   <span class="d-inline-flex align-items-center font-monospace fw-semibold text-secondary" style="font-size: 0.80rem;"><i class="fas fa-id-card me-1 text-primary"></i> V-12344111</span>
   ```
3. **Sin anidación de bordes en switches/toggles.** Nada de una sub-tarjeta con borde por cada switch. Usa listas abiertas: filas sin borde o con divisor inferior sutil `border-bottom border-light-subtle`.
4. **Iconos de módulo limpios** en modales: `rounded-circle` o fondo tenue (`#eef2ff` + `text-primary`), sin borde de caja.
5. **Todo redondeado:** cards, botones, modales, badges, inputs (`rounded-4`, `rounded-3`, `rounded-pill`). Sin esquinas rectas.
6. **Jerarquía y aire:** contraste alto, espaciado generoso, sombras sutiles (`shadow-xs`, `shadow-sm`, `shadow-hover`), tipografía **Plus Jakarta Sans** o **Inter** (pesos 400–800). Sin mayúsculas forzadas en labels de formulario.
7. **Iconos:** FontAwesome 6 (`fas fa-*`) según el dominio (ver §6).
8. **Tickets térmicos (80mm):** el RIF/identificación fiscal es texto plano `font-monospace fw-bold`, nunca en píldora, badge ni borde.
9. **Reutiliza componentes** (ver `componentes-blade.md`) antes de escribir marcado.

## 2. Estructura de una página estándar

```blade
@extends('Sistema.layouts.app')
@section('titulo', '📊 Título')
@section('subtitulo', 'Descripción corta')
@section('rutas') … breadcrumb … @endsection
@section('acciones') @can('modulo.crear') <x-btn-action … /> @endcan @endsection
@section('contenido') … @endsection
@section('scripts')
    @include('Sistema.components.datatable')   {{-- solo si usa DataTables --}}
    <script>const urlLista = "{{ url('/modulo/lista') }}";</script>
    <script src="{{ asset('estilos/jsPropios/modulo.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/modulo.js')) ?: time() }}"></script>
@endsection
```

## 3. Patrones reutilizables

**Tarjeta KPI** (el patrón vigente; no existe componente):
```html
<div class="col-12 col-sm-6 col-xl-3">
  <div class="card border rounded-4 shadow-xs bg-white p-3 h-100">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <span class="text-muted small font-monospace fw-semibold">ETIQUETA</span>
      <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;"><i class="fas fa-dollar-sign"></i></div>
    </div>
    <h3 class="fw-bold mb-0 text-dark font-monospace" id="kpiX">$ 0.00</h3>
    <span class="badge bg-primary-subtle text-primary rounded-pill font-monospace mt-2" id="kpiXBs">Bs. 0.00</span>
  </div>
</div>
```
Paleta semántica: primario/ventas `primary` (#4f46e5 en gráficos) · éxito/comisiones/ingresos `success` (#10b981) · alerta/líder/crédito `warning` · peligro/devoluciones `danger` · informativo `info`.

**Banner ejecutivo de reportes:** `card border-0 rounded-4 shadow-sm text-white overflow-hidden` con `background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%)` (ver `reportes.blade.php`).

**Barra de filtros:** `card border rounded-4 shadow-xs bg-white p-3` con `<x-input type="date">`, `<x-select>`/`<x-select2>` y `<x-button>`; encabezado con `<x-section-header>` cuando lleve título.

**Pestañas (pills):**
- La navegación va en su propia tarjeta `card border rounded-4 p-2 shadow-xs mb-4 bg-white` y, justo debajo, los `tab-pane`.
- No envuelvas un `<x-datatable>` en tarjetas anidadas dentro del tab-pane.
- Número y semántica de `headers` del `<x-datatable>` = `columns` del JS.
- Al activar pestaña, reajusta columnas (ver `javascript-helpers.md` §3).

**Modales (`<x-modal>`):** tarjetas internas `bg-white border rounded-4 shadow-xs`; switches en listas abiertas (regla 3).

## 4. Card grid vs DataTable

| Usa | Módulos | Motivo |
|---|---|---|
| **Card grid ejecutiva** | Empresas, Usuarios/Administradores, Almacenes | Pocos registros; importan avatar/logo, estado, pills de empresa y contadores de permisos |
| **DataTable** | Clientes, Proveedores, Productos, Ventas/Facturas, Kardex, Categorías, Servicios, Métodos de Pago, Vendedores, Cajas | Volumen alto: paginación, orden y server-side |

Grid de cards: (1) tarjeta de búsqueda con `<x-search-filter>`; (2) `row g-4` con `.card-executive rounded-4`, hover con elevación; (3) cabecera con avatar squircle (`avatar-executive-md`), nombre `fw-bold text-dark` y badge de rol/documento; (4) cuerpo con chips de contacto; (5) pie con botones redondeados Editar/Eliminar; (6) skeleton de carga mientras llega el AJAX.

## 5. Gráficos
Chart.js (CDN cargado en el layout). Barras con `borderRadius: 6`, leyenda propia en HTML, estado vacío con icono y texto, y `destroy()` de la instancia previa antes de redibujar.

## 6. Iconos estándar
Usuarios/Clientes `fa-users`, `fa-user-plus`, `fa-user-edit`, `fa-user-shield`, `fa-user-tag` · Empresas/Sedes `fa-building`, `fa-store`, `fa-landmark` · Proveedores/Recepción `fa-truck-moving`, `fa-dolly` · Pagos `fa-credit-card`, `fa-money-bill-wave`, `fa-dollar-sign` · Inventario `fa-boxes-stacked`, `fa-warehouse` · POS/Facturación `fa-cash-register`, `fa-receipt` · Reportes/Config `fa-chart-line`, `fa-sliders-h` · Vendedores `fa-user-tie`.
