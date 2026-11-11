# Dominio: flujo del negocio y contratos entre módulos

## 1. Arranque del sistema
- Si no existe SuperAdmin activo ni Empresa, el middleware `VerificarConfiguracionInicial` (grupo `web` en `bootstrap/app.php`) redirige todo a `/configuracion-inicial` (excluye `estilos/*`, `storage/*`, `build/*`, `favicon.ico`, `configuracion-inicial*`).
- Asistente en una sola `DB::transaction`: (1) Empresa Matriz (nombre comercial, razón social, RIF `J-/V-/G-/E-`, teléfono, correo, dirección, logo, switch `maneja_motos`); (2) SuperAdmin (cédula en `name`, email, nombre, apellido, clave ≥ 8); (3) Métodos de pago iniciales. Luego loguea al SuperAdmin y fija `session(['empresa_activa_id' => $empresa->id])`.
- Flags por empresa: `maneja_motos` (muestra Recepción de Motos y Motos & Seriales en el sidebar) y `maneja_vendedores` (activa vendedores/comisiones). Se editan en `/empresas`.

## 2. Mapa de entidades
```
EMPRESAS (raíz multi-tenant)
 ├─ USUARIOS & ROLES (Spatie + empresa_user: SuperAdmin, Admin, Operador, Vendedor)
 ├─ ALMACENES (bodega, depósito, piso de venta)
 ├─ CATEGORIAS ─ PRODUCTOS & SERVICIOS ─ PRODUCTO_PROVEEDORES ─ PROVEEDORES
 │     ├─ PRODUCTO_CODIGOS_BARRA (N por producto)
 │     ├─ PRODUCTO_STOCK_ALMACENES (stock físico por almacén con lockForUpdate)
 │     ├─ PRODUCTO_SERIALES (seriales individuales con estado: disponible, vendido)
 │     └─ Fiscal: IVA (16%/8%/0%) (IGTF inerte / en desarrollo)
 ├─ RECEPCIONES/COMPRAS → entrada a stock + Kardex + CXP (si es crédito)
 ├─ TRASLADOS → doble asiento Kardex (traslado_salida / traslado_entrada)
 ├─ AJUSTES → ajuste_positivo / ajuste_negativo
 ├─ POS/FACTURAS → descuenta stock + Kardex (+ comisión vendedor) + CXC (si es crédito)
 ├─ SECUENCIAS (tabla atómica con lockForUpdate para venta, control, devolución y liquidación)
 ├─ KARDEX (libro mayor inmutable de movimientos valorados)
 ├─ CAJAS & TURNOS (apertura, arqueo, corte X, cierre Z a ciegas, caja_movimientos)
 ├─ METODOS DE PAGO · CLIENTES (globales) · VENDEDORES (catálogo tenant)
 └─ CREDITOS (CXC clientes con abonos en turno; CXP proveedores con abono FIFO)
```

## 3. Contratos por módulo
1. **Empresas:** entidad maestra; todo se acota por `empresa_id`.
2. **Productos & Servicios:** `tipo` = `producto` (con stock) o `servicio` (sin stock/almacén, precio bidireccional USD↔Bs). La tabla `productos` identifica el ítem con `codigo_interno` (SKU); los códigos de barra residen en la relación `codigosBarra` (`producto_codigos_barra.codigo_barra`), **no** existe columna `codigo_barra` en `productos`. Unidades: `unidad, kilo, gramo, litro, bulto, caja, paquete, metro`. Fiscal: `aplica_iva`+`iva_porcentaje`. Precios: costo, detal, mayorista. (IGTF no implementado por ahora, campos inertes).
3. **Recepción de mercancía:** wizard de 2 fases (cabecera fiscal/proveedor/condición; renglones, costos, márgenes, resumen). Bimonetaria con tasa congelada. Recuerda `ultimo_margen_detal/mayorista`, vincula `producto_proveedores`, imprime `/recepciones/{id}/imprimir` y asienta Kardex `entrada_recepcion`. Si es a crédito, crea registro en `cuentas_por_pagar`.
4. **Motos/vehículos:** por lote con seriales únicos (N.I.V./V.I.N. 17 caracteres, chasis, motor, certificado de origen, placa) y atributos (marca, modelo, año, color, cilindrada, ref). Estados: `disponible`, `reservada`, `vendida`, `en_mantenimiento`.
5. **Traslados:** movimiento atómico entre almacenes con doble asiento Kardex (`traslado_salida` en origen y `traslado_entrada` en destino), actualizando la ubicación física de seriales y productos con `lockForUpdate`.
6. **POS y facturación:** búsqueda por cualquier código de barras o serial; descuenta del almacén de despacho; cálculo en vivo de subtotal, IVA y total; pagos mixtos multimoneda; preventas en espera (`ventas_en_espera`); ticket 80mm; Kardex `salida_venta`. Correlativos atómicos con `Secuencia::siguiente` (`VEN-XXXXX` y número de control `00-XXXXXXXX`) bajo cerrojo pesimista e índices únicos en base de datos. Vueltos en efectivo se registran automáticamente como `egreso` en `caja_movimientos`.
7. **Anulación de factura (`POST /facturas/{id}/anular`):** con `permission:ventas.anular`; inmutable; repone stock/seriales con `anulacion_venta` en Kardex; anula créditos CXC; anula comisión del vendedor (`comision_monto_usd = 0`, `comision_estado = 'sin_comision'`); registra egreso en el turno de caja abierto actual por devolución del dinero efectivamente cobrado (menos devoluciones previas); motivo obligatorio ($\ge 5$ caracteres) y auditoría completa (`anulada_por_id`, `anulada_at`, `motivo_anulacion`); sello "ANULADA" en ticket e impresión.
8. **Devoluciones de productos (`POST /facturas/{id}/devolver`):** parcial o total con reposición de inventario; reembolso neto proporcional (descontando descuento previo e IVA proporcional al renglón); exige turno abierto y registra egreso con método (`tipo_reembolso`); si la factura tiene CXC con saldo pendiente, primero amortiza el saldo y solo el remanente genera salida de dinero; recalcula comisión del vendedor según venta neta remanente.
9. **Ajustes de inventario:** diferencias de conteo registradas como `MovimientoInventario` con código `AJU-XXXXX` y asientos `ajuste_positivo` o `ajuste_negativo`.
10. **Cuentas por Pagar (CXP):** registro automático desde recepciones a crédito. Soporta abonos a factura individual (`abonarFacturaEspecifica`) y abonos generales por proveedor mediante algoritmo **FIFO** (`abonarGeneralDeuda`, liquidando facturas desde la más antigua con `lockForUpdate`).
11. **Cuentas por Cobrar (CXC):** registro automático desde ventas con saldo pendiente. Exige turno de caja abierto para percibir abonos (`cuentas_por_cobrar_abonos`), actualizando concurrentemente el saldo de la cuenta y de la factura ligada, e integrándose al arqueo del turno.

## 4. Cajas, turnos, vendedores y roles
- **Roles reales del sistema:**
  - `SuperAdmin`: raíz global; único con bypass por rol. Gestiona empresas y métodos de pago globales.
  - `Admin`: gestiona sus empresas asignadas (usuarios, catálogos, cajas, vendedores, reportes). Puede facturar eligiendo cualquier caja abierta.
  - `Operador`: rol base con permisos asignados por usuario (ej. Cajero: `pos.acceso, ventas.crear, cajas.ver, cajas.movimientos, cajas.arqueo, cxc.ver, cxc.abonar`). Cierre Z a ciegas de su propio turno.
  - `Vendedor`: rol asignable desde la UI para asesores comerciales; solo preventa (`pos.acceso, pos.preventa, reportes.vendedores, consultor.ver`). No cobra, no factura, no anula; solo ve y elimina sus propias preventas.
- **Vendedores y Comisiones:** catálogo por `empresa_id` (`tipo_documento`, `documento`, `nombre`, `telefono`, `correo`, `comision_porcentaje`), vínculo opcional a `users.id` con unicidad `(empresa_id, user_id)`. La comisión se **congela** al facturar sobre el subtotal neto sin IVA. Liquidación de comisiones con `permission:vendedores.liquidar` y egreso opcional en caja.
- **Exclusividad de turnos:** un cajero solo puede tener 1 turno abierto por empresa; una caja física solo 1 turno abierto.
- **Corte X** (`/cajas/turnos/{id}/reporte-x` · `imprimir-x`): auditoría en vivo sin cerrar. **Cierre Z** (`/cajas/turnos/{id}/cerrar` · `imprimir-z`): conteo físico (`monto_cierre_usd/bs`), diferencia (Exacto/Sobrante/Faltante), ticket con firmas. Cajero a ciegas; Admin/SuperAdmin ven esperado.
- **POS:** exige turno abierto, incluye atajo de auditoría Corte X (el Cierre Z vive exclusivamente en Cajas y Turnos) y atribuye vendedor en el cajón de cobro.

## 5. Borradores de recepciones (productos y motos)
1. Autoguardado local con debounce de 500ms en `localStorage` (`draft_recepcion_producto` / `draft_recepcion_moto`).
2. Borradores en servidor (`recepciones_borradores`, `recepcion_motos_borradores`): `GET /recepciones/borradores`, `POST /recepciones/borradores` (JSON en `datos_json`), `GET /recepciones/borradores/{id}`, `DELETE /recepciones/borradores/{id}`.
3. UI: badge contador en `Borradores Guardados` (`badgeConteoBorradores`), barra `#alertaBorradorDetectado` (Restaurar/Descartar), botón "Guardar Borrador" y modal `<x-modal id="modalBorradoresX">`.
4. Al finalizar la factura se elimina el borrador local y el del servidor.

## 6. Centro de reportes (`/reportes`)
Ingresos y métodos de pago (por caja y rango de fechas) · Cartera CXC/CXP con antigüedad (Al día, Por vencer, Mora >30d, >60d) · Inventario (valoración, stock por almacén, mínimos, top vendidos) · Rentabilidad (margen bruto $ y %) · Vendedores y comisiones · Stock por almacenes. Cada reporte ofrece vista previa, PDF (ver/descargar) y Excel (CSV).

## 7. Consultor de precios (`/consultor-precios`)
Modo kiosco independiente (`consultor-layout.blade.php`, sin sidebar ni navegación), tema oscuro/claro persistido en `localStorage` (`kiosk_theme_consultor`), foco perpetuo con pitidos Web Audio (1450Hz acierto, 220Hz no encontrado), escaneo continuo sin bloqueo, precios USD/Bs con tasa BCV e IVA, fuentes adaptativas (`clamp()`) para montos largos.
