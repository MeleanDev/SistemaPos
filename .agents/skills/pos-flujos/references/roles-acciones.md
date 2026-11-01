# Roles y matriz de acciones

## 1. Roles
| Rol | Descripción |
|---|---|
| `SuperAdmin` | Raíz global; único con bypass por rol. Gestiona empresas y métodos de pago. |
| `Admin` | Gestiona sus empresas: usuarios, catálogos, cajas, vendedores, reportes. Puede facturar eligiendo caja (D6). |
| `Operador` | Rol base; permisos asignados por usuario. Perfil típico **Cajero**: `pos.acceso, ventas.crear, cajas.ver, cajas.movimientos, cajas.arqueo, cxc.ver, cxc.abonar` (+ `ventas.descuentos`/`ventas.anular` solo si se otorga). |
| `Vendedor` | Solo preventa (D1): `pos.acceso, pos.preventa`. Asignable desde la UI. |

Reglas: SuperAdmin invisible a Admin/Operador/Vendedor. Admin solo asigna empresas que él tiene. Acciones dependen de su permiso padre (`UsuarioClass::normalizarPermisos`).

## 2. Matriz (✅ permitido · 🔑 solo con el permiso indicado · ❌ no)

| Acción | Permiso | SuperAdmin | Admin | Cajero | Vendedor |
|---|---|:--:|:--:|:--:|:--:|
| Gestionar empresas | rol SuperAdmin | ✅ | ❌ | ❌ | ❌ |
| Gestionar métodos de pago (globales) | rol SuperAdmin | ✅ | ❌ | ❌ | ❌ |
| Gestionar usuarios / permisos | `usuarios.*` | ✅ | ✅ | ❌ | ❌ |
| Catálogos (clientes, productos, almacenes…) | `modulo.*` | ✅ | ✅ | 🔑 | ❌ |
| Crear/editar vendedores y % | `vendedores.*` | ✅ | ✅ | ❌ | ❌ |
| Liquidar comisiones | `vendedores.liquidar` | ✅ | ✅ | ❌ | ❌ |
| Aperturar turno | `cajas.aperturar` | ✅ | ✅ | ❌ | ❌ |
| Crear preventa | `pos.preventa` o `ventas.crear` | ✅ | ✅ | ✅ | ✅ |
| Ver/editar/eliminar preventas | dueño o `ventas.crear` | ✅ | ✅ | ✅ (todas) | solo propias |
| Facturar | `ventas.crear` | ✅ (elige caja) | ✅ (elige caja) | ✅ (su turno) | ❌ |
| Descuento / precio distinto | `ventas.descuentos` | ✅ | ✅ | 🔑 con tope | ❌ |
| Anular / devolver | `ventas.anular` | ✅ | ✅ | 🔑 | ❌ |
| Reimprimir factura | `ventas.ver` | ✅ | ✅ | ✅ | ❌ |
| Movimientos de caja | `cajas.movimientos` | ✅ | ✅ | 🔑 su turno | ❌ |
| Corte X | `cajas.arqueo` | ✅ | ✅ | su turno | ❌ |
| Cierre Z | `cajas.cerrar` | ✅ cualquiera | ✅ cualquiera | solo el suyo, a ciegas | ❌ |
| Abonar CXC | `cxc.abonar` | ✅ | ✅ | 🔑 con turno | ❌ |
| Abonar CXP / recepciones / traslados / ajustes | permisos propios | ✅ | ✅ | 🔑 | ❌ |
| Reportes financieros | `reportes.ver` | ✅ | ✅ | ❌ | ❌ |
| Reporte de vendedores | `reportes.vendedores` | todos | todos | ❌ | solo propios |
| Consultor de precios | `consultor.ver` (nuevo) | ✅ | ✅ | ✅ | ✅ |
| Dashboard | `dashboard.ver` o autenticado + datos filtrados | ✅ | ✅ | limitado | limitado |

## 3. Reglas de implementación
- Usar `@can`/middleware por permiso. Eliminar `hasRole(['SuperAdmin','Admin','superadmin','admin','administrador'])` fuera de SuperAdmin; el Admin recibe sus capacidades vía permisos del seeder.
- Los permisos "propios" (preventa propia, turno propio, vendedor propio) se imponen en servidor, no solo ocultando botones.
- `authorize()` de los FormRequest sensibles debe usar `$this->user()->can(...)` además del middleware de ruta.
- Reportes: dividir en `reportes.ver` (ventas/ingresos), `reportes.financieros` (rentabilidad, créditos), `reportes.inventario`, `reportes.vendedores`.
- Rutas sin permiso hoy (`panel-principal`, `consultor-precios*`) deben llevar permiso o filtrar datos sensibles (costos).
- Métodos de pago hoy van por `role:SuperAdmin`: se acepta (D4); alternativa válida: usar `permission:metodos_pago.*` solo asignado a SuperAdmin.
