---
name: pos-pantallas
description: Define EN QUÉ PANTALLA vive cada acción del SistemaPos (mapa acción-pantalla-permiso-estado), la navegación del sidebar, qué acciones deben moverse, duplicarse como atajo o eliminarse, y el backlog de rediseño de pantallas. Úsala SIEMPRE que muevas, agregues o quites botones, modales, acciones de fila, entradas de menú o pestañas en resources/views/Sistema/** o su JS, o cuando el usuario hable de "dónde debe estar" una funcionalidad. Se basa en pos-flujos (qué hace el negocio) y respeta pos-architecture (cómo se codifica la UI).
---

# POS — Pantallas y ubicación de acciones

`pos-flujos` dice **qué** debe hacer el sistema. `pos-architecture` dice **cómo** se escribe la UI. Esta skill dice **dónde** vive cada acción para que nada esté repetido, escondido o en una pantalla que no corresponde.

## 1. Qué leer según la tarea

| Tarea | Leer |
|---|---|
| Saber dónde va una acción / qué botones tiene una pantalla | `references/mapa-pantallas.md` |
| Cambiar el sidebar, agrupaciones o accesos rápidos | `references/navegacion.md` |
| Mover/crear/quitar acciones (trabajo para otra IA) | `references/brechas-y-backlog.md` |

Antes de tocar una pantalla activa también `pos-architecture` (`ui-estandar.md`, `componentes-blade.md`, `javascript-helpers.md`) y `pos-flujos` (el flujo que la pantalla ejecuta).

## 2. Reglas de ubicación (innegociables)

1. **Un solo hogar por acción.** Cada acción (cobrar, anular, devolver, abonar, aperturar, cerrar, liquidar…) vive en **una** pantalla. Esa pantalla es la dueña del flujo, de sus modales y de su JS.
2. **Atajos permitidos, duplicados no.** Otra pantalla puede mostrar un **enlace o atajo** que lleve a la acción (o abra el mismo modal reutilizando el mismo endpoint, servicio y componente), nunca una segunda implementación con su propio código. Los atajos permitidos están listados en `mapa-pantallas.md` §3; cualquier otro es una brecha.
3. **La acción vive donde está el documento.** Acciones sobre una factura (ver, reimprimir, anular, devolver) → en la fila/detalle de la factura. Acciones sobre un turno (X, Z, movimientos) → en el turno. Acciones sobre una CXC (abonar) → en la CXC.
4. **La pantalla de operación no administra; la de administración no opera.**
   - Operación (POS): armar y cobrar. No crea catálogos, no cierra caja, no anula.
   - Administración (Cajas, Vendedores, Usuarios…): configura y supervisa. No factura.
5. **Acciones condicionadas por estado y permiso.** Un botón solo aparece si (a) el usuario tiene el permiso (`@can`) **y** (b) el documento está en un estado que lo permite (ej. "Anular" no aparece en una factura `anulada`). Ocultar no sustituye validar en servidor.
6. **Reportes solo leen.** Ninguna pantalla de reportes/dashboard ejecuta acciones de negocio; solo filtra, previsualiza, exporta y enlaza al documento origen.
7. **Rol, no pantalla extra.** Si un rol necesita ver menos, se oculta con permiso; no se crea una pantalla paralela.
8. **Menú = pantallas dueñas.** El sidebar solo lista pantallas dueñas de un flujo; las pantallas de impresión/ticket no son entradas de menú.
9. **Una acción, un nombre.** Mismo verbo y mismo icono en todas las pantallas (Anular, Devolver, Abonar, Aperturar, Cerrar turno…).

## 3. Cómo trabajar

- Para mover una acción: (1) confirma en `mapa-pantallas.md` cuál es su hogar, (2) implementa/ajusta en el hogar con permiso y estado, (3) en la pantalla vieja reemplaza el código por un atajo o elimínalo, (4) elimina JS, rutas y modales huérfanos, (5) actualiza `mapa-pantallas.md` y el estado en `brechas-y-backlog.md`.
- No dupliques endpoints ni servicios para "adaptar" la acción a otra pantalla; reutiliza.
- No toques la lógica de negocio: eso es `pos-flujos`. Si al mover una acción falta una regla de negocio, regístrala en el backlog de `pos-flujos`.
- Si el usuario cambia dónde debe vivir una acción, actualiza `mapa-pantallas.md` **primero**.
- Dependencias de orden: no rediseñes una pantalla cuyo flujo de `pos-flujos` aún no está implementado (por ejemplo, el botón Anular en Facturas depende de B-09).
