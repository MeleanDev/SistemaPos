# Permisos y roles (Spatie Laravel Permission)

> **Fuente de verdad de la matriz de permisos:** `UsuarioClass` (`app/Service/Administradores/UsuarioClass.php`, clave `permisos_modulos`) y `database/seeders/RolesYPermisosSeeder.php`. No dupliques la lista aquí: léela de esos archivos. Incluye módulos como Vendedores, Reportes (`reportes.ver`, `reportes.vendedores`) y Logs (`logs.ver`) que no estaban en documentos antiguos.

## 1. Roles
| Rol | Alcance |
|---|---|
| `SuperAdmin` | Raíz global, todos los permisos sincronizados, sin restricción de empresa (cambia con el selector del navbar). Puede crear `SuperAdmin`, `Admin`, `Operador`, `Vendedor`. |
| `Admin` | Una o más empresas asignadas. Crea/gestiona `Admin`, `Operador` y `Vendedor` (de sus empresas). **Nunca** crea ni ve `SuperAdmin`. |
| `Operador` | Opera POS/cajas/ventas. Permisos por módulo asignables granularmente (`ver`, `crear`, `editar`, `eliminar`, más acciones operativas). Perfil cajero típico: `pos.acceso, ventas.crear, cajas.ver, cajas.movimientos, cajas.arqueo, cxc.ver, cxc.abonar`. |
| `Vendedor` | Asignable desde UI para asesores comerciales: solo preventa (`pos.acceso, pos.preventa, reportes.vendedores, consultor.ver`). No cobra ni factura ni anula; ve/elimina solo sus propias preventas y ve solo sus comisiones. |

Aislamiento:
- Los `SuperAdmin` son invisibles para `Admin`, `Operador` y `Vendedor` (tablas, selects, gestión de usuarios).
- Un `Admin` solo asigna empresas del subconjunto que él tiene.
- Un usuario no-SuperAdmin solo accede/cambia a empresas asignadas (`empresa_user`); cualquier intento externo responde 403.
- Cajeros (`/cajas/turnos/aperturar`) y usuarios vinculables a vendedores solo incluyen usuarios de la empresa activa y excluyen `SuperAdmin`.

## 2. Convención de nombres y permisos actuales
`{modulo}.{accion}`:
- **Catálogos y básicos:** `clientes.*`, `proveedores.*`, `categorias.*`, `servicios.*`, `almacenes.*`, `usuarios.*` (`ver`, `crear`, `editar`, `eliminar`, más `usuarios.permisos`, `usuarios.asignar_empresas`).
- **Productos & Kardex:** `productos.*`, `inventario.kardex`, `inventario.traslados`, `inventario.ajustar_stock`, `compras.recepcion`.
- **Motos & Seriales:** `motos.*` (`ver`, `crear`, `editar`, `eliminar`, `motos.recepcion`).
- **POS & Facturación:** `pos.acceso`, `pos.preventa`, `ventas.ver`, `ventas.crear`, `ventas.anular`, `ventas.descuentos`.
- **Cajas & Turnos:** `cajas.ver`, `cajas.crear`, `cajas.editar`, `cajas.eliminar`, `cajas.aperturar`, `cajas.cerrar`, `cajas.movimientos`, `cajas.arqueo`.
- **Créditos:** `cxc.ver`, `cxc.abonar`, `cxp.ver`, `cxp.abonar`.
- **Vendedores:** `vendedores.ver`, `vendedores.crear`, `vendedores.editar`, `vendedores.eliminar`, `vendedores.liquidar`.
- **Reportes:** `reportes.ver`, `reportes.financieros`, `reportes.inventario`, `reportes.vendedores`.
- **Consultor & Config:** `consultor.ver`, `configuracion.ver`, `configuracion.editar`, `logs.ver`.

## 3. Dependencia de permisos
Toda acción secundaria (`.crear`, `.editar`, `.eliminar`, `.abonar`, `.traslados`, `.ajustar_stock`, `.anular`, `.descuentos`, `.movimientos`, `.arqueo`, `.aperturar`, `.cerrar`, `.permisos`, `.recepcion`, `.preventa`, `.liquidar`) requiere su permiso padre (`.ver`, `.kardex` o `.acceso`).
- Backend: `UsuarioClass::normalizarPermisos(array $permisos)` inyecta los padres automáticamente antes de persistir.
- Frontend (`usuario.js`): activar una acción activa el switch padre; desactivar el padre desactiva todas las acciones dependientes del módulo.

## 4. Dónde se aplican
1. **Rutas:** middleware individual por ruta (`->middleware('permission:modulo.accion')`). Meta para código nuevo; hay grupos con middleware amplio en rutas heredadas (reportes, facturas) que no se tocan sin pedirlo.
2. **Blade:** botones de creación/mutación envueltos en `@can('modulo.accion')` (`<x-btn-action>` y `<x-button>` incluidos). Sin permiso, el botón no existe en el DOM.
3. **Sidebar:** `resources/views/Sistema/layouts/sidebar.blade.php` con `@can`/`canAny`.
4. **Nuevos permisos:** agrégalos en `RolesYPermisosSeeder` **y** en `permisos_modulos` de `UsuarioClass`, y al sidebar si hay entrada de menú.
