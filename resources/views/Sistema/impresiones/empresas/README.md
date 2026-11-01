# 📄 Plantillas Personalizadas por Empresa (Bespoke Templates)

Esta carpeta permite asignar diseños exclusivos de facturas y hojas de garantías a cualquier empresa del sistema sin tocar la base de datos ni formularios innecesarios.

---

## 🚀 Convención de Nombres

Para que una empresa tenga su propio diseño personalizado, crea una carpeta con el ID de la empresa:

```
resources/views/Sistema/impresiones/empresas/
└── empresa_{ID}/
    ├── factura.blade.php   (Diseño exclusivo de Nota de Entrega / Factura)
    └── garantia.blade.php  (Diseño exclusivo de Hoja de Condiciones de Garantía)
```

Por ejemplo, para la empresa con `ID = 1`:
- `resources/views/Sistema/impresiones/empresas/empresa_1/factura.blade.php`
- `resources/views/Sistema/impresiones/empresas/empresa_1/garantia.blade.php`

---

## 🛡️ Reglas y Estándares

1. **Herencia del Layout Base:**
   Extiende siempre de `Sistema.impresiones.layouts.documento_base` para heredar automáticamente la **marca de agua inmutable del sistema**, los estilos CSS print-friendly y las barras de control (imprimir / descargar PDF):
   ```blade
   @extends('Sistema.impresiones.layouts.documento_base')

   @section('titulo', 'Nota de Entrega / Factura ' . ($venta->codigo ?? '#' . $venta->id))

   @section('contenido_documento')
       <!-- Tu diseño exclusivo HTML / CSS aquí -->
   @endsection
   ```

2. **Variables Disponibles en la Vista:**
   - `$venta`: Modelo Eloquent de la Venta (con relaciones `$venta->detalles`, `$venta->cliente`, `$venta->user`, etc.).
   - `$empresa`: Modelo Eloquent de la Empresa emisora.
   - `$colorPrimario`: Código HEX de color primario corporativo.
   - `$marcaAguaRuta`: Ruta del logotipo del sistema para la marca de agua.

3. **Fallback Automático:**
   Si una empresa **no** tiene su carpeta `empresa_{ID}`, el sistema utiliza automáticamente:
   - Factura: `Sistema.impresiones.facturas.comercial_clasica` (Diseño retail estándar de alta calidad, WhatsApp, Instagram, vendedor, sello de entregado, etc.).
   - Garantía: `Sistema.impresiones.garantias.hoja-garantia` (Cláusulas completas, causales de anulación, seriales y firma de conformidad).
