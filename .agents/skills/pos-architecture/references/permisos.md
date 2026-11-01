# Permisos y roles (Spatie Laravel Permission)

> **Fuente de verdad de la matriz de permisos:** `UsuarioClass` (`app/Service/Administradores/UsuarioClass.php`, clave `permisos_modulos`) y `database/seeders/RolesYPermisosSeeder.php`. No dupliques la lista aquí: léela de esos archivos. Incluye módulos como Vendedores, Reportes (`reportes.ver`, `reportes.vendedores`) y Logs (`logs.ver`) que no estaban en documentos antiguos.

## 1. Roles
| Rol | Alcance |
|---|---|
| `SuperAdmin` | Raíz global, todos los permisos sincronizados, sin restricción de empresa (cambia con el selector del navbar). Puede crear `SuperAdmin`, `Admin`, `Operador`. |
| `Admin` | Una o más empresas asignadas. Crea/gestiona solo `Admin` (de sus empresas) y `Operador`. **Nunca** crea ni ve `SuperAdmin`. |
| `Operador` | Opera POS/cajas/ventas. Permisos por módulo asignables (`ver`, `crear`, `editar`, `eliminar`, más acciones específicas). |

Aislamiento:
- Los `SuperAdmin` son invisibles para `Admin` y `Operador` (tablas, selects, gestión de usuarios).
- Un `Admin` solo asigna empresas del subconjunto que él tiene.
- Un usuario no-SuperAdmin solo accede/cambia a empresas asignadas (`empresa_user`); cualquier intento externo responde 403.
- Cajeros (`/cajas/turnos/aperturar`) y usuarios vinculables a vendedores solo incluyen usuarios de la empresa activa y excluyen `SuperAdmin`.

## 2. Convención de nombres
`{modulo}.{accion}`: `clientes.ver`, `clientes.crear`, `clientes.editar`, `clientes.eliminar`; acciones especiales: `ventas.anular`, `ventas.descuentos`, `cxc.abonar`, `cajas.aperturar|cerrar|movimientos|arqueo`, `inventario.kardex|traslados|ajustar_stock`, `compras.recepcion`, `motos.recepcion`, `pos.acceso`.

## 3. Dependencia de permisos
Toda acción (`.crear`, `.editar`, `.eliminar`, `.abonar`, `.traslados`, `.ajustar_stock`, `.anular`, `.descuentos`, `.movimientos`, `.arqueo`, `.aperturar`, `.cerrar`, `.permisos`, `.recepcion`) requiere su permiso padre (`.ver`, `.kardex` o `.acceso`).
- Backend: `UsuarioClass::normalizarPermisos(array $permisos)` inyecta los padres antes de guardar.
- Frontend (`usuario.js`): activar una acción activa el switch padre; desactivar el padre desactiva todas las acciones del módulo.

## 4. Dónde se aplican
1. **Rutas:** middleware individual por ruta (`->middleware('permission:modulo.accion')`). Meta para código nuevo; hay grupos con middleware amplio en rutas heredadas (reportes, facturas) que no se tocan sin pedirlo.
2. **Blade:** botones de creación/mutación envueltos en `@can('modulo.accion')` (`<x-btn-action>` y `<x-button>` incluidos). Sin permiso, el botón no existe en el DOM.
3. **Sidebar:** `resources/views/Sistema/layouts/sidebar.blade.php` con `@can`/`canAny`.
4. **Nuevos permisos:** agrégalos en `RolesYPermisosSeeder` **y** en `permisos_modulos` de `UsuarioClass`, y al sidebar si hay entrada de menú.
