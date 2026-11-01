# Reportes y definición de "venta neta"

## 1. Definiciones únicas (una sola implementación, p. ej. `VentaNetaQuery` / scope en `Venta`)
- **Venta válida**: `estado ≠ 'anulada'`.
- **Venta neta** de una factura = `total` − Σ devoluciones de esa factura (por monto).
- **Subtotal neto** (sin IVA) neto de devoluciones: base para comisiones y rentabilidad.
- **Fecha de la venta** = `fecha_emision`; **fecha de la devolución/anulación** = su propia fecha (el efecto en caja y en el período de reporte cae en la fecha del evento).
- **Flujo de caja real** = pagos de ventas + abonos CXC + ingresos − vuelto − reembolsos − egresos, por fecha de movimiento.

Todos los reportes, el dashboard y el Corte X/Z **deben** usar estas definiciones. Prohibido filtrar `estado` a mano en cada query.

## 2. Reportes y contenido esperado

| Reporte | Contenido | Permiso |
|---|---|---|
| Ventas / Ingresos | Venta neta por período, caja, cajero, método y moneda. Muestra bruto, devoluciones, anulaciones y neto. | `reportes.ver` |
| Flujo de caja | Flujo real por método (incluye abonos CXC, vuelto, reembolsos). | `reportes.ver` |
| Rentabilidad | Margen sobre venta neta; costo congelado en el renglón; opcional restar comisiones. | `reportes.financieros` |
| Créditos CXC/CXP | Saldos `pendiente + parcial` con antigüedad. El Dashboard usa **los mismos** estados. | `reportes.financieros` |
| Inventario / Stock | Valoración, mínimos, stock por almacén. | `reportes.inventario` |
| Vendedores y comisiones | Venta neta, comisión ganada, pagada, pendiente por vendedor (ver `vendedores-comisiones.md`). Su total de ventas **cuadra** con Ventas/Ingresos filtrando por vendedor. | `reportes.vendedores` |
| Cuadre diario (nuevo) | Por caja/turno: ventas, devoluciones, anulaciones, abonos, vuelto, esperado vs contado. | `cajas.arqueo` |
| Devoluciones y anulaciones (nuevo) | Listado con motivo, usuario, monto, factura original. | `ventas.anular` |
| Libro de ventas (nuevo) | Base imponible, IVA, exento por factura. **Sin IGTF** (D5). | `reportes.financieros` |

## 3. Criterios de aceptación globales
1. Para un mismo rango, `Σ ventas netas por vendedor + ventas sin vendedor = venta neta total`.
2. El efectivo esperado de un turno = lo que muestra el flujo de caja de ese turno.
3. Dashboard (CXC, CXP, ventas del día) = reportes correspondientes.
4. Filtros por empresa siempre; clientes y métodos de pago globales (D4) no se filtran por empresa pero los documentos que los usan sí.
5. Vista previa, PDF y CSV muestran las mismas cifras.
