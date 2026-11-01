# 📋 Plan Técnico: Sistema de Personalización de Facturas y Hoja de Garantías por Empresa

Este documento contiene la planificación integral, arquitectura técnica y diseño funcional para permitir que **cada empresa personalice su propia plantilla de factura en PDF e imprima una Hoja de Condiciones Generales y Garantías independiente o anexa**.

---

## 🎯 1. Objetivos del Módulo

1. **Diseño Visual Parametrizado por Empresa**: Permitir a cada empresa configurar el aspecto de sus facturas (colores corporativos, disposición de logo, visibilidad de datos, columnas de la tabla y formatos de papel).
2. **Soporte de Papelería Preimpresa (Formas Libres SENIAT)**: Capacidad de ocultar el membrete corporativo y calibrar márgenes si la empresa usa papel fiscal con membrete físico de imprenta.
3. **Hoja de Condiciones Generales de Garantías**: Permitir que cada empresa defina sus políticas, exclusiones, tiempos de cobertura y firmas, imprimiéndose como hoja anexa (página 2) o como documento independiente.
4. **Módulo Especializado para Motos**: En empresas con `maneja_motos = true`, incluir automáticamente campos de NIV, serial de motor, chasis, color y kilometraje tanto en la factura como en el certificado de garantía.
5. **Cero Riesgos de Seguridad y Alta Estabilidad**: Utilizar un constructor modular estructurado (evitando inyecciones arbitrarias de código) compatible al 100% con `barryvdh/laravel-dompdf` y las vistas de impresión del navegador.
6. **🔒 Regla Inmutable: Marca de Agua Oficial del Sistema**: Sin importar la plantilla elegida, los colores o personalizaciones de la empresa, **la marca de agua del sistema (`public/estilos/imgPropio/logo.png`) debe estar SIEMPRE presente** de fondo en todas las facturas y hojas de garantía con opacidad controlada. Esta opción no puede ser desactivada ni alterada por ninguna empresa.

---

## 🧱 2. Arquitectura de Datos (Base de Datos)

Se propone crear una tabla dedicada 1 a 1 con `empresas`, o añadir columnas estructuradas en formato JSON / relacional para máxima escalabilidad:

### Opción Recomendada: Tabla `empresa_configuraciones_documentos`

```php
Schema::create('empresa_configuraciones_documentos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
    
    // --- 1. CONFIGURACIÓN DE FACTURA CARTA / PDF ---
    $table->string('plantilla_factura', 50)->default('moderna'); // moderna, clasica, minimalista, forma_libre
    $table->string('tamano_papel', 20)->default('letter');        // letter, half_letter, a4
    $table->string('color_primario', 10)->default('#2563eb');     // Color hexadecimal
    $table->string('alineacion_logo', 15)->default('left');       // left, center, right
    $table->boolean('mostrar_membrete')->default(true);           // false para papelería preimpresa
    $table->unsignedSmallInteger('margen_superior_mm')->default(0);// Calibración en mm
    
    // Columnas visibles en la tabla de ítems
    $table->boolean('mostrar_codigo_producto')->default(true);
    $table->boolean('mostrar_marca')->default(true);
    $table->boolean('mostrar_motos_detalles')->default(true);     // NIV, motor, chasis
    $table->boolean('mostrar_equivalencia_bs')->default(true);    // Doble moneda USD/Bs
    $table->boolean('mostrar_metodos_pago')->default(true);       // Desglose de formas de cobro
    $table->boolean('mostrar_qr')->default(true);                 // QR de validación
    $table->text('nota_pie_factura')->nullable();                 // Cuentas bancarias o notas breves
    $table->boolean('requiere_firma_recibido')->default(false);   // Líneas de firma
    
    // --- 2. CONFIGURACIÓN DE HOJA DE GARANTÍAS ---
    $table->boolean('garantia_activa')->default(true);            // Habilitar módulo de garantías
    $table->string('garantia_titulo', 150)->default('TÉRMINOS Y CONDICIONES GENERALES DE GARANTÍA');
    $table->longText('garantia_clausulas')->nullable();           // Texto enriquecido o markdown con cláusulas
    $table->unsignedSmallInteger('garantia_dias_defecto')->default(30); // Días continuos de garantía
    $table->boolean('garantia_imprimir_automatica')->default(false); // Anexar siempre tras factura
    $table->boolean('garantia_requiere_firma')->default(true);    // Bloque de firma cliente/empresa
    
    $table->timestamps();
});
```

---

## 🎨 3. Constructor Visual en la Interfaz (UI/UX)

Ubicado en una pestaña dedicada dentro de **Configuración de Empresa** (`resources/views/Sistema/pages/empresa/configuracion.blade.php`):

### 3.1 Pestaña: "Diseño de Facturas"
- **Barra de Opciones (Lado Izquierdo)**:
  - Selector de Estilo Base: *Moderna Ejecutiva*, *Clásica Formal*, *Forma Libre (Papel Preimpreso)*, *Media Carta*.
  - Paleta de Color: Selector interactivo de color primario corporativo.
  - Switches de Visibilidad:
    - `[x]` Mostrar logotipo y datos de empresa
    - `[x]` Mostrar equivalencia en Bolívares (Tasa Oficial)
    - `[x]` Mostrar seriales / NIV (para motos y repuestos)
    - `[x]` Desglose de pagos recibidos
    - `[x]` Código QR de validación
  - Campo de texto para Cuentas Bancarias / Mensaje al pie.
- **Previsualizador en Vivo (Lado Derecho)**:
  - Muestra una hoja tamaño carta interactiva que actualiza tipografías, colores y bloques en tiempo real mientras el usuario modifica los controles.

### 3.2 Pestaña: "Póliza de Garantías"
- Título personalizable del certificado.
- Editor de cláusulas (tiempo de garantía, condiciones de invalidación de garantía por mal uso, sellos rotos o fallas eléctricas).
- Opción de "Imprimir automáticamente con cada factura" o "Imprimir bajo demanda".
- Previsualizador del documento de garantía en hoja separada.

---

## 📄 4. Generación y Renderizado de Documentos

### 4.1 Estructura de Vistas Blade

```
resources/views/Sistema/impresiones/
├── facturas/
│   ├── moderna.blade.php            # Factura con acentos de color, logo y bloques redondeados
│   ├── clasica.blade.php            # Estilo contable tradicional en blanco y negro
│   ├── forma_libre.blade.php        # Solo cuerpo de datos para papel con membrete preimpreso
│   └── media_carta.blade.php        # Formato compacto horizontal (2 facturas por hoja)
└── garantias/
    ├── hoja-garantia.blade.php      # Documento oficial de términos de garantía
    └── certificado-moto.blade.php   # Garantía extendida con datos de chasis/motor/kilometraje
```

### 4.2 Endpoints y Rutas Backend (`routes/admin.php`)

```php
// Impresiones desde el POS / Facturas
Route::get('/pos/imprimir-carta/{id}', [PosController::class, 'imprimirCarta'])->name('pos.imprimir_carta');
Route::get('/pos/descargar-pdf/{id}', [PosController::class, 'descargarPdf'])->name('pos.descargar_pdf');

// Nueva ruta para Hoja de Garantías
Route::get('/pos/garantia/{id}', [PosController::class, 'imprimirGarantia'])->name('pos.imprimir_garantia');
Route::get('/pos/garantia-pdf/{id}', [PosController::class, 'descargarGarantiaPdf'])->name('pos.descargar_garantia_pdf');
```

### 4.3 Lógica en el Controlador (`PosController.php`)

```php
public function imprimirGarantia(string $id): View
{
    $empresaId = $this->obtenerEmpresaId();
    $venta = $this->ventaService->obtenerVentaParaImpresion($id, $empresaId);
    $configuracion = $this->obtenerConfiguracionDocumentos($empresaId);

    return view('Sistema.impresiones.garantias.hoja-garantia', compact('venta', 'configuracion'));
}

### 4.4 🔒 Marca de Agua del Sistema (Obligatoria e Inmutable)

Por directriz de plataforma, **todos los documentos emitidos (facturas, notas de entrega, formas libres y hojas de garantías)** deben incluir fijamente la marca de agua del sistema en el fondo:

- **Recurso Utilizado**: `public/estilos/imgPropio/logo.png`
- **Renderizado en DomPDF**:
  ```html
  <div class="system-watermark">
      <img src="{{ public_path('estilos/imgPropio/logo.png') }}" alt="Watermark">
  </div>
  ```
- **Estilos CSS Inyectados (Inalterables)**:
  ```css
  .system-watermark {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 420px;
      opacity: 0.045; /* Muy sutil, no interfiere con la lectura ni el código de barras */
      z-index: -1000;
      pointer-events: none;
      text-align: center;
  }
  .system-watermark img {
      width: 100%;
      height: auto;
      filter: grayscale(100%);
  }
  ```
- **Protección**: No existe ningún switch, toggle o campo en base de datos para apagar o remover esta marca de agua. Viene incrustada directamente en el layout base del cual extienden todas las plantillas.

---

## 🛡️ 5. Estándares de Diseño y Calidad (`pos-architecture`)

1. **Sin Contenedores Grises ni Bordes Excesivos**: El diseñador visual y las previsualizaciones deben usar tarjetas blancas (`bg-white`), bordes limpios y redondeados (`rounded-4`), y sombras sutiles (`shadow-xs` / `shadow-sm`).
2. **Aislamiento Multiempresa Estricto**: Toda lectura y modificación de la configuración de factura y garantías debe incluir el scope del tenant activo (`empresa_id`).
3. **Paginación Inteligente**: En facturas con múltiples páginas, las tablas CSS deben incluir `page-break-inside: avoid` y `thead { display: table-header-group; }` para que los encabezados se repitan limpiamente en cada página sin romperse.
4. **Preservación de Base de Datos**: Las migraciones deben ser aditivas (`add_..._to_...` o creación de tabla nueva sin afectar datos preexistentes).

---

## 📌 6. Próximos Pasos para la Ejecución

1. **Fase 1**: Crear la migración de base de datos con los parámetros de personalización y cláusulas de garantía.
2. **Fase 2**: Maquetar la plantilla base modular de la factura y la vista de la Hoja de Garantías.
3. **Fase 3**: Implementar los métodos de impresión y descarga PDF en `PosController` y `VentaClass`.
4. **Fase 4**: Añadir la interfaz visual de configuración y Live Preview en `configuracion.blade.php`.
5. **Fase 5**: Integrar el botón "Imprimir Garantía" en la vista de detalle de ventas (`facturas.blade.php`) y en la pantalla final de cobro del POS.
