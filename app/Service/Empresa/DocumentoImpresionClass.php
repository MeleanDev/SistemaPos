<?php

namespace App\Service\Empresa;

use App\Models\Empresa;
use App\Models\Venta;

class DocumentoImpresionClass
{
    /**
     * Obtener configuración documental por defecto para una empresa
     */
    public function obtenerConfiguracion(int $empresaId): object
    {
        return (object) [
            'empresa_id' => $empresaId,
            'plantilla_factura' => 'comercial_clasica',
            'tamano_papel' => 'letter',
            'margen_superior_mm' => 12,
            'color_primario' => '#2563eb',
            'alineacion_logo' => 'left',
            'mostrar_membrete' => true,
            'mostrar_codigo_producto' => true,
            'mostrar_marca' => true,
            'mostrar_motos_detalles' => true,
            'mostrar_equivalencia_bs' => true,
            'mostrar_metodos_pago' => true,
            'mostrar_qr' => false,
            'requiere_firma_recibido' => true,
            'nota_pie_factura' => 'Comprobante de entrega mercantil. Conserve su factura para cualquier trámite o reclamo de garantía.',
            'garantia_activa' => true,
            'garantia_imprimir_automatica' => false,
            'garantia_dias_defecto' => 30,
            'garantia_requiere_firma' => true,
            'garantia_titulo' => 'TÉRMINOS Y CONDICIONES GENERALES DE GARANTÍA',
            'garantia_clausulas' => null,
        ];
    }

    /**
     * Resolver la vista Blade correspondiente a la factura.
     * Prioridad:
     * 1. Plantilla exclusiva diseñada a la medida: Sistema.impresiones.empresas.empresa_{id}.factura
     * 2. Si la empresa vende motos (maneja_motos == true), plantilla legal Forma Libre de Motos
     * 3. Plantilla comercial clásica estándar (retail general)
     * 4. Fallback de seguridad al diseño base
     */
    public function resolverVistaFactura(int $empresaId): string
    {
        // 1. MÁXIMA PRIORIDAD: Plantilla exclusiva hecha a mano para la empresa (por ID)
        $vistaExclusiva = "Sistema.impresiones.empresas.empresa_{$empresaId}.factura";
        if (view()->exists($vistaExclusiva)) {
            return $vistaExclusiva;
        }

        // 2. PROTECCIÓN MOTOS: Si la empresa maneja motos, usar siempre la plantilla legal de Forma Libre
        $empresa = Empresa::find($empresaId);
        if ($empresa && $empresa->maneja_motos) {
            return 'Sistema.pages.empresa.factura-carta';
        }

        // 3. Plantilla comercial clásica estándar inspirada en retail real
        if (view()->exists('Sistema.impresiones.facturas.comercial_clasica')) {
            return 'Sistema.impresiones.facturas.comercial_clasica';
        }

        // 4. Fallback de seguridad al diseño base
        return 'Sistema.pages.empresa.factura-carta';
    }

    /**
     * Resolver la vista Blade para la hoja de garantías.
     * Prioridad:
     * 1. Plantilla exclusiva diseñada a mano para la empresa: Sistema.impresiones.empresas.empresa_{id}.garantia
     * 2. Hoja de garantías estándar del sistema (con cláusulas, exclusiones y renglones de seriales y firma)
     */
    public function resolverVistaGarantia(int $empresaId): string
    {
        // 1. Plantilla exclusiva de garantía por empresa
        $vistaExclusiva = "Sistema.impresiones.empresas.empresa_{$empresaId}.garantia";
        if (view()->exists($vistaExclusiva)) {
            return $vistaExclusiva;
        }

        return 'Sistema.impresiones.garantias.hoja-garantia';
    }

    /**
     * Estructurar datos necesarios para la vista de la factura
     */
    public function datosParaFactura(Venta $venta, ?object $config = null, bool $esPdf = false): array
    {
        $empresa = $venta->empresa ?? Empresa::find($venta->empresa_id);
        $config = $config ?? $this->obtenerConfiguracion($venta->empresa_id);

        return [
            'venta' => $venta,
            'empresa' => $empresa,
            'config' => $config,
            'colorPrimario' => $config->color_primario ?? '#2563eb',
            'marcaAguaRuta' => public_path('estilos/imgPropio/logo.png'),
            'esPdf' => $esPdf,
        ];
    }

    /**
     * Estructurar datos necesarios para la hoja de garantías
     */
    public function datosParaGarantia(Venta $venta, ?object $config = null, bool $esPdf = false): array
    {
        $empresa = $venta->empresa ?? Empresa::find($venta->empresa_id);
        $config = $config ?? $this->obtenerConfiguracion($venta->empresa_id);

        return [
            'venta' => $venta,
            'empresa' => $empresa,
            'config' => $config,
            'colorPrimario' => $config->color_primario ?? '#2563eb',
            'marcaAguaRuta' => public_path('estilos/imgPropio/logo.png'),
            'esPdf' => $esPdf,
        ];
    }
}
