# Guía Maestra de Prompts — Desarrollo y Evolución del Sistema POS

Esta guía contiene plantillas de prompts optimizadas y probadas para dar instrucciones a cualquier asistente o desarrollador IA en este proyecto. Cada prompt está calibrado para que la IA active automáticamente las skills correctas (`pos-architecture`, `pos-flujos`, `pos-pantallas`), respete las decisiones cerradas y nunca rompa el estándar arquitectónico.

---

## 📋 Índice de Prompts
1. [Prompt 1: Agregar una nueva funcionalidad en la pantalla POS](#prompt-1-agregar-una-nueva-funcionalidad-en-la-pantalla-pos)
2. [Prompt 2: Mover o reubicar botones, modales o acciones entre pantallas](#prompt-2-mover-o-reubicar-botones-modales-o-acciones-entre-pantallas)
3. [Prompt 3: Crear o refactorizar un módulo completo](#prompt-3-crear-o-refactorizar-un-módulo-completo)
4. [Prompt 4: Modificar o crear un nuevo flujo de negocio](#prompt-4-modificar-o-crear-un-nuevo-flujo-de-negocio)
5. [Prompt 5: Auditoría y checklist de integridad antes de cerrar una tarea](#prompt-5-auditoría-y-checklist-de-integridad-antes-de-cerrar-una-tarea)

---

### Prompt 1: Agregar una nueva funcionalidad en la pantalla POS

> **Cuándo usarlo:** Cuando quieras añadir un nuevo cálculo, un botón de acción rápida, un filtro, soporte para una nueva modalidad de pago o una mejora al carrito/cobro del POS.

```markdown
Activa las skills `pos-architecture` y `pos-flujos` antes de comenzar.

Necesito agregar la siguiente funcionalidad en la pantalla del Punto de Venta (POS):
[DESCRIBE AQUÍ LA FUNCIONALIDAD: ej. Agregar un botón de descuento global por cupón / Agregar selector de tipo de despacho / Permitir propinas o propina voluntaria]

Reglas estrictas que debes cumplir:
1. Stack técnico: Blade + Bootstrap 5 + jQuery (NO Tailwind, NO modales ni selects escritos a mano; usa componentes <x-*>).
2. Seguridad y Permisos: La acción debe estar protegida por su permiso Spatie correspondiente (ej. @can('ventas.descuentos')) tanto en Blade como en el backend.
3. Validación en Servidor: No confíes en lo que envía el navegador; valida precios, stock, almacén y turnos en un FormRequest y en VentaClass.
4. Integridad de Caja: Si esta función afecta montos de dinero, asegúrate de que caja_turno_id nunca sea null y que el efectivo esperado cuadre al centavo.
5. JavaScript: El código en public/estilos/jsPropios/pos.js debe respetar las reglas: cero comentarios, sin `window.x = x` y usando peticionAjax o helpers globales.
6. Al finalizar, formatea el PHP con `vendor/bin/pint --dirty --format agent` y ejecuta el test correspondiente con `php artisan test --compact --filter=Pos`.
```

---

### Prompt 2: Mover o reubicar botones, modales o acciones entre pantallas

> **Cuándo usarlo:** Cuando quieras cambiar de lugar un botón (por ejemplo mover algo del POS a Cajas, o de Facturas a CXC), reorganizar la barra superior, reorganizar el sidebar o limpiar modales duplicados.

```markdown
Activa las skills `pos-pantallas` y `pos-architecture`.

Necesito reubicar la siguiente acción en el sistema:
- Acción o botón a mover: [DESCRIBE EL BOTÓN/MODAL: ej. Mover el botón de X desde la pantalla Y hacia la pantalla Z]
- Motivo: Cumplir con el principio de "un solo hogar por acción" establecido en mapa-pantallas.md.

Reglas estrictas que debes cumplir:
1. Reubicación completa: Mueve el HTML/Blade, el modal (usando <x-modal>), el JS en `public/estilos/jsPropios/` y la ruta correspondiente.
2. Cero código huérfano: No dejes eventos JS huérfanos, ni modales fantasmas, ni endpoints sin uso en la pantalla anterior.
3. Atajo permitido: Si en la pantalla de origen aún se requiere acceso rápido, déjalo únicamente como un enlace o botón de redirección de solo lectura (según atajos permitidos en mapa-pantallas.md §5).
4. Permisos: Envuelve el botón y la acción en su directiva @can('modulo.accion') y añade el middleware correspondiente en routes/web.php.
5. Verifica que no existan errores de consola ni advertencias en el navegador.
```

---

### Prompt 3: Crear o refactorizar un módulo completo

> **Cuándo usarlo:** Cuando vayas a programar un nuevo catálogo, una nueva vista de gestión, o refactorizar un módulo antiguo que tenga estilos o código desactualizado.

```markdown
Activa la skill `pos-architecture` y lee las referencias:
- `references/ui-estandar.md`
- `references/componentes-blade.md`
- `references/javascript-helpers.md`
- `references/backend.md`

Necesito [crear / refactorizar] el módulo de: [NOMBRE DEL MÓDULO: ej. Garantías de Equipos / Comisiones de Cobradores]

Requisitos obligatorios:
1. UI Ejecutiva: Contenedores `bg-white border rounded-4 shadow-xs`. Cero fondos grises (prohibido bg-light/table-light/alert-light). Cédulas, RIF y sedes sin caja ni borde (solo texto tipográfico con icono).
2. Blade: Reutiliza obligatoriamente <x-datatable>, <x-modal>, <x-select2>, <x-input>, <x-button> y <x-btn-action>.
3. JavaScript: En `public/estilos/jsPropios/[modulo].js`: sin comentarios, sin `window.x =`, sin `$.ajax` directo. Usa `enviarFormulario`, `cambiarEstadoRegistro`, `consultarRegistro` y `peticionAjax` con sus opciones reales.
4. Backend: Multi-empresa estricto mediante `$this->obtenerEmpresaId()`. Validación en FormRequest (extiende BaseRequest, mensajes en español). Método `index()` solo devuelve la vista vacía.
5. Corrección de estilo: Ejecuta `vendor/bin/pint --dirty --format agent`.
```

---

### Prompt 4: Modificar o crear un nuevo flujo de negocio

> **Cuándo usarlo:** Cuando cambies reglas sobre cómo se cobra, cómo se devuelven productos, cómo se cierran cajas, cómo se calculan comisiones o cómo se generan créditos.

```markdown
Activa prioritariamente la skill `pos-flujos` y consulta `pos-architecture`.

Necesito implementar un cambio en las reglas de negocio para:
[DESCRIBE EL FLUJO: ej. Incorporar notas de crédito para clientes / Permitir pagos mixtos de una factura entre dos cajas distintas / Ajustar cálculo de retenciones]

Restricciones y contratos que NO se pueden romper:
1. Respeta las decisiones cerradas D1 a D7 de `pos-flujos`:
   - D1: Vendedor solo preventa.
   - D2: Comisión congelada al facturar sobre subtotal_neto.
   - D3: Anulaciones y devoluciones siempre permitidas con ventas.anular (afectando turno actual).
   - D4: Clientes y métodos de pago son globales.
   - D5: IGTF inerte por ahora.
   - D6: caja_turno_id obligatorio; admin elige caja abierta.
   - D7: Cierre Z a ciegas para el cajero.
2. Inmutabilidad: No borres registros contables ni de inventario; genera asientos contrarios.
3. Kardex: Todo movimiento de mercancía debe generar su asiento en `kardex` con costo unitario y cantidad real sin saldo negativo.
4. Efectivo: Si hay entrada o salida de dinero físico, debe actualizar `caja_movimientos` y cuadrar con la fórmula del arqueo.
5. Transaccionalidad: Encapsula todo dentro de `DB::transaction` con cerrojos `lockForUpdate`.
```

---

### Prompt 5: Auditoría y checklist de integridad antes de cerrar una tarea

> **Cuándo usarlo:** Antes de dar por terminado cualquier cambio o refactorización para asegurarte de que no queden errores ocultos.

```markdown
Activa las skills `pos-architecture` y `pos-flujos`.

Realiza una auditoría completa del código que acabamos de tocar y verifica el siguiente checklist:
1. ¿Todas las consultas en controladores y servicios tienen aislamiento por `empresa_id`?
2. ¿Existen fallbacks silenciosos prohibidos como `?? 1` en usuario o empresa?
3. ¿Todos los botones de crear/editar/eliminar/anular están protegidos con `@can` en el Blade?
4. ¿El archivo JavaScript tiene comentarios, funciones en `window` o llamadas directas a `$.ajax`?
5. ¿Hay discrepancias entre los nombres de columnas de las migraciones y las proyecciones de Eloquent (ej. códigos de barra vs código interno)?
6. Ejecuta `vendor/bin/pint --dirty --format agent` para certificar el formato PSR-12.
7. Entrega un informe conciso confirmando el estado de cada punto.
```
