# JavaScript: reglas y API real de los helpers

> Fuente de verdad: `public/estilos/jsPropios/components/*.component.js`. Si este documento y el código difieren, gana el código: corrige este documento.
> Los helpers se cargan globalmente desde `resources/views/Sistema/components/js-components.blade.php` (ya incluido por el layout `Sistema.layouts.app`). No los vuelvas a incluir.

## 1. Reglas de archivo (módulos en `public/estilos/jsPropios/*.js`)

1. **Cero comentarios** (`//` y `/* */`) en archivos de módulo. Los nombres deben explicar el código.
   - Excepción: los archivos de `jsPropios/components/` llevan JSDoc; no se tocan.
2. **No asignar `window.xxx = xxx`** en archivos de módulo. Declara con `const nombre = function () {}` o `const nombre = async function () {}`: al estar en el ámbito global del script, los `onclick="nombre()"` inline funcionan igual.
3. **No usar `$.ajax`, `$.get` ni `$.post` directos** para operaciones estándar: usa los helpers de abajo.
4. **No duplicar validación del backend en JS** antes de `enviarFormulario`: el `FormRequest` responde 422 y el helper muestra los errores con SweetAlert2.
5. Rutas en constantes JS con template literals (`${urlBase}/lista`); deben coincidir con `routes/admin.php`.
6. Cada módulo con formulario define `resetearFormulario{Modulo}()` (limpia campos, quita `.is-invalid`, restaura selects y tablas dinámicas).
7. Abrir/cerrar modales con la API nativa de Bootstrap 5:
   ```javascript
   const modalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalX'));
   modalInstance.show();
   ```

## 2. API real de los helpers

### `enviarFormulario(opciones)` — POST/PUT con FormData
| Opción | Tipo | Notas |
|---|---|---|
| `form` | selector o elemento | **Obligatorio.** (NO existe `formId`) |
| `url` | string | Endpoint final (ya con el id si es edición) |
| `isEditar` | boolean | Agrega `_method=PUT` al FormData (NO existe `esActualizacion`/`urlActualizar`) |
| `modalSelector` | string | Ej. `'#modalX'`; se oculta tras éxito (NO existe `modalId`) |
| `tablaSelector` | string | Ej. `'#datatable_x'`; se recarga tras éxito (NO existe `tablaId`) |
| `btnSubmit`, `textoGuardarOriginal` | selector, string | Spinner en el botón |
| `antesDeEnviar(formData)` | fn | Para agregar/mutar campos del FormData |
| `onSuccess(res)`, `onError(errorOXhr)` | fn | Para recargar cards u otra lógica (NO existe `funcionActualizar`) |

```javascript
$('#formularioX').on('submit', function (e) {
    e.preventDefault();
    enviarFormulario({
        form: this,
        url: isEditar ? `${urlActualizar}/${id}` : urlGuardar,
        isEditar: isEditar,
        modalSelector: '#modalX',
        tablaSelector: '#datatable_x',
        btnSubmit: '#modalXBtnGuardar',
    });
});
```

### `cambiarEstadoRegistro(opciones)` — confirmación + `DELETE {url}/{id}`
Opciones: `url` (base), `id`, `nombre`, `titulo`, `mensaje`, `confirmButtonText`, `tablaSelector`, `onSuccess(res)`.
Para refrescar cards usa `onSuccess` (NO existe `funcionActualizar`, `tablaId`, `texto`).

### `consultarRegistro(urlBase, id, callback?)`
Devuelve la promesa de jQuery (`GET {urlBase}/{id}`). Desenvuelve siempre de forma segura:
```javascript
const res = await consultarRegistro(urlDetalles, id);
const datos = res && res.data ? res.data : res;
```

### `peticionAjax(opciones)`
Envoltorio de `$.ajax` con `method: 'GET'` y `dataType: 'json'` por defecto. Pasa las opciones de jQuery tal cual (`url`, `method`, `data`, `contentType`, `processData`). **No existe `mensajeCarga`.**
```javascript
const res = await peticionAjax({ url: urlKpis, method: 'GET', data: params });
```

### `crearDataTable(config)`
`selector` (ej. `'#datatable_x'`), `url`, `columns` (obligatorios). Opcionales: `dataExtra` (objeto o `function (d)` para filtros GET), `order`, `columnDefs`, `pageLength`, `searchPlaceholder`, `responsive`, `serverSide`, `processing`. **No existen `tablaId` ni `columnas`.**
Recargar: `recargarDataTable('#datatable_x', resetPaging = false)`.

### Select2
- `crearSelect2({ selector, modalSelector?, placeholder?, allowClear?, width?, dropdownParent?, minimumResultsForSearch? })` — no acepta `url` ni `selectId`; las opciones se cargan al `<select>` antes (HTML o JS).
- `limpiarSelect2(selector)`, `establecerValorSelect2(selector, valor)`, `destruirSelect2(selector)`.
- Dentro de un modal, `modalSelector` es **obligatorio** (evita problemas de foco y z-index).
- Tras repoblar `<option>` dinámicamente: `$select.trigger('change.select2')` o `establecerValorSelect2`.

### Utilidades
`desglosarCedula(cedula)` → `{ tipo, prefijo, numero }` · `desglosarTelefono(tel)` → `{ codigo, prefijo, numero }` · `aplicarRestriccionesInput()`.

### Toasts y confirmaciones
`window.notificacion.fire({ icon, title, text })` (toast SweetAlert2), `window.confirmarAccion(config, callback)`, `window.mostrarErroresValidacion(xhr, titulo)`. Vienen de `Sistema.components.alert.*`.

### `CalculosCompra` (`calculos-compra.component.js`)
Centraliza cálculos bimonetarios de compras/recepciones (`normalizarNumero`, `calcularPreciosDesdeMargen`, `calcularMargenDesdePrecio`, `calcularEquivalenteMoneda`, `calcularFechaVencimientoCredito`). Úsalo en recepciones; no reimplementes márgenes ni conversiones USD/Bs.

## 3. Pestañas con tablas
En cada `button[data-bs-toggle="pill"|"tab"]` escucha `shown.bs.tab` y ejecuta `$.fn.dataTable.tables({ visible: true, api: true }).columns.adjust()` (y `.responsive.recalc()` si aplica), o las tablas ocultas se renderizan con ancho incorrecto.
