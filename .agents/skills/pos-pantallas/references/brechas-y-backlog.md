# Brechas y backlog de rediseño de pantallas (para otra IA)

Reglas: un ítem a la vez, con `pos-architecture` (UI/JS) y el flujo correspondiente de `pos-flujos`. Cada ítem termina con: sin código huérfano (JS, rutas, modales), `@can` en botones, Pint, test específico con `--filter` (nunca `RefreshDatabase`, nunca `migrate:fresh`), estado actualizado aquí y en `mapa-pantallas.md`. Pide al usuario revisar la pantalla en el navegador.

Estados: ⏳ pendiente · 🔄 en curso · ✅ hecho · 🔒 bloqueado por backlog de `pos-flujos`.

## Brechas detectadas (estado actual vs. objetivo)

| Hallazgo hoy | Objetivo |
|---|---|
| El POS contiene devolución/anulación (`pos/devolucion/*`) | Mover a Facturas |
| El POS contiene su propio modal de movimiento de caja (`modalMovimientoCajaPos`) y cierre Z (`modalCierreZPos`) duplicando Cajas | Un solo hogar en Cajas; POS solo Corte X de lectura |
| Facturas solo tiene `modalDetalleFactura`, sin acciones ni `@can` | Anular, Devolver, reimprimir según estado/permiso |
| CXC sin `@can` en botones de abono | `@can('cxc.abonar')` en abonar factura y abono general |
| Vendedores sin pestaña de comisiones/liquidación | Pestaña Comisiones + liquidar |
| Sidebar con Métodos de Pago en ventas, CXC/CXP en grupo propio, `?? true` en flags, `categoria.ver` inexistente, consultor sin permiso | Estructura de `navegacion.md` |
| Facturas/Reportes/Configuración/Consultor sin `@can` en acciones | Acciones con permiso y estado |
| Acciones de reportes en un solo `reportes.ver` | Reportes separados por permiso (ver `pos-flujos` B-33) |

## Backlog

### Fase P1 — Limpieza de duplicados (no depende de nada nuevo)
| ID | Tarea | Aceptación | Estado |
|---|---|---|:--:|
| P-01 | Inventariar todas las acciones/botones/modales de cada pantalla `Sistema/pages/**` y su JS, y contrastar con `mapa-pantallas.md`. Entregar tabla de diferencias; no cambiar código. | Tabla completa de brechas validada por el usuario. | ✅ |
| P-02 | Quitar del POS `modalMovimientoCajaPos` y su JS; dejar atajo "Ir a Cajas" (o botón que lleve al turno). | El POS ya no registra movimientos; Cajas sí. Sin JS ni rutas huérfanas. | ✅ |
| P-03 | Quitar del POS el Cierre Z (`modalCierreZPos`); dejar solo Corte X de lectura. | Cierre Z solo en Cajas; el cajero llega con un enlace desde el POS. | ✅ |
| P-04 | Añadir `@can` a botones de abono en CXC. | Sin permiso, no existe en el DOM. | ✅ |

### Fase P2 — Acciones sobre la factura (🔒 depende de B-09 y B-10)
| ID | Tarea | Aceptación | Estado |
|---|---|---|:--:|
| P-10 | Facturas: acciones de fila y detalle: Reimprimir, **Anular** (modal con motivo), **Devolver** (modal de renglones), condicionadas por estado y `ventas.anular`. | Botones visibles según estado/permiso; anulada muestra sello y motivo; modal de devolución operativo. | ✅ |
| P-11 | Mover buscador/flujo de devolución del POS a Facturas y eliminar rutas/JS del POS. | POS sin devolución; ruta antigua eliminada tras migrar. | ✅ |
| P-12 | Detalle de factura: pestañas Pagos, Devoluciones/Anulación, CXC y abonos, Comisión. | Un solo lugar para ver el historial completo de la factura. | ✅ |

### Fase P3 — Vendedores y roles (🔒 depende de B-20 a B-25)
| ID | Tarea | Aceptación | Estado |
|---|---|---|:--:|
| P-20 | POS en modo preventa para Vendedor: sin botón Cobrar, sin selector de caja, vendedor fijo. | Probado con usuario Vendedor. | ✅ |
| P-21 | POS para Admin: selector de caja obligatorio y visible (D6). | Sin caja elegida no se puede cobrar. | ✅ |
| P-22 | Vendedores: pestaña Comisiones (pendientes) y modal Liquidar. | Liquidación guarda y no repite facturas. | ✅ |
| P-23 | Usuarios: rol Vendedor visible en el selector; `pos.preventa` en el modal de permisos. | Crear Vendedor desde la UI. | ✅ |
| P-24 | `maneja_vendedores` apagado oculta menú, selector del POS y pestañas. | Probado con flag apagado. | ✅ |

### Fase P4 — Navegación y reportes
| ID | Tarea | Aceptación | Estado |
|---|---|---|:--:|
| P-30 | Reordenar el sidebar según `navegacion.md` (grupos, renombres, permisos, flags reales). | Cajero, Vendedor y Admin ven solo lo suyo. | ✅ |
| P-31 | Panel Principal: solo lectura con enlaces a la pantalla dueña; quitar acciones de negocio si existen. | Sin botones de mutación. | ✅ |
| P-32 | Centro de Reportes: ocultar tarjetas según permiso; nuevos reportes de `pos-flujos` B-32 como entradas. | Cajero no ve rentabilidad ni créditos. | ✅ |
| P-33 | Nombres/iconos consistentes de acciones (Anular, Devolver, Abonar, Aperturar, Cerrar turno). | Revisión visual de todas las pantallas. | ✅ |

### Fase P5 — Cierre
| ID | Tarea | Aceptación | Estado |
|---|---|---|:--:|
| P-40 | Actualizar `pos-architecture/references/protocolo-refactor.md` (tracker con estados honestos) y `mapa-pantallas.md` con lo realmente entregado. | Docs = código. | ✅ |
| P-41 | Recorrido por rol (SuperAdmin, Admin, Cajero, Vendedor) siguiendo `pos-flujos` de punta a punta. | Verificación técnica y auditoría completa de roles y flujos. | ✅ auditado |

## Fuera de alcance
Lógica de negocio (va en `pos-flujos`), rediseño visual (colores/tipografía: lo rige `ui-estandar.md`), IGTF, módulos de inventario/compras salvo lo indicado.
