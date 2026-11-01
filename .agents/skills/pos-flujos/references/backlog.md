# Backlog de implementación (para otra IA)

Reglas: un ítem a la vez, en orden. Antes de cada uno: activar `pos-architecture` + leer el reference de `pos-flujos` indicado. Después: Pint + test específico (`php artisan test --compact --filter=...`), y marcar el estado aquí. **No `migrate:fresh`, no sembrar datos, no cambiar dependencias.** Migraciones nuevas solo aditivas.

Estados: ⏳ pendiente · 🔄 en curso · ✅ hecho.

## Fase 0 — Seguridad del trabajo
| ID | Tarea | Aceptación | Estado |
|---|---|---|:--:|
| B-00 | Aislar BD de tests: `.env.testing` o SQLite en memoria en `phpunit.xml`; pasar a `RefreshDatabase` solo sobre BD de test. **Preguntar al usuario** antes de tocar `phpunit.xml`. | Los tests siguen usando la BD real (`mysql` `datavault`) con `DatabaseTransactions` (sin `RefreshDatabase`, `migrate:fresh`, `db:wipe` ni seeders en tests). | ✅ aceptado con riesgo |

## Fase 1 — Dinero e integridad (ref: `flujo-factura.md`, `cajas-turnos.md`)
| ID | Tarea | Aceptación | Estado |
|---|---|---|:--:|
| B-01 | `metodos_pago.es_efectivo` (bool, migración aditiva, default por nombre actual) + UI de métodos de pago. | Arqueo usa el flag, no el texto del nombre. | ✅ |
| B-02 | Registrar **vuelto** como `egreso` en `caja_movimientos` (con método, moneda, documento ligado a la venta). | Test: venta pagada 20 USD de 15 USD → esperado del turno sube solo 15. | ✅ |
| B-03 | Reescribir `CajaClass::calcularReporteTurno` con la fórmula de `cajas-turnos.md` §4; quitar `max(0,…)`; por moneda. | Test con ventas, vuelto, abono, devolución y egreso cuadra al centavo. | ✅ |
| B-04 | Cierre Z: cajero solo su turno y a ciegas; admin cualquiera. Ajustar `caja.js` y endpoint. | Cajero no puede cerrar turno ajeno (403); la respuesta previa al conteo no incluye el esperado para cajero. | ✅ |
| B-05 | Facturar: `caja_turno_id` obligatorio; admin elige caja abierta; sin turno → error. Quitar la rama `caja = null`. | Ninguna venta con `caja_turno_id` null; test admin con 0, 1 y 2 cajas abiertas. | ✅ |
| B-06 | Quitar fallbacks `?? 1` (empresa, user, almacén) en `VentaClass`/`KardexClass`. | Sin contexto → excepción clara. | ✅ |
| B-07 | Validaciones de servidor en venta: precio de lista, descuento solo con `ventas.descuentos` (+ tope), almacén/serial/moto/método de pago válidos, stock suficiente (Kardex deja de recortar a 0; lanza excepción). `GuardarVentaRequest` + servicio. | Tests: precio manipulado → 422; sin permiso de descuento → 403/422; sin stock → error y nada persiste. | ⏳ |
| B-08 | Correlativos con lock: tabla `secuencias` por empresa/tipo (`venta`, `control`, `devolucion`, `anulacion`) + únicos `(empresa_id, codigo)` y `(empresa_id, numero_control)`. Migración que **no falle** con duplicados existentes (detectar y reportar al usuario antes). | Test concurrente/secuencial sin duplicados; sin recorrer toda la tabla. | ⏳ |
| B-09 | **Anulación de factura** (`flujo-factura.md` §4): migración (`anulada_por_id`, `anulada_at`, `motivo_anulacion`), servicio, ruta `POST facturas/{id}/anular` con `permission:ventas.anular`, FormRequest, botón en Facturas con `@can`, sello ANULADA en impresión. | Test: stock repuesto, CXC anulada, comisión 0, egreso en turno actual, no se puede anular dos veces. | ⏳ |
| B-10 | **Devolución correcta** (§5): monto neto proporcional (descuento/IVA), `tipo_reembolso` con método, egreso en turno actual, turno abierto obligatorio, recálculo de comisión por monto. | Test con descuento: reembolso = neto proporcional; comisión recalculada. | ⏳ |
| B-11 | **Abono CXC** actualiza `ventas.monto_pagado/saldo_pendiente`, exige turno abierto, rechaza sobrepago. | Test: factura refleja el abono; sin turno → error. | ⏳ |
| B-12 | Lock (`lockForUpdate`) en motos/seriales/ventas durante facturar, anular y devolver. | Segundo intento sobre la misma moto falla. | ⏳ |
| B-13 | FK `caja_turnos.user_id` sin `cascade` (pasar a `restrict`); evitar borrar usuarios con turnos. | Borrar usuario con turnos → error controlado. | ⏳ |

## Fase 2 — Roles y vendedores (ref: `vendedores-comisiones.md`, `roles-acciones.md`)
| ID | Tarea | Aceptación | Estado |
|---|---|---|:--:|
| B-20 | Añadir `pos.preventa` a `permisos_modulos` (padre `pos.acceso`) y al seeder; eliminar `cajas.administrar_todas`. | Aparece en el modal de permisos. | ⏳ |
| B-21 | Rol `Vendedor` asignable desde UI (`UsuarioClass::obtenerRolesYEmpresas`) con permisos base. | Crear usuario Vendedor desde pantalla y que solo pueda armar preventas. | ⏳ |
| B-22 | Reemplazar `hasRole([...'admin'...])` por permisos (excepto SuperAdmin) en `VentaClass`, `CajaClass`, `PanelPrincipalClass`; `esVendedor` = `pos.preventa` ∧ ¬`ventas.crear`. Ajustar seeder del rol Admin. | Tests de permisos por rol. | ⏳ |
| B-23 | Preventas con dueño: vendedor solo ve/elimina las suyas; `vendedor_id` fijo al recuperar. | Test de acceso cruzado. | ⏳ |
| B-24 | `unique(empresa_id, user_id)` en `vendedores` (con chequeo previo de duplicados) y respeto de `maneja_vendedores` en rutas/POS/reportes. | Flag apagado → rutas de vendedores 404/403. | ⏳ |
| B-25 | `comision_estado` + liquidación de comisiones (`vendedores.liquidar`), egreso opcional en caja. | Test: liquidar marca facturas como pagadas y no se liquidan dos veces. | ⏳ |
| B-26 | Rol/permiso de vendedor propio en reportes: filtrar por `vendedor_id` propio siempre que no tenga `reportes.vendedores` global. | Vendedor con `reportes.ver` no ve ajenos. | ⏳ |

## Fase 3 — Reportes (ref: `reportes.md`)
| ID | Tarea | Aceptación | Estado |
|---|---|---|:--:|
| B-30 | Crear fuente única de venta neta (scope/clase) y migrar Panel, Ingresos, Rentabilidad, Vendedores (UI/PDF/CSV) y Z. | Criterios globales 1-3 de `reportes.md` con tests. | ⏳ |
| B-31 | Panel: CXC/CXP incluyen `pendiente + parcial`. | Mismo número que reporte de Créditos. | ⏳ |
| B-32 | Nuevos reportes: Cuadre diario, Devoluciones/Anulaciones, Flujo de caja, Libro de ventas (sin IGTF). | Vista previa, PDF y CSV coinciden. | ⏳ |
| B-33 | Dividir permisos de reportes (`financieros`, `inventario`) en seeder + `UsuarioClass` + sidebar + rutas. | Cajero sin acceso a rentabilidad. | ⏳ |

## Fase 4 — Estándares y documentación
| ID | Tarea | Aceptación | Estado |
|---|---|---|:--:|
| B-40 | Limpiar `setup.js` (1 `$.ajax`, 3 `window.x=`, comentarios), `dashboard.js` (`$.ajax`), `usuario.js` (`window.x=`). | Checklist JS de `pos-architecture` en verde. | ⏳ |
| B-41 | Permisos en `panel-principal` y `consultor-precios*`; ocultar costos al que no tenga permiso. | Rutas con middleware. | ⏳ |
| B-42 | Corregir `pos-architecture`: `dominio-modulos.md` (quitar IGTF-en-POS, añadir anulación, vendedor/roles reales), `permisos.md` y el tracker de `protocolo-refactor.md` (estados honestos). | Docs coinciden con el código. | ⏳ |
| B-43 | Tests de regresión por flujo (factura, anulación, devolución, abono, arqueo, comisión, permisos por rol). | Suite verde; pedir al usuario correr la completa. | ⏳ |

## Fuera de alcance (no hacer)
- IGTF (D5). Clientes/métodos de pago por empresa (D4). Que el vendedor cobre (D1).
