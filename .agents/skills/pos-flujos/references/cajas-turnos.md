# Cajas y turnos

## 1. Reglas de oro
- Una caja física = a lo sumo **1 turno abierto**. Un usuario = a lo sumo **1 turno abierto por empresa**. Reforzar con transacción + lock (y, si el motor lo permite, índice único sobre una columna generada/`abierta`).
- Todo dinero (ventas, vuelto, abonos CXC, devoluciones, anulaciones, comisiones pagadas, ingresos/egresos menores) se asienta en un turno abierto.
- Un turno cerrado es inmutable. Correcciones posteriores van al turno abierto actual con referencia al documento original.

## 2. Apertura
- Permiso `cajas.aperturar` (Admin/SuperAdmin; configurable a otros). Asigna cajero (usuario activo de la empresa, no SuperAdmin) y fondo inicial USD/Bs.
- El Admin que factura sin turno propio **elige** una caja ya abierta (D6); no abre turnos implícitamente.

## 3. Movimientos de dinero del turno
Tabla `caja_movimientos` (`ingreso|egreso`) es el **libro único** de efectivo no originado en una venta: ingresos/egresos menores, **vuelto**, reembolsos de devolución/anulación, pago de comisiones.

Cada movimiento: turno, usuario, tipo, método de pago, moneda, monto en su moneda **y** equivalente, concepto, referencia a documento (`documento_tipo`, `documento_id`).

Reglas: egreso no puede exceder el efectivo disponible de esa moneda en el turno (aviso/bloqueo configurable). Monto USD y Bs deben ser coherentes con la tasa del día.

## 4. Fórmula del efectivo esperado (por moneda, sin `max(0, …)`)

```
esperado = apertura
         + Σ pagos en efectivo de ventas del turno (monto_origen por moneda)
         + Σ abonos CXC en efectivo del turno
         + Σ movimientos ingreso en efectivo
         − Σ vuelto entregado (en la moneda en que se entregó)
         − Σ reembolsos en efectivo (devoluciones/anulaciones)
         − Σ movimientos egreso en efectivo
```

- "Efectivo" se determina por un campo `es_efectivo` en `metodos_pago` (no por coincidencia de texto en el nombre). Los métodos de pago son globales (D4): el flag también.
- Los métodos no efectivo (transferencia, punto, zelle…) se concilian por separado: se listan por método/moneda y no entran al conteo físico.
- Mostrar el esperado negativo si ocurre (señal de error), no ocultarlo.

## 5. Corte X (parcial)
- `cajas.arqueo`. Cajero: solo su turno. Admin: cualquiera. No cierra nada.
- Muestra: apertura, ventas (cantidad, bruto, devoluciones, neto), pagos por método/moneda, abonos CXC, movimientos, vuelto, reembolsos, efectivo esperado.

## 6. Cierre Z (D7)
- `cajas.cerrar`.
- **Cajero**: cierra solo su turno y **a ciegas** — ingresa el conteo físico USD/Bs antes de ver el esperado; después se muestra diferencia (Exacto/Sobrante/Faltante).
- **Admin/SuperAdmin**: cierran cualquier turno y pueden ver el esperado.
- Antes de cerrar avisar (sin bloquear) si hay preventas abiertas del turno; bloquear si hay operaciones en curso.
- Al cerrar guardar: conteo, esperado, diferencia por moneda, `cerrado_por_id`, totales.
- Reimpresión Z/X disponible siempre; el Z cerrado no se recalcula.

## 7. Cajas (catálogo)
- CRUD por empresa; desactivar solo sin turno abierto.
- Borrar un usuario **no** debe borrar sus turnos (FK `restrict`/`nullOnDelete`, nunca `cascade`).
