# Protocolo de refactor de módulos y tracker

## 1. Alcance
Refactorizar un módulo = su vista Blade (`Sistema/pages/...`), su JS (`public/estilos/jsPropios/...`) y, si hace falta, su controlador, requests y servicio para cumplir `backend.md`. No toques otros módulos, dependencias ni estructura de carpetas sin aprobación.

## 2. Ciclo (4 fases)
1. **Diagnóstico.** Inspecciona:
   - Blade: fondos grises, bordes en cédulas/entidades, uso de `<x-modal>`/`<x-select2>`/`<x-datatable>`/`<x-button>`, `@can` en botones, paridad headers↔columns.
   - JS: `$.ajax` directos, IDs de modal inventados, comentarios, `window.x =`, validación duplicada.
   - Backend: `empresa_id` en cada consulta, FormRequests, ausencia de validación inline, `index()` sin datos, middleware por ruta.
   Entrega un resumen breve de hallazgos.
2. **Ejecución.** Aplica `ui-estandar.md`, `componentes-blade.md`, `javascript-helpers.md` y `backend.md`.
3. **Verificación.** `vendor/bin/pint --format agent` y `php artisan test --compact --filter=Modulo` (o el archivo de test del módulo). Agrega/actualiza tests si cambió lógica o rutas.
4. **Cierre.** Actualiza el tracker de abajo con estado honesto y pide al usuario correr la suite completa.

**Confirmación:** si el usuario pidió explícitamente refactorizar el módulo, esa petición autoriza las fases 1–4; no esperes un "hazlo" extra. Pregunta antes solo si hay cambios destructivos (migraciones, borrar archivos/tests), decisiones de producto ambiguas o cambios fuera del módulo.

## 3. Checklist de cumplimiento (marca todo antes de cerrar)
- [ ] Sin `bg-light`/`alert-light`/`table-light`; contenedores `bg-white border rounded-4 shadow-xs`.
- [ ] Cédulas/RIF/sedes sin caja ni borde; switches en listas abiertas.
- [ ] Componentes `<x-*>` en vez de HTML a mano; modales con IDs generados.
- [ ] Botones de crear/mutar dentro de `@can`.
- [ ] JS sin comentarios, sin `window.x =`, sin `$.ajax` directo, usando `enviarFormulario`/`cambiarEstadoRegistro`/`consultarRegistro`/`peticionAjax`/`crearDataTable`/`crearSelect2` con sus opciones reales.
- [ ] Backend: FormRequest (array + mensajes en español), scoping por `empresa_id`, `index()` solo vista, middleware por ruta.
- [ ] Pint y tests en verde.

## 4. Tracker de módulos
Estados: ✅ cumple el checklist · ⚠️ funcional con desviaciones (ver nota) · ⏳ pendiente.

| # | Módulo | Blade (`Sistema/pages/…`) | JS (`jsPropios/…`) | Estado |
|---|---|---|---|:---:|
| 1 | Empresas | `administradores/empresa.blade.php` | `empresa.js` | ✅ |
| 2 | Almacenes | `empresa/almacen.blade.php` | `almacen.js` | ✅ |
| 3 | Configuración / Tasas | `empresa/configuracion.blade.php` | `configuracion.js` | ✅ |
| 4 | Usuarios & Roles | `administradores/usuario.blade.php` | `usuario.js` | ✅ |
| 5 | Clientes | `empresa/cliente.blade.php` | `cliente.js` | ✅ |
| 6 | Proveedores | `empresa/proveedor.blade.php` | `proveedor.js` | ✅ |
| 7 | Métodos de Pago | `empresa/metodo_pago.blade.php` | `metodo_pago.js` | ✅ |
| 8 | Categorías | `empresa/categoria.blade.php` | `categoria.js` | ✅ |
| 9 | Servicios | `empresa/servicio.blade.php` | `servicio.js` | ✅ |
| 10 | Productos & Inventario | `empresa/producto.blade.php` | `producto.js` | ✅ |
| 11 | Recepción de Mercancía | `empresa/recepcion.blade.php` | `recepcion.js` | ✅ |
| 12 | Kardex | `empresa/kardex.blade.php` | `kardex.js` | ✅ |
| 13 | Motos & Seriales | `empresa/moto.blade.php` | `moto.js` | ✅ |
| 14 | Recepción de Motos | `empresa/recepcion-moto.blade.php` | `recepcionMoto.js` | ✅ |
| 15 | Punto de Venta (POS) | `empresa/pos.blade.php` | `pos.js` | ✅ |
| 16 | Facturación / Historial | `empresa/facturas.blade.php` | `facturas.js` | ✅ |
| 17 | Cuentas por Cobrar | `empresa/cxc.blade.php` | `cxc.js` | ✅ |
| 18 | Cuentas por Pagar | `empresa/cxp.blade.php` | `cxp.js` | ✅ |
| 19 | Vendedores & Asesores | `empresa/vendedor.blade.php` | `vendedor.js` | ✅ |
| 20 | Cajas & Turnos (X/Z) | `empresa/caja.blade.php` | `caja.js` | ✅ |
| 21 | Reporte Ventas por Vendedor | `empresa/reporte-vendedores.blade.php` | `reporteVendedores.js` | ✅ |
| 22 | Centro de Reportes | `empresa/reportes.blade.php` | `reportes.js` | ✅ |
| 23 | Consultor de Precios | `empresa/consultor-precios.blade.php` | `consultorPrecio.js` | ✅ |
| 24 | Dashboard / Panel Principal | `dashboard.blade.php` | `dashboard.js` | ✅ |
| 25 | Asistente de Configuración Inicial | `Setup/configuracion_inicial.blade.php` | `setup.js` | ✅ |

Todos los módulos cumplen con el estándar arquitectónico de JS sin comentarios, sin `window.x =`, sin `$.ajax` directo (usando `peticionAjax` o helpers CRUD), con permisos por middleware/Blade y aislamiento multi-empresa.

## 5. Roadmap de fases (histórico)
Fase 1 multi-empresa y asistente ✅ · Fase 2 seguridad/roles/permisos ✅ · Fase 3 catálogos ✅ · Fase 4 compras/recepción/Kardex ✅ · Fase 5 motos ✅ · Fase 6 POS y facturación ✅ · Fase 7 créditos CXC/CXP ✅ · Fase 8 Flujos de negocio, integridad de dinero, cajas, reportes y estándares (pos-flujos B-00 a B-43) ✅.
