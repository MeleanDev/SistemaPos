---
name: pos-flujos
description: Define CÓMO DEBE FUNCIONAR el negocio del SistemaPos - ciclo de vida de la factura (preventa, cobro, anulación, devolución), cajas y turnos, vendedores y comisiones, roles y quién puede qué, cuentas por cobrar y reportes. Úsala SIEMPRE que toques ventas, POS, facturas, cajas, turnos, vendedores, comisiones, devoluciones, anulaciones, CXC, roles/permisos de operación o reportes. Complementa a pos-architecture (que define CÓMO se escribe el código); esta define QUÉ debe hacer el sistema y el backlog de correcciones.
---

# POS — Flujos de negocio (fuente de verdad)

Esta skill es la **especificación funcional**. `pos-architecture` manda en el *cómo se codifica*; `pos-flujos` manda en el *qué debe pasar*. Si el código contradice esta skill, **el código está mal** (salvo que el usuario diga lo contrario) y se corrige.

## 1. Qué leer según la tarea

| Tarea | Leer |
|---|---|
| Venta, POS, preventa, factura, anulación, devolución | `references/flujo-factura.md` |
| Cajas, turnos, Corte X, Cierre Z, movimientos, arqueo | `references/cajas-turnos.md` |
| Vendedores, comisiones | `references/vendedores-comisiones.md` |
| Roles, permisos, quién puede qué | `references/roles-acciones.md` |
| Reportes, dashboard, totales | `references/reportes.md` |
| Implementar correcciones (trabajo para otra IA) | `references/backlog.md` (ordenado, con criterios de aceptación) |

Antes de escribir código, activa también `pos-architecture` y lee la referencia técnica que corresponda.

## 2. Decisiones de negocio cerradas (no volver a preguntar)

| # | Decisión |
|---|---|
| D1 | **Vendedor = solo preventa.** No cobra, no factura, no anula. |
| D2 | **La comisión se gana al FACTURAR** (no al cobrar), aunque la venta sea a crédito. Base: `subtotal_neto` (sin IVA). |
| D3 | **Anular y devolver se puede en cualquier momento** (incluso de turnos ya cerrados), con permiso `ventas.anular`, motivo obligatorio y auditoría. Nada se borra. |
| D4 | **Clientes y Métodos de pago son GLOBALES** para todas las empresas. Es una excepción documentada a la regla `empresa_id`; no se "arregla". |
| D5 | **IGTF NO se implementa por ahora** (en desarrollo). No calcularlo, no guardarlo, no prometerlo en documentación. Los campos `aplica_igtf` existen pero están inertes. |
| D6 | **Admin/SuperAdmin pueden facturar sin turno propio, pero DEBEN elegir la caja** en la que facturan. `caja_id` y `caja_turno_id` nunca quedan `null` en una venta. |
| D7 | (Propuesta del analista, vigente salvo que el usuario la cambie) **El cajero cierra solo su turno y a ciegas** (cuenta el efectivo antes de ver el esperado). Admin/SuperAdmin cierran cualquiera y ven el esperado. |

## 3. Principios transversales (aplican a todo flujo)

1. **Nada se borra**: facturas, devoluciones, abonos y movimientos son inmutables; se corrigen con un documento contrario (anulación, devolución, movimiento inverso).
2. **Todo movimiento de dinero pertenece a un turno abierto.** Si una operación mueve dinero y no hay turno abierto desde el cual registrarla → se rechaza con mensaje claro.
3. **Una sola fuente de "venta neta"** para todos los reportes (ver `reportes.md`).
4. **El servidor es la autoridad**: precio, descuento, stock, método de pago, almacén, serial y permisos se validan en backend, nunca se confía en lo que manda el navegador.
5. **Atomicidad**: cada operación (facturar, anular, devolver, abonar, cerrar turno) en una `DB::transaction` con `lockForUpdate` sobre lo que cambia.
6. **Sin fallbacks silenciosos** (`?? 1` en empresa/usuario/almacén está prohibido): si falta contexto, error.
7. **Permisos, no nombres de rol**: usar `can('modulo.accion')`. Solo `SuperAdmin` se permite por rol (raíz global).
8. **Auditoría**: toda anulación/devolución/cierre guarda quién, cuándo y por qué.

## 4. Glosario

- **Preventa / venta en espera**: carrito guardado por un vendedor (o cajero), sin stock ni dinero comprometido.
- **Factura / Venta**: documento emitido y cobrado/acreditado. Estados: `completada`, `devuelta_parcial`, `devuelta_total`, `anulada`.
- **Turno**: sesión de una caja con un responsable; contiene todo el dinero que entra/sale.
- **Venta neta**: ventas en estado ≠ `anulada`, menos devoluciones, definidas en `reportes.md`.
- **Comisión ganada**: la de una factura emitida y no anulada, ajustada por devoluciones.

## 5. Cómo trabajar con esta skill

- Para implementar: toma el siguiente ítem de `references/backlog.md`, respeta su criterio de aceptación, escribe/ajusta tests (usar BD de pruebas aislada, ver B-00) y corre Pint.
- Si descubres una incongruencia nueva, agrégala al backlog; no la "arregles" fuera del alcance.
- Si el usuario cambia una decisión, actualiza la tabla §2 **primero** y luego los references afectados.
- Nunca `migrate:fresh` ni sembrar datos de prueba sin permiso explícito.
