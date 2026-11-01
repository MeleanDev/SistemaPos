# Mapa de pantallas: qué acciones tiene cada una

Leyenda: ✅ vive aquí · 🔗 solo enlace/atajo · ❌ no debe estar. Permisos entre paréntesis. "Estado" = condición del documento para mostrar el botón.

## 1. Pantallas de operación

### Punto de Venta (POS) — `pos.acceso`
Hogar de: **armar venta, preventas, cobrar (facturar)**.

| Acción | Quién / permiso | Notas |
|---|---|---|
| Buscar/escanear ítem, carrito, descuento por renglón | `pos.acceso` (descuento: `ventas.descuentos`) | |
| Seleccionar cliente / crear cliente rápido | `pos.acceso` | Atajo a Clientes (mismo servicio) |
| Elegir vendedor | `pos.acceso` si `maneja_vendedores` | Vendedor-usuario: fijo en sí mismo |
| Guardar preventa | `pos.preventa` o `ventas.crear` | |
| Recuperar / eliminar preventa | dueño o `ventas.crear` | Vendedor: solo propias |
| **Cobrar / facturar** | `ventas.crear` | Vendedor puro **no ve** el botón Cobrar |
| Elegir caja (admin) | `ventas.crear` + admin | Selector obligatorio en barra superior (D6) |
| Imprimir ticket / carta / garantía tras cobrar | `pos.acceso` | |
| Ver estado del turno propio | `cajas.ver` | Solo lectura |
| 🔗 Corte X de mi turno | `cajas.arqueo` | Atajo de solo lectura |
| ❌ Devolución / Anulación | — | Se mueve a Facturas |
| ❌ Cierre Z | — | Vive en Cajas y Turnos |
| ❌ Movimientos de caja (ingreso/egreso) | — | Vive en Cajas y Turnos |
| ❌ Aperturar turno | — | Vive en Cajas y Turnos |

### Consultor de Precios — autenticado
Solo lectura: buscar y mostrar precio. Sin acciones. Costos ocultos sin permiso.

## 2. Pantallas de administración y supervisión

### Facturas (Historial) — `ventas.ver`
Hogar de: **consultar, reimprimir, anular, devolver**.

| Acción | Permiso | Estado requerido |
|---|---|---|
| Filtrar, KPIs, ver detalle | `ventas.ver` | cualquiera |
| Reimprimir ticket / carta / garantía | `ventas.ver` | cualquiera |
| **Anular** | `ventas.anular` | `completada`, `devuelta_parcial` |
| **Devolver** (parcial/total) | `ventas.anular` | `completada`, `devuelta_parcial` |
| Ver devoluciones y anulación (motivo, usuario, monto) | `ventas.ver` | si existen |
| 🔗 Ir a CXC de la factura | `cxc.ver` | con CXC |
| ❌ Crear factura | — | POS |

### Cajas y Turnos — `cajas.ver`
Hogar de: **catálogo de cajas, turnos, Corte X, Cierre Z, movimientos**.

| Acción | Permiso | Notas |
|---|---|---|
| CRUD de cajas | `cajas.crear/editar/eliminar` | |
| Aperturar turno (asignar cajero, fondo) | `cajas.aperturar` | Admin |
| Registrar ingreso/egreso menor | `cajas.movimientos` | Cajero: su turno |
| Ver movimientos del turno (incluye vuelto, reembolsos) | `cajas.ver` | |
| Corte X (ver/imprimir) | `cajas.arqueo` | Cajero: su turno |
| **Cierre Z** | `cajas.cerrar` | Cajero: suyo y a ciegas; Admin: cualquiera |
| Historial de turnos, reimprimir Z | `cajas.ver`/`cajas.arqueo` | |
| ❌ Facturar | — | POS |

### Cuentas por Cobrar — `cxc.ver`
Hogar de: **abonos a clientes**.

| Acción | Permiso | Notas |
|---|---|---|
| Ver deuda por cliente y por factura | `cxc.ver` | |
| Abonar a factura / abono general | `cxc.abonar` | Exige turno abierto; con `@can` en los botones |
| Imprimir ticket de abono | `cxc.ver` | |
| 🔗 Ir a la factura origen | `ventas.ver` | |
| ❌ Crear crédito | — | Nace al facturar en POS |

### Cuentas por Pagar — `cxp.ver`
Ver deuda por proveedor, abonar (`cxp.abonar`), ticket. 🔗 a la recepción origen. ❌ crear deuda (nace en Recepción).

### Vendedores — `vendedores.ver` (solo si `maneja_vendedores`)
Hogar de: **catálogo de vendedores y liquidación de comisiones**.

| Acción | Permiso |
|---|---|
| CRUD vendedor, % comisión, vincular usuario | `vendedores.crear/editar/eliminar` |
| Pestaña **Comisiones**: ver pendientes por vendedor | `vendedores.ver` |
| **Liquidar comisiones** | `vendedores.liquidar` |
| 🔗 Ir al reporte de ventas por vendedor | `reportes.vendedores` |

### Usuarios y Roles — `usuarios.ver`
CRUD usuarios, asignar rol (SuperAdmin/Admin/Operador/**Vendedor**), empresas y permisos. Vincular vendedor se hace desde **Vendedores**, no aquí.

### Clientes (globales) — `clientes.ver`
CRUD. El POS tiene atajo de creación rápida con el mismo servicio. Sin acciones de venta.

### Métodos de Pago (globales) — SuperAdmin
CRUD con `es_efectivo`. Nada más.

### Empresas / Configuración
Empresas (SuperAdmin): flags `maneja_motos`, `maneja_vendedores`. Configuración: datos de empresa y tasas.

## 3. Inventario y compras

| Pantalla | Hogar de | No debe tener |
|---|---|---|
| Productos | CRUD, ficha, códigos de barra, proveedores | ajustes de stock (viven en Kardex) |
| Servicios / Categorías / Almacenes | CRUD | — |
| Kardex | Consulta, **ajuste de stock** (`inventario.ajustar_stock`), **traslado** (`inventario.traslados`), comprobante | recepciones, ventas |
| Motos & Seriales | CRUD modelos/motos, ficha, cambiar estado | recepción de lote (vive en Recepción de Motos) |
| Recepción Mercancía / Recepción Motos | Crear recepción, borradores, **anular**, imprimir (`compras.recepcion`/`motos.recepcion`) | abonar deuda (vive en CXP) |
| Proveedores | CRUD (global por empresa) | — |

## 4. Informes (solo lectura)

| Pantalla | Contenido |
|---|---|
| Panel Principal | KPIs y alertas, enlaces a la pantalla dueña. Sin acciones de negocio. |
| Centro de Reportes | Ventas/Ingresos, Flujo de caja, Rentabilidad, Créditos, Inventario, Stock, Cuadre diario, Devoluciones/Anulaciones, Libro de ventas. Vista previa, PDF, CSV. |
| Ventas por Vendedor | Venta neta, comisión ganada/pagada/pendiente. Solo lectura (la liquidación está en Vendedores). |

## 5. Atajos permitidos (lista cerrada)

1. POS → crear cliente rápido (Clientes).
2. POS → Corte X de mi turno (Cajas, solo lectura).
3. POS/Recepciones → creación rápida de proveedor/producto/modelo (mismos servicios que su pantalla dueña).
4. Facturas ↔ CXC (enlaces cruzados).
5. Panel/Reportes → enlace al documento origen.
6. Vendedores → enlace al reporte de vendedores.

Cualquier otra duplicación se considera brecha.
