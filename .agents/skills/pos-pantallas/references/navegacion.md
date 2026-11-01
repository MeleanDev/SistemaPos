# Navegación (sidebar)

Fuente actual: `resources/views/Sistema/layouts/sidebar.blade.php`. Reglas generales en `SKILL.md` §2.8.

## Estructura objetivo

| Grupo | Entradas (permiso) |
|---|---|
| (raíz) | Panel Principal |
| **Operación** | Punto de Venta (`pos.acceso`) · Consultor de Precios (`consultor.ver`) |
| **Ventas y Caja** | Facturas (`ventas.ver`) · Cajas y Turnos (`cajas.ver`) · Cuentas por Cobrar (`cxc.ver`) |
| **Equipo de ventas** *(si `maneja_vendedores`)* | Vendedores y Comisiones (`vendedores.ver`) |
| **Compras** | Recepción Mercancía (`compras.recepcion`) · Recepción Motos (`motos.recepcion` + `maneja_motos`) · Proveedores (`proveedores.ver`) · Cuentas por Pagar (`cxp.ver`) |
| **Inventario y Catálogo** | Categorías · Productos · Servicios · Motos & Seriales (+`maneja_motos`) · Almacenes · Kardex |
| **Clientes** | Clientes (`clientes.ver`) |
| **Informes** | Centro de Reportes (`reportes.ver`) · Ventas por Vendedor (`reportes.vendedores` + `maneja_vendedores`) |
| **Sistema** | Empresas (SuperAdmin) · Métodos de Pago (SuperAdmin) · Usuarios y Roles (`usuarios.ver`) · Configuración (`configuracion.ver`) |

## Cambios respecto a hoy
1. **Métodos de Pago** sale de "Ventas & Facturación" y pasa a **Sistema** (es configuración global de SuperAdmin).
2. **Cuentas por Cobrar** pasa junto a Facturas y Cajas ("Ventas y Caja"); **Cuentas por Pagar** pasa a Compras. El grupo "Créditos & Finanzas" desaparece.
3. **Vendedores** sale del grupo de ventas y pasa a su propio grupo condicionado por `maneja_vendedores` (hoy el flag usa `?? true`; debe ser el valor real).
4. Renombrar "Historial Facturas" → **Facturas** (ahora anula y devuelve, ya no es solo historial).
5. "Consultor de Precios" necesita permiso (`consultor.ver`); hoy no tiene.
6. Corregir permisos huérfanos en el `canAny` de Inventario (`categoria.ver` no existe).
7. El grupo de cada sección solo se muestra si el usuario ve al menos una entrada.
8. Un Vendedor solo debe ver: Panel, POS (modo preventa), Consultor y su Reporte de ventas.
9. Un Cajero típico ve: Panel, POS, Consultor, Facturas (reimprimir), Cajas y Turnos (su turno), CXC.

## Layouts
- `app.blade.php`: pantallas de administración.
- `pos-layout.blade.php`: POS. Su barra superior solo muestra turno/caja/tasa/preventas; no aloja acciones administrativas.
- `consultor-layout.blade.php`: modo kiosco, sin navegación.
