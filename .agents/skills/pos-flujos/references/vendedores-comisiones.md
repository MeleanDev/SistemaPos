# Vendedores y comisiones

## 1. Qué es un Vendedor (D1)
Una persona que **arma preventas**. Puede ser solo un registro (asesor sin acceso al sistema) o estar vinculado a un usuario.

- Catálogo `vendedores` por empresa: tipo/documento, nombre, contacto, `comision_porcentaje`, estado, `user_id` opcional.
- `unique(empresa_id, documento)` y `unique(empresa_id, user_id)` (un usuario = un vendedor por empresa).
- Solo usuarios activos de la empresa, no SuperAdmin, pueden vincularse.
- El flag `maneja_vendedores` de la empresa **sí** gobierna: apagado → no hay menú, ni rutas de vendedores/reporte de vendedores, ni selector de vendedor en POS.

## 2. Usuario "Vendedor"
- Rol `Vendedor` visible y asignable desde Usuarios (junto a Admin y Operador). Permisos base: `pos.acceso`, `pos.preventa`, `reportes.vendedores` (solo lo propio).
- **No** tiene `ventas.crear`, `cajas.*`, `ventas.anular`, `ventas.descuentos`.
- Permiso nuevo `pos.preventa` debe existir en el seeder **y** en `permisos_modulos` de `UsuarioClass`, con su padre `pos.acceso`.
- Se elimina `cajas.administrar_todas` (no existe). "Es vendedor" se decide por **un solo criterio**: tiene `pos.preventa` y no tiene `ventas.crear`.

## 3. Atribución
- En la preventa el vendedor queda asignado (el propio si el usuario está vinculado; selector si lo arma un cajero/admin).
- Al facturar se hereda el `vendedor_id` de la preventa. Facturación directa sin preventa: el cajero elige vendedor (si `maneja_vendedores`) o queda sin vendedor (sin comisión).

## 4. Comisión (D2)
- **Se gana al facturar.** Se congela en la venta: `comision_porcentaje`, `comision_monto_usd`, `comision_monto_bs` (con la tasa de la venta).
- Base: `subtotal_neto_usd` (sin IVA, después de descuentos).
- **Devolución**: nueva comisión = `comision_porcentaje × (subtotal_neto − neto devuelto proporcional por monto)`.
- **Anulación**: comisión = 0.
- Cambiar el % del vendedor **no** altera facturas ya emitidas.

## 5. Estado de pago de la comisión
Aunque se gana al facturar, hay que saber si ya se pagó:
- Campo `comision_estado ∈ {pendiente, pagada}` y `comision_liquidacion_id` en la venta (o tabla `liquidaciones_comision` con detalle).
- **Liquidar**: permiso `vendedores.liquidar` (Admin). Selecciona vendedor + rango; suma comisiones `pendiente` de facturas no anuladas; genera liquidación inmutable y, si se paga en efectivo, un `egreso` en el turno abierto.
- Una comisión de factura anulada/devuelta después de liquidada genera **saldo a favor/descuento** en la siguiente liquidación (no se reescribe la liquidación pasada).

## 6. Visibilidad
- Vendedor: solo ve sus ventas y comisiones (filtro por su `vendedor_id`, no por tener o no `reportes.ver`).
- Admin / `reportes.vendedores`: todos.
- Reporte de vendedores usa la **venta neta** (ver `reportes.md`) y muestra: ventas netas, comisión ganada, pagada, pendiente.
