# Dominio: flujo del negocio y contratos entre módulos

## 1. Arranque del sistema
- Si no existe SuperAdmin activo ni Empresa, el middleware `VerificarConfiguracionInicial` (grupo `web` en `bootstrap/app.php`) redirige todo a `/configuracion-inicial` (excluye `estilos/*`, `storage/*`, `build/*`, `favicon.ico`, `configuracion-inicial*`).
- Asistente en una sola `DB::transaction`: (1) Empresa Matriz (nombre comercial, razón social, RIF `J-/V-/G-/E-`, teléfono, correo, dirección, logo, switch `maneja_motos`); (2) SuperAdmin (cédula en `name`, email, nombre, apellido, clave ≥ 8); (3) Métodos de pago iniciales. Luego loguea al SuperAdmin y fija `session(['empresa_activa_id' => $empresa->id])`.
- Flags por empresa: `maneja_motos` (muestra Recepción de Motos y Motos & Seriales en el sidebar) y `maneja_vendedores` (activa vendedores/comisiones). Se editan en `/empresas`.

## 2. Mapa de entidades
```
EMPRESAS (raíz multi-tenant)
 ├─ USUARIOS & ROLES (Spatie + empresa_user)
 ├─ ALMACENES (bodega, depósito, piso de venta)
 ├─ CATEGORIAS ─ PRODUCTOS & SERVICIOS ─ PRODUCTO_PROVEEDORES ─ PROVEEDORES
 │     ├─ PRODUCTO_CODIGOS_BARRA (N por producto)
 │     ├─ PRODUCTO_STOCK_ALMACENES (stock por almacén)
 │     └─ Fiscal: IVA (16%/8%/0%) + IGTF (3% en divisas)
 ├─ RECEPCIONES/COMPRAS → entrada a stock + Kardex
 ├─ TRASLADOS → doble asiento Kardex
 ├─ POS/FACTURAS → descuenta stock + Kardex (+ comisión de vendedor)
 ├─ KARDEX (libro mayor inmutable)
 ├─ CAJAS & TURNOS (apertura, arqueo, corte X, cierre Z)
 ├─ METODOS DE PAGO · CLIENTES (globales) · VENDEDORES
 └─ CREDITOS (CXC clientes, CXP proveedores)
```

## 3. Contratos por módulo
1. **Empresas:** entidad maestra; todo se acota por `empresa_id`.
2. **Productos & Servicios:** `tipo` = `producto` (con stock) o `servicio` (sin stock/almacén, precio bidireccional USD↔Bs). Unidades: `unidad, kilo, gramo, litro, bulto, caja, paquete, metro`. Fiscal: `aplica_iva`+`iva_porcentaje`, `aplica_igtf`+`igtf_porcentaje` (3% por defecto). Precios: costo, detal, mayorista.
3. **Recepción de mercancía:** wizard de 2 fases (cabecera fiscal/proveedor/condición; renglones, costos, márgenes, resumen). Bimonetaria con tasa BCV congelada. Recuerda `ultimo_margen_detal/mayorista`, vincula `producto_proveedores`, imprime `/recepciones/{id}/imprimir` y asienta Kardex `entrada_recepcion`.
4. **Motos/vehículos:** por lote con seriales únicos (N.I.V./V.I.N. 17 caracteres, chasis, motor, certificado de origen, placa) y atributos (marca, modelo, año, color, cilindrada, ref). Estados: `disponible`, `reservada`, `vendida`, `en_mantenimiento`.
5. **Traslados:** movimiento atómico entre almacenes con doble asiento Kardex (`Salida por Traslado` / `Entrada por Traslado`).
6. **POS y facturación:** búsqueda por cualquier código de barras o serial; descuenta del almacén de la caja (piso de venta); cálculo en vivo de subtotal, IVA, IGTF y total; pagos mixtos multimoneda; ventas en espera; ticket 80mm; Kardex `Salida por Venta`.
7. **Anulaciones/devoluciones:** reponen stock/serial al almacén de origen con `Entrada por Anulación`.
8. **Ajustes de inventario:** diferencias de conteo registradas como `Ajuste de Inventario` (mermas/sobrantes).

## 4. Cajas, turnos y vendedores
- **Vendedores:** catálogo propio por `empresa_id` (`tipo_documento`, `documento`, `nombre`, `telefono`, `correo`, `comision_porcentaje`), vínculo opcional a `users.id`. La comisión se **congela** al emitir la factura (`ventas.comision_porcentaje`, `comision_monto_usd`, `comision_monto_bs`).
- **Exclusividad de turnos:** un cajero solo puede tener 1 turno abierto por empresa; una caja física solo 1 turno abierto.
- **Corte X** (`/cajas/turnos/{id}/reporte-x` · `imprimir-x`): auditoría en vivo sin cerrar. **Cierre Z** (`/cajas/turnos/{id}/cerrar` · `imprimir-z`): conteo físico (`monto_cierre_usd/bs`), diferencia (Exacto/Sobrante/Faltante), ticket con firmas. Ambos térmicos 80mm.
- **POS:** exige turno desde la barra superior, dispara Corte X/Cierre Z y atribuye vendedor en el cajón de cobro.

## 5. Borradores de recepciones (productos y motos)
1. Autoguardado local con debounce de 500ms en `localStorage` (`draft_recepcion_producto` / `draft_recepcion_moto`).
2. Borradores en servidor (`recepciones_borradores`, `recepcion_motos_borradores`): `GET /recepciones/borradores`, `POST /recepciones/borradores` (JSON en `datos_json`), `GET /recepciones/borradores/{id}`, `DELETE /recepciones/borradores/{id}`.
3. UI: badge contador en `Borradores Guardados` (`badgeConteoBorradores`), barra `#alertaBorradorDetectado` (Restaurar/Descartar), botón "Guardar Borrador" y modal `<x-modal id="modalBorradoresX">`.
4. Al finalizar la factura se elimina el borrador local y el del servidor.

## 6. Centro de reportes (`/reportes`)
Ingresos y métodos de pago (por caja y rango de fechas) · Cartera CXC/CXP con antigüedad (Al día, Por vencer, Mora >30d, >60d) · Inventario (valoración, stock por almacén, mínimos, top vendidos) · Rentabilidad (margen bruto $ y %) · Vendedores y comisiones · Stock por almacenes. Cada reporte ofrece vista previa, PDF (ver/descargar) y Excel (CSV).

## 7. Consultor de precios (`/consultor-precios`)
Modo kiosco independiente (`consultor-layout.blade.php`, sin sidebar ni navegación), tema oscuro/claro persistido en `localStorage` (`kiosk_theme_consultor`), foco perpetuo con pitidos Web Audio (1450Hz acierto, 220Hz no encontrado), escaneo continuo sin bloqueo, precios USD/Bs con tasa BCV e IVA, fuentes adaptativas (`clamp()`) para montos largos.
