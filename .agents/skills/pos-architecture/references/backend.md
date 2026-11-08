# Backend: controladores, requests, servicios, multi-empresa

## 1. Capas
`Route (permission) → Controller (delgado) → FormRequest (valida) → Service Class (lógica) → Model`

- Controladores: `app/Http/Controllers/{Dominio}/`. Servicios: `app/Service/{Dominio}/{Modulo}Class.php`. Requests: `app/Http/Requests/{Dominio}/{Modulo}/` (`CrearRequest`, `ActualizarRequest`, …).
- Inyección por constructor con propiedad privada promovida: `private ProveedorClass $proveedorClass`.
- `App\Http\Controllers\Controller` ya usa el trait `HasEmpresaActiva`. **No redefinas** `obtenerEmpresaId()`:
  - `$this->obtenerEmpresaId(): int` (aborta 403 si no hay empresa activa)
  - `$this->obtenerEmpresaActiva(): ?Empresa`
- Respuestas JSON consistentes: `{ success: bool, message?: string, data?: mixed }` con códigos 200/403/404/422/500.

## 2. Validación
- **Endpoints que reciben datos para crear/actualizar/mutar** usan siempre un `FormRequest`; nada de `$request->validate([...])` inline. Usa `$request->validated()`; nunca `$request->all()`.
- Los endpoints de solo lectura con filtros opcionales (`fecha_inicio`, `fecha_fin`, `vendedor_id`) pueden leer `$request->input()` sin FormRequest.
- Los FormRequests con alcance de empresa extienden `App\Http\Requests\BaseRequest` (da `$this->empresaId()` / `$this->obtenerEmpresaId()`).
- Reglas en **formato array** (`['required', 'string', 'max:100']`), nunca strings con `|`.
- `messages(): array` obligatorio, en español.

### `Rule::exists` / `Rule::unique`
| Tipo de tabla | Ejemplos | Alcance |
|---|---|---|
| Tenant (tienen `empresa_id`) | `proveedores`, `cuentas_por_cobrar`, `cuentas_por_pagar`, `productos`, `almacenes`, `ventas`, `kardex`, `motos`, `vendedores`, `cajas` | `where('empresa_id', $this->empresaId())` **y** `where('estado', true)` |
| Catálogos globales (sin `empresa_id`) | `metodos_pago`, `clientes` | solo `where('estado', true)` |

```php
'proveedor_id' => ['required', 'integer',
    Rule::exists('proveedores', 'id')->where(fn ($q) => $q->where('empresa_id', $this->empresaId())->where('estado', true)),
],
'rif' => ['required', 'string', 'max:20',
    Rule::unique('proveedores', 'rif')->ignore($this->route('id'))   // ignore solo en ActualizarRequest
        ->where(fn ($q) => $q->where('empresa_id', $this->empresaId())->where('estado', true)),
],
```
La unicidad se evalúa solo contra activos (`estado = true`) para permitir reactivar registros desactivados.

## 3. Servicios y borrado lógico
- Todos los métodos reciben el `empresaId` y filtran por él.
- `lista(int $empresaId)`: devuelve un `Builder` filtrado por `empresa_id` y, por defecto, `estado = true` (para DataTables).
- `guardar(array $datos, int $empresaId)`: si existe un registro **inactivo** con el mismo identificador único (rif, cédula, nombre, email) dentro de la empresa, se actualiza y reactiva; si no, se crea con `estado = true`.
- `eliminar(int $id, int $empresaId)`: borrado lógico (`estado = false`). Algunos módulos (p. ej. `VendedorClass::eliminar`) **alternan** el estado; sigue el patrón del servicio que ya existe en ese módulo.
- Resolución de registros: `Modelo::where('empresa_id', $empresaId)->findOrFail($id)`.

```php
public function guardar(array $datos, int $empresaId): Modelo
{
    $datos['empresa_id'] = $empresaId;
    $existenteInactivo = Modelo::where('empresa_id', $empresaId)->where('estado', false)
        ->where('rif', $datos['rif'])->first();

    if ($existenteInactivo) {
        $existenteInactivo->update($datos + ['estado' => true]);
        return $existenteInactivo;
    }

    return Modelo::create($datos + ['estado' => true]);
}
```

## 4. Multi-empresa y Concurrencia Transaccional (reglas de oro)
1. **Multi-empresa:** Toda entidad transaccional o de catálogo lleva `empresa_id`, y **toda** consulta, regla de validación y reactivación se limita a `$this->obtenerEmpresaId()`. Una consulta sin `where('empresa_id', …)` sobre una tabla tenant es un fallo crítico de seguridad.
2. **Prohibición de Fallbacks Silenciosos:** Prohibido el uso de fallbacks tipo `?? 1` en `empresa_id`, `user_id` o `almacen_id`. Si falta el contexto autenticado o el parámetro, se debe arrojar una excepción explícita (`Exception` o `AuthorizationException`).
3. **Atomicidad y Bloqueo Pesimista:** Toda operación que altere inventario, dinero, cajas o documentos fiscales debe envolverse en `DB::transaction()` con `lockForUpdate()` sobre los modelos involucrados (`ProductoStockAlmacen`, `Moto`, `ProductoSerial`, `Venta`, `CuentaPorCobrar`, `CuentaPorPagar`).
4. **Secuencias y Correlativos Consecutivos:** Usar siempre `Secuencia::siguiente($empresaId, $tipo)` para generar números de factura (`venta`), números de control fiscal (`numero_control`), devoluciones (`devolucion`) o liquidaciones (`liquidacion_comision`). Jamás consultar `MAX(id)` ni generar correlativos sin cerrojo transaccional.

## 5. Rutas y permisos
- En `routes/admin.php`, **cada ruta** lleva `->middleware('permission:{modulo}.{accion}')` individual (ver `permisos.md`).
- No uses un `Route::middleware('permission:...')->group()` amplio para módulos nuevos: un operador de solo lectura podría ejecutar `PUT`/`DELETE`.
- Nombra rutas con `->name()` y úsalas con `route()` en Blade; en JS, constantes que apuntan a esas rutas.

## 6. Controller `index()`: solo la vista
`index()` devuelve únicamente `return view('Sistema.pages…');`. No pases modelos ni colecciones con `compact()`/`with()`. Catálogos, selects, KPIs y listas se cargan por AJAX desde endpoints JSON (`/lista`, `/catalogos`, `/activos`, `/kpis`, `/datos`). Excepciones válidas: vistas imprimibles (`ticket-venta`, `*-imprimir`, `reporte-*-ticket`) que reciben el modelo a imprimir.

## 7. Datos y base de datos
- Constantes del POS (prefijos de cédula, códigos de país, formatos) en `config/pos.php`; llegan a Blade (`<x-input-documento>`, `<x-input-telefono>`) y a JS (`window.CONFIG_POS`).
- **Nunca ejecutes `php artisan migrate:fresh`** ni borres la base para cambios rutinarios. Solo si es inevitable y el usuario lo confirma explícitamente.
- `DatabaseSeeder` solo llama a `RolesYPermisosSeeder`. **Nunca** siembres usuarios ni empresas de prueba: la Empresa Matriz y el SuperAdmin se crean en `/configuracion-inicial`.
- Autenticación: `User.name` guarda la cédula (`V-12345678`); login con `name` + `password`, usuario con `estado = true`. Contraseña mínima: 8 caracteres en instalación, 6 en gestión de usuarios.

## 8. Verificación
- `vendor/bin/pint --format agent` tras modificar PHP.
- Pest: `php artisan test --compact --filter=Modulo` (o el archivo). Los tests de módulos viven en `tests/Feature/Empresa/`.
- Pide al usuario que corra la suite completa al final (`php artisan test --compact`).
