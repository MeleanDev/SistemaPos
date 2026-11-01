# Componentes Blade: catálogo real y cuándo usar cada uno

> Fuente de verdad: `resources/views/components/*.blade.php` (se usan como `<x-nombre>`). Antes de crear marcado nuevo, revisa si ya existe el componente.

## 1. Catálogo (props reales)

| Componente | Props principales | Uso |
|---|---|---|
| `<x-button>` | `variant` (`primary`, `success`, `outline-dark`, `outline-danger`, `outline-success`, `outline-secondary`…), `size` (`xs`/`sm`/`md`/`lg`), `rounded`, `type`, `icon`, `iconColor`, `iconPosition`, `text`, `badge`, `href`, `target`, `disabled` | Botón/enlace genérico (79 usos). Acciones secundarias, filtros, exportaciones. |
| `<x-btn-action>` | `icon`, `text`, `type`, `variant`, `href`, `target` | CTA principal con gradiente. **Solo en `@section('acciones')`** de la cabecera de página (crear, ir a otro módulo). Envolver en `@can('{modulo}.crear')`. |
| `<x-input>` | `name`, `id`, `label`, `icon`, `type`, `placeholder`, `required`, `maxlength`, `col`, `value`, `readonly`, `disabled`, `optionalText`, `addonIcon`, `addonText`, `addonPosition` (`left`/`right`), `helpText` | Input estándar con icono/addon. |
| `<x-select>` | `name`, `id`, `label`, `icon`, `required`, `col`, `disabled`, `optionalText` | `<select>` simple sin búsqueda. No tiene `placeholder`. |
| `<x-select2>` | `name`, `id`, `label`, `icon`, `placeholder`, `required`, `col`, `disabled`, `optionalText`, `modalParent`, `allowClear`, `actionText`, `actionIcon`, `actionOnClick` | Select con búsqueda. Obligatorio para catálogos (clientes, productos, proveedores, usuarios). Se inicializa en JS con `crearSelect2`. |
| `<x-input-documento>` | `label`, `selectName`, `inputName`, `types`, `defaultType`, `required`, `col`, `maxlength` | Tipo + número de cédula/RIF. |
| `<x-input-telefono>` | `label`, `selectName`, `inputName`, `defaultCode`, `countries`, `required`, `optionalText`, `col` | Código de país + teléfono. |
| `<x-checkbox>` | `name`, `id`, `value`, `label`, `description`, `checked`, `disabled`, `switch`, `inline`, `col`, `labelClass`, `wrapperClass` | Checkbox o switch (`switch="true"`). |
| `<x-radio>` | `name`, `value`, `label`, `description`, `checked`, `card`, `icon`, `iconColor`, `iconBg`… | Radio normal o en formato card. |
| `<x-modal>` | `id`, `title`, `subtitle`, `icon`, `size`, `headerColor`, `formId`, `submitButton`, `submitText`, `submitIcon`, slot `footer` | Único modal permitido. |
| `<x-section-header>` | `title`, `subtitle`/`description`, `icon`, `class` + slot (botones) | Encabezado de subformularios/secciones/filtros (reemplaza alertas grises). |
| `<x-table-dynamic>` | `id`, `bodyId`, `headers` (`['label' => 'X', 'width' => '20%', 'class' => 'text-end']`), `class` | Tabla de subformulario o tabla armada por JS (no DataTables). |
| `<x-datatable>` | `id`, `tbodyId`, `headers` (strings o `['title' => 'X', 'class' => '', 'width' => '', 'style' => '']`), `withCard` (def. `true`), `tableClass`, `theadClass`, `cardClass`, `cardBodyClass` | Tabla DataTables server-side. Pasa `:withCard="false"` si ya está dentro de otro contenedor. |
| `<x-search-filter>` | `inputId`, `counterId`, `placeholder`, `loadingText`, `counterIcon`, `badgeClass` | Barra de búsqueda en vivo + contador para grids de cards. |

Diferencia de cabeceras: `x-table-dynamic` usa la clave `label`; `x-datatable` usa `title`/`name`.

## 2. Selectores generados por `<x-modal id="modalX">` (obligatorios)

| Elemento | ID |
|---|---|
| Header | `#modalXHeader` |
| Título | `#modalXTitulo` |
| Subtítulo (solo si hay `subtitle`) | `#modalXSubtitulo` |
| Icono | `#modalXIcono` |
| Botón guardar | `#modalXBtnGuardar` |
| Texto del botón guardar | `#modalXTextoGuardar` |

Nunca inventes IDs como `#modalXTituloTexto` o `#btnGuardarX`.

## 3. Trampas de nombres (esto confunde)

1. **`<x-datatable>` ≠ `@include('Sistema.components.datatable')`.**
   - `<x-datatable>` (`components/datatable.blade.php`) dibuja la tabla HTML.
   - `@include('Sistema.components.datatable')` (`Sistema/components/datatable.blade.php`) carga los CSS/JS de DataTables y el tema. Va en `@section('scripts')` de toda vista con DataTables.
   - `crearDataTable` (JS) inicializa la tabla. Los tres se usan juntos.
2. **Parciales huérfanos — NO usar:** `Sistema/components/modal.blade.php`, `Sistema/components/select2.blade.php`, `Sistema/components/card-stat.blade.php`. No tienen usos; los componentes válidos son `<x-modal>` y `<x-select2>`. Para KPIs usa el patrón de tarjeta de `ui-estandar.md`.
3. `<x-btn-action>` (gradiente, cabecera) vs `<x-button>` (genérico). No uses `<x-btn-action>` dentro del contenido.
4. `<x-input-documento>`: `defaultType` por defecto es `'V-'`; si pasas `:types="['V' => 'V-']"` (clave sin guion), `defaultType` debe ser la clave (`"V"`). Revisa el módulo hermano (`vendedor.blade.php` vs `cliente.blade.php`).
5. Las vistas de reportes/PDF (`Sistema/pdf/*`) y tickets (`*-ticket`, `*-imprimir`) son documentos independientes con su propio CSS inline; no usan estos componentes.
