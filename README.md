# Sistema POS (Punto de Venta & Gestión de Inventario Automotriz / Motos y Repuestos)

Sistema integral de Punto de Venta (POS), Facturación, Control de Inventario Especializado (Repuestos y Motos/Vehículos con seriales únicos), Gestión de Almacenes múltiples, Movimientos Kardex, Recepción de Mercancía, Clientes, Proveedores, Cuentas por Cobrar (CXC), Cuentas por Pagar (CXP) y Reportes Financieros / Administrativos.

---

## 🛠️ Arquitectura y Tecnologías

El sistema sigue una arquitectura desacoplada y modular:
- **Backend**: Laravel 12 (PHP 8.4) con controladores tipo API / respuestas JSON estructuradas (`{ success: true, data: [...], message: "..." }`).
- **Frontend / UI**: Vistas Blade estructuradas en `resources/views/Sistema/` complementadas con componentes reutilizables en `resources/views/Sistema/components/`.
- **Librerías de Interfaz**:
  - **jQuery**: Manejo de DOM, eventos y peticiones AJAX asíncronas con configuración global de CSRF y alertas.
  - **Bootstrap 5**: Maquetación responsiva, grid y modales.
  - **DataTables**: Tablas interactivas con filtrado, paginación y tema visual ejecutivo.
  - **Select2**: Selectores con búsqueda predictiva y soporte para Bootstrap 5.
  - **Chart.js**: Gráficos dinámicos de métricas en tiempo real.
  - **SweetAlert2**: Notificaciones Toast y modales de confirmación/error.
  - **FontAwesome 5**: Catálogo de iconos oficial para toda la navegación y botones.

---

## 📂 Estructura de Módulos del Sistema

### 1. Panel Principal (Dashboard)
- Indicadores en tiempo real (Ventas de hoy, facturas emitidas, repuestos más vendidos, motos vendidas, alertas de stock bajo/crítico).
- Balance y estado de caja actual.
- Gráficas de tendencias de venta por período (Chart.js).

### 2. Clientes y Proveedores
- **Clientes**:
  - Cédula / RIF, Nombres, Apellidos, Teléfono (opcional), Correo (opcional), Dirección.
  - Tipo de cliente: Detal / Mayorista.
  - Historial de compras y vehículos registrados.
  - **Crédito (CXC)**: Límite de crédito asignado, días de crédito (7, 15, 30 días) y estado (Activo, Moroso, Bloqueado).
- **Proveedores**:
  - RIF / ID Fiscal, Razón Social, Contacto, Teléfono, Correo.
  - Términos y condiciones de crédito/pago (15, 30, 45, 60 días).
  - Límite de crédito otorgado y datos financieros / bancarios para transferencias rápidas.

### 3. Productos e Inventario (Especializado)
- **Repuestos**:
  - Código de barra, Código de parte / fabricante.
  - Categoría y Subcategoría, Marcas compatibles.
  - Ubicación física (Pasillo / Estante).
  - Stock mínimo, stock máximo y alertas automáticas de reabastecimiento.
  - Precios de venta configurables: Precio Detal, Precio Mayor, Precio Taller.
  - Manejo de impuestos (IVA general / exento).
  - Notas técnicas y especificaciones.
- **Vehículos (Motos)**:
  - Referencia, Marca, Modelo, Año, Color, Cilindrada.
  - Control riguroso por seriales únicos: NIV, Número de Chasis, Número de Motor.
  - Número de Placa, Número de Certificado de Origen y Número de Factura.
- **Movimientos / Kardex y Almacenes**:
  - Gestión de múltiples almacenes/sucursales (Nombre, Ubicación).
  - Registro de movimientos: Entradas, salidas, transferencias entre depósitos/almacenes.
  - Trazabilidad de motivos y usuario responsable del movimiento.
  - Capacidad de cargar stock y vender desde almacenes específicos.

### 4. Módulo de Ventas y Caja (POS)
- **Terminal POS**: Selección rápida de caja asignada por usuario.
- **Venta Rápida / Stock Crítico**: Permiso especial para autorizar salidas si el stock marca cero en contingencia.
- **Facturación / Documentos**: Emisión de tickets POS, facturas legales, notas de entrega y presupuestos/cotizaciones.
- **Multi-Moneda y Formas de Pago**: Pagos combinados/mixtos (Efectivo en USD/Bs, Transferencia, Pago Móvil, Punto de Venta/Tarjetas).
- **Historial de Facturas**: Consulta, anulación, reimpresión y filtro de transacciones.

### 5. Recepción de Mercancías (Compras)
- Registro de órdenes de compra y carga de facturas de proveedores.
- Actualización automática de stock en Kardex.
- Actualización opcional de costos de compra y recalculo de precios de venta.

### 6. Cuentas por Cobrar (CXC) & Cuentas por Pagar (CXP)
- **CXC (Clientes)**: Control de saldos pendientes, abonos, cuentas vencidas y gestión de estados de mora.
- **CXP (Proveedores)**: Programación de pagos según vencimiento de factura, registro de pagos parciales y límites de crédito utilizados.

### 7. Reportes y Auditoría
- Cierre de caja: Corte X (parcial) y Corte Z (cierre final).
- Kardex detallado por producto/repuesto/moto.
- Reporte de ganancias y margen de utilidad.
- Inventario valorado y reporte de rendimiento por vendedor.

### 8. Gestión Multi-Empresa, Usuarios y Roles
- **Datos de la Empresa**: RIF, Nombre/Razón Social, Teléfono, Correo, Dirección, Logo, Moneda principal, Tasa de cambio del día, Configuración de IVA.
- **Roles y Permisos**:
  - `SuperAdmin`: Control de licencias y empresas abonadas.
  - `ClienteAdmin`: Administración general de la empresa.
  - `ClienteNormal`: Permisos granulares por módulo (ej. solo facturar, solo ver inventario, recepcionar).

---

## 🎨 Catálogo Oficial de Iconos FontAwesome Disponibles

Para asegurar compatibilidad con la plantilla UI, se deben usar únicamente los siguientes iconos disponibles:

### Solid Icons (`fas fa-*`)
`fas fa-address-book`, `fas fa-address-card`, `fas fa-adjust`, `fas fa-align-center`, `fas fa-align-justify`, `fas fa-align-left`, `fas fa-align-right`, `fas fa-allergies`, `fas fa-ambulance`, `fas fa-anchor`, `fas fa-archive`, `fas fa-arrow-alt-circle-down`, `fas fa-arrow-alt-circle-left`, `fas fa-arrow-alt-circle-right`, `fas fa-arrow-alt-circle-up`, `fas fa-arrow-circle-down`, `fas fa-arrow-circle-left`, `fas fa-arrow-circle-right`, `fas fa-arrow-circle-up`, `fas fa-arrow-down`, `fas fa-arrow-left`, `fas fa-arrow-right`, `fas fa-arrow-up`, `fas fa-arrows-alt`, `fas fa-arrows-alt-h`, `fas fa-arrows-alt-v`, `fas fa-asterisk`, `fas fa-at`, `fas fa-backward`, `fas fa-balance-scale`, `fas fa-ban`, `fas fa-band-aid`, `fas fa-barcode`, `fas fa-bars`, `fas fa-bed`, `fas fa-beer`, `fas fa-bell`, `fas fa-bell-slash`, `fas fa-bicycle`, `fas fa-binoculars`, `fas fa-birthday-cake`, `fas fa-blind`, `fas fa-bold`, `fas fa-bolt`, `fas fa-bomb`, `fas fa-book`, `fas fa-bookmark`, `fas fa-box`, `fas fa-box-open`, `fas fa-boxes`, `fas fa-braille`, `fas fa-briefcase`, `fas fa-bug`, `fas fa-building`, `fas fa-bullhorn`, `fas fa-bullseye`, `fas fa-bus`, `fas fa-calculator`, `fas fa-calendar`, `fas fa-calendar-alt`, `fas fa-calendar-check`, `fas fa-calendar-minus`, `fas fa-calendar-plus`, `fas fa-calendar-times`, `fas fa-camera`, `fas fa-camera-retro`, `fas fa-car`, `fas fa-cart-plus`, `fas fa-certificate`, `fas fa-chart-area`, `fas fa-chart-bar`, `fas fa-chart-line`, `fas fa-chart-pie`, `fas fa-check`, `fas fa-check-circle`, `fas fa-check-square`, `fas fa-chevron-circle-down`, `fas fa-chevron-circle-left`, `fas fa-chevron-circle-right`, `fas fa-chevron-circle-up`, `fas fa-chevron-down`, `fas fa-chevron-left`, `fas fa-chevron-right`, `fas fa-chevron-up`, `fas fa-child`, `fas fa-circle`, `fas fa-circle-notch`, `fas fa-clipboard`, `fas fa-clipboard-check`, `fas fa-clipboard-list`, `fas fa-clock`, `fas fa-clone`, `fas fa-cloud`, `fas fa-code`, `fas fa-code-branch`, `fas fa-coffee`, `fas fa-cog`, `fas fa-cogs`, `fas fa-columns`, `fas fa-comment`, `fas fa-comment-alt`, `fas fa-comment-dots`, `fas fa-comments`, `fas fa-compass`, `fas fa-compress`, `fas fa-copy`, `fas fa-copyright`, `fas fa-credit-card`, `fas fa-crosshairs`, `fas fa-cube`, `fas fa-cubes`, `fas fa-cut`, `fas fa-database`, `fas fa-desktop`, `fas fa-dollar-sign`, `fas fa-dolly`, `fas fa-dolly-flatbed`, `fas fa-donate`, `fas fa-dot-circle`, `fas fa-download`, `fas fa-edit`, `fas fa-ellipsis-h`, `fas fa-ellipsis-v`, `fas fa-envelope`, `fas fa-envelope-open`, `fas fa-eraser`, `fas fa-exchange-alt`, `fas fa-exclamation`, `fas fa-exclamation-circle`, `fas fa-exclamation-triangle`, `fas fa-expand`, `fas fa-external-link-alt`, `fas fa-eye`, `fas fa-eye-slash`, `fas fa-fast-backward`, `fas fa-fast-forward`, `fas fa-fax`, `fas fa-female`, `fas fa-file`, `fas fa-file-alt`, `fas fa-file-archive`, `fas fa-file-code`, `fas fa-file-excel`, `fas fa-file-image`, `fas fa-file-pdf`, `fas fa-file-invoice-dollar`, `fas fa-file-word`, `fas fa-film`, `fas fa-filter`, `fas fa-fire`, `fas fa-flag`, `fas fa-flask`, `fas fa-folder`, `fas fa-folder-open`, `fas fa-font`, `fas fa-forward`, `fas fa-frown`, `fas fa-gamepad`, `fas fa-gavel`, `fas fa-gem`, `fas fa-gift`, `fas fa-globe`, `fas fa-graduation-cap`, `fas fa-hand-holding`, `fas fa-hand-holding-usd`, `fas fa-hand-paper`, `fas fa-hand-point-down`, `fas fa-hand-point-left`, `fas fa-hand-point-right`, `fas fa-hand-point-up`, `fas fa-handshake`, `fas fa-hashtag`, `fas fa-hdd`, `fas fa-heading`, `fas fa-headphones`, `fas fa-heart`, `fas fa-history`, `fas fa-home`, `fas fa-hourglass`, `fas fa-id-badge`, `fas fa-id-card`, `fas fa-id-card-alt`, `fas fa-image`, `fas fa-images`, `fas fa-inbox`, `fas fa-indent`, `fas fa-industry`, `fas fa-info`, `fas fa-info-circle`, `fas fa-key`, `fas fa-keyboard`, `fas fa-language`, `fas fa-laptop`, `fas fa-leaf`, `fas fa-level-down-alt`, `fas fa-level-up-alt`, `fas fa-lightbulb`, `fas fa-link`, `fas fa-list`, `fas fa-list-alt`, `fas fa-list-ol`, `fas fa-list-ul`, `fas fa-lock`, `fas fa-lock-open`, `fas fa-magic`, `fas fa-magnet`, `fas fa-male`, `fas fa-map`, `fas fa-map-marker`, `fas fa-map-marker-alt`, `fas fa-map-pin`, `fas fa-microchip`, `fas fa-microphone`, `fas fa-minus`, `fas fa-minus-circle`, `fas fa-minus-square`, `fas fa-mobile`, `fas fa-mobile-alt`, `fas fa-moon`, `fas fa-motorcycle`, `fas fa-mouse-pointer`, `fas fa-newspaper`, `fas fa-outdent`, `fas fa-paint-brush`, `fas fa-pallet`, `fas fa-paper-plane`, `fas fa-paperclip`, `fas fa-paste`, `fas fa-pause`, `fas fa-pen-square`, `fas fa-pencil-alt`, `fas fa-percent`, `fas fa-phone`, `fas fa-phone-slash`, `fas fa-phone-square`, `fas fa-piggy-bank`, `fas fa-play`, `fas fa-plug`, `fas fa-plus`, `fas fa-plus-circle`, `fas fa-plus-square`, `fas fa-print`, `fas fa-qrcode`, `fas fa-question`, `fas fa-question-circle`, `fas fa-random`, `fas fa-recycle`, `fas fa-redo`, `fas fa-redo-alt`, `fas fa-reply`, `fas fa-road`, `fas fa-rocket`, `fas fa-save`, `fas fa-search`, `fas fa-search-minus`, `fas fa-search-plus`, `fas fa-server`, `fas fa-share`, `fas fa-share-alt`, `fas fa-share-square`, `fas fa-shield-alt`, `fas fa-shipping-fast`, `fas fa-shopping-bag`, `fas fa-shopping-basket`, `fas fa-shopping-cart`, `fas fa-sign-in-alt`, `fas fa-sign-out-alt`, `fas fa-sliders-h`, `fas fa-smile`, `fas fa-snowflake`, `fas fa-spinner`, `fas fa-square`, `fas fa-star`, `fas fa-sticky-note`, `fas fa-stop`, `fas fa-stopwatch`, `fas fa-sync`, `fas fa-sync-alt`, `fas fa-table`, `fas fa-tablet`, `fas fa-tablet-alt`, `fas fa-tag`, `fas fa-tags`, `fas fa-tasks`, `fas fa-terminal`, `fas fa-th`, `fas fa-th-large`, `fas fa-th-list`, `fas fa-thumbs-down`, `fas fa-thumbs-up`, `fas fa-ticket-alt`, `fas fa-times`, `fas fa-times-circle`, `fas fa-trash`, `fas fa-trash-alt`, `fas fa-tree`, `fas fa-trophy`, `fas fa-truck`, `fas fa-truck-loading`, `fas fa-truck-moving`, `fas fa-tv`, `fas fa-umbrella`, `fas fa-undo`, `fas fa-undo-alt`, `fas fa-unlock`, `fas fa-unlock-alt`, `fas fa-upload`, `fas fa-user`, `fas fa-user-circle`, `fas fa-user-plus`, `fas fa-user-secret`, `fas fa-user-shield`, `fas fa-user-tag`, `fas fa-user-tie`, `fas fa-users`, `fas fa-users-cog`, `fas fa-utensils`, `fas fa-video`, `fas fa-wallet`, `fas fa-warehouse`, `fas fa-wifi`, `fas fa-window-close`, `fas fa-wrench`.

---

## 📌 Guía para Nuevas Sesiones de IA / Desarrolladores

Al trabajar en este proyecto:
1. **Mantener la separación Front/API**: Los controladores deben retornar vistas Blade limpias para las rutas web, y endpoints AJAX/JSON para operaciones de datos (`store`, `update`, `destroy`, `getData`, etc.).
2. **Utilizar Componentes**: Ubicar componentes visuales en `resources/views/Sistema/components/`.
3. **Validación y Alertas**: Manejar respuestas con `SweetAlert2` y errores 422 con formato estándar.
4. **Iconografía**: Consultar la lista superior antes de agregar iconos a menús, botones o títulos.
