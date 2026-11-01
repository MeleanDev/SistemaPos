---
name: pos-architecture
description: Estándares de arquitectura, UI ejecutiva, componentes Blade, helpers JS, backend multi-empresa y permisos del sistema POS (Laravel + Blade + Bootstrap 5 + jQuery). Úsala SIEMPRE que crees, modifiques, revises o refactorices cualquier módulo, vista, JS, controlador, request, servicio o ruta de este proyecto. Para pantallas de resources/views/Sistema/** gana sobre cualquier otra skill de diseño o frontend.
---

# POS — Guía de arquitectura y estándares

Esta skill es corta a propósito. Lee las reglas de abajo y **carga solo la referencia que necesites**.

## 1. Qué leer según la tarea

| Si vas a… | Lee |
|---|---|
| Escribir o cambiar Blade / estilos / modales | `references/ui-estandar.md` y `references/componentes-blade.md` |
| Escribir o cambiar JS de un módulo | `references/javascript-helpers.md` |
| Escribir controlador, request, servicio o ruta | `references/backend.md` y `references/permisos.md` |
| Entender el negocio (POS, kardex, cajas, recepciones, comisiones) | `references/dominio-modulos.md` |
| Refactorizar o auditar un módulo / ver su estado | `references/protocolo-refactor.md` |
| Saber **qué debe hacer** el negocio (factura, anulación, caja, vendedores, roles, reportes) | skill `pos-flujos` (gana sobre `dominio-modulos.md` si difieren) |
| Decidir **en qué pantalla vive** una acción / mover o quitar botones, modales o entradas de menú | skill `pos-pantallas` |

## 2. Precedencia entre skills (resuelve conflictos)

1. Lo que pida el usuario en la conversación.
2. `AGENTS.md` y **esta skill** → mandan en todo `resources/views/Sistema/**`, `public/estilos/jsPropios/**`, `app/**` y `routes/**`.
3. `laravel-best-practices` y `testing-best-practices` → apoyo para PHP y tests. Si contradicen esta skill (por ejemplo en estructura de carpetas o validación), gana esta skill.
4. `design-taste-frontend`, `impeccable`, `high-end-visual-design` → **solo** para landing pages o sitios públicos de marketing. **No las uses en `Sistema/**`**: imponen una dirección de arte distinta (fuentes, paletas, "anti-slop") que choca con el estándar ejecutivo del POS.
5. `tailwindcss-development` → **no aplica** a `Sistema/**` (usa Bootstrap 5). Solo si el archivo que editas ya está escrito con clases Tailwind.
6. `infer-conventions` → únicamente si el usuario lo pide de forma explícita.

## 3. Stack real (no asumas otro)
Laravel + Blade · Bootstrap 5 + CSS propio · jQuery · DataTables (yajra, `datatables()->of()`) · Select2 · SweetAlert2 · Chart.js · FontAwesome 6 · DomPDF · Spatie Permission · Pest. El JS propio vive en `public/estilos/jsPropios/` y se carga con `<script src=…>` (sin Vite, sin módulos ES). Los helpers globales están en `jsPropios/components/`.

## 4. Reglas innegociables

**UI**
1. Sin fondos grises (`bg-light`, `alert-light`, `table-light`): contenedores `bg-white border rounded-4 shadow-xs`.
2. Cédulas, RIFs y nombres de sede/empresa **sin caja ni borde**: texto tipográfico con icono.
3. Switches/toggles en listas abiertas, no en sub-tarjetas con borde.
4. Todo redondeado (`rounded-3/4/pill`); tipografía Plus Jakarta Sans/Inter.
5. Reutiliza `<x-modal>`, `<x-select2>`, `<x-input>`, `<x-button>`, `<x-btn-action>`, `<x-section-header>`, `<x-table-dynamic>`, `<x-datatable>`, `<x-search-filter>`; no escribas modales ni selects a mano.

**JavaScript**
6. Módulos en `jsPropios/*.js`: **cero comentarios**, **sin `window.x = x`**, sin `$.ajax` directo.
7. Usa los helpers con sus **opciones reales** (`enviarFormulario({ form, url, isEditar, modalSelector, tablaSelector })`, etc.). Las opciones `formId`, `tablaId`, `modalId`, `esActualizacion`, `funcionActualizar` **no existen**. Detalle en `javascript-helpers.md`.
8. IDs de modal generados por `<x-modal>`: `#modalXTitulo`, `#modalXSubtitulo`, `#modalXIcono`, `#modalXBtnGuardar`, `#modalXTextoGuardar`.

**Backend**
9. Multi-empresa: toda consulta, regla y reactivación se limita a `$this->obtenerEmpresaId()`. No redefinas `obtenerEmpresaId()`.
10. Validación mutante en `FormRequest` (reglas en array, mensajes en español, extiende `BaseRequest`); nada de `$request->validate()` inline.
11. `index()` devuelve solo la vista; los datos llegan por endpoints JSON/AJAX.
12. Cada ruta con `->middleware('permission:modulo.accion')` individual; botones de mutación dentro de `@can`.

**Operación**
13. **Nunca** `php artisan migrate:fresh` ni resetear la BD sin permiso explícito del usuario. Nunca sembrar usuarios/empresas de prueba.
14. Tras tocar PHP: `vendor/bin/pint --format agent` y el test más específico (`php artisan test --compact --filter=…`).
15. No cambies dependencias, no crees carpetas base ni documentos `.md` sin que te lo pidan.

## 5. Glosario de nombres que se confunden
- **`<x-datatable>`** = tabla HTML · **`@include('Sistema.components.datatable')`** = carga librerías/tema de DataTables · **`crearDataTable`** = inicialización JS. Van juntos.
- **`<x-btn-action>`** = CTA con gradiente solo en `@section('acciones')` · **`<x-button>`** = botón genérico.
- **No usar (huérfanos):** `Sistema/components/modal.blade.php`, `select2.blade.php`, `card-stat.blade.php`.
- **`Sistema.layouts.app`** ya incluye jQuery, Bootstrap, Select2, SweetAlert2, Chart.js y todos los helpers JS; las vistas solo agregan su propio JS en `@section('scripts')`.

## 6. Deuda conocida
Varios módulos antiguos aún incumplen alguna regla (ver estados ⚠️ en `references/protocolo-refactor.md`). Es deuda, no ejemplo a copiar: en código nuevo o refactorizado aplica siempre el estándar. No reescribas módulos ajenos a la tarea sin que el usuario lo pida.

## 7. Cuando algo no está claro
Si dos fuentes se contradicen, **gana el código real** (`components/*.blade.php`, `*.component.js`) sobre la documentación; luego corrige la documentación. Si sigue ambiguo, pregunta al usuario en vez de inventar.
