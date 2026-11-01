<?php

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Service\Empresa\DocumentoImpresionClass;
use Database\Seeders\DemoEmpresasMultiplesSeeder;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->empresa = Empresa::create([
        'rif' => 'J-112233445',
        'nombre' => 'Comercializadora Impresion Test',
        'razon_social' => 'Comercializadora Impresion Test C.A.',
        'direccion' => 'Av. Principal Local 1',
        'estado' => true,
    ]);

    $this->user = User::create([
        'name' => 'V-11223344',
        'nombre' => 'Cajero',
        'apellido' => 'Test',
        'email' => 'cajero@impresion.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->user->assignRole('Admin');
    $this->user->empresas()->attach($this->empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $this->almacen = Almacen::create([
        'empresa_id' => $this->empresa->id,
        'codigo' => 'ALM-01',
        'nombre' => 'Almacén Central',
        'estado' => true,
    ]);

    $this->cliente = Cliente::create([
        'nombre' => 'Pedro',
        'apellido' => 'Pérez',
        'cedula' => 'V-99887766',
        'telefono' => '04141234567',
        'direccion' => 'Calle 5 con Carrera 2',
        'estado' => true,
    ]);

    $this->categoria = Categoria::create([
        'empresa_id' => $this->empresa->id,
        'codigo' => 'CAT-01',
        'nombre' => 'Repuestos',
        'estado' => true,
    ]);

    $this->producto = Producto::create([
        'empresa_id' => $this->empresa->id,
        'categoria_id' => $this->categoria->id,
        'codigo_interno' => 'PROD-TEST-01',
        'nombre' => 'Batería 12V 7A',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 10.00,
        'precio_detal_usd' => 15.00,
        'estado' => true,
    ]);

    $this->venta = Venta::create([
        'empresa_id' => $this->empresa->id,
        'cliente_id' => $this->cliente->id,
        'user_id' => $this->user->id,
        'almacen_id' => $this->almacen->id,
        'codigo' => 'FAC-TEST-0001',
        'numero_control' => '00-000001',
        'fecha_emision' => now()->toDateString(),
        'hora_emision' => now()->toTimeString(),
        'condicion_pago' => 'contado',
        'subtotal_usd' => 15.00,
        'descuento_usd' => 0.00,
        'iva_usd' => 0.00,
        'total_usd' => 15.00,
        'total_bs' => 600.00,
        'tasa_dia' => 40.00,
        'estado' => 'completada',
    ]);

    VentaDetalle::create([
        'venta_id' => $this->venta->id,
        'almacen_id' => $this->almacen->id,
        'producto_id' => $this->producto->id,
        'cantidad' => 1,
        'precio_unitario_usd' => 15.00,
        'subtotal_usd' => 15.00,
        'precio_unitario_bs' => 600.00,
        'subtotal_bs' => 600.00,
        'tipo_item' => 'producto',
    ]);
});

test('vista de factura carta se renderiza con marca de agua oficial y datos de empresa', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->get("/pos/imprimir-carta/{$this->venta->id}");

    $response->assertOk()
        ->assertSee('system-watermark-container')
        ->assertSee('Comercializadora Impresion Test')
        ->assertSee('FAC-TEST-0001')
        ->assertSee('Batería 12V 7A');
});

test('vista de hoja de garantia se renderiza con marca de agua y terminos', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->get("/pos/garantia/{$this->venta->id}");

    $response->assertOk()
        ->assertSee('system-watermark-container')
        ->assertSee('TÉRMINOS Y CONDICIONES GENERALES DE GARANTÍA')
        ->assertSee('FAC-TEST-0001')
        ->assertSee('Batería 12V 7A');
});

test('descarga directa de factura en pdf funciona correctamente', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->get("/pos/descargar-pdf/{$this->venta->id}");

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('descarga directa de hoja de garantia en pdf funciona correctamente', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->get("/pos/garantia-pdf/{$this->venta->id}");

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('si existe plantilla personalizada por empresa se utiliza como maxima prioridad', function () {
    $empresaDir = resource_path("views/Sistema/impresiones/empresas/empresa_{$this->empresa->id}");
    if (! file_exists($empresaDir)) {
        mkdir($empresaDir, 0755, true);
    }

    $customBlade = $empresaDir.'/factura.blade.php';
    file_put_contents($customBlade, "@extends('Sistema.impresiones.layouts.documento_base')\n@section('contenido_documento')<div id=\"custom-tenant-template\">DISEÑO EXCLUSIVO EMPRESA CUSTOM</div>@endsection");

    try {
        $response = $this->actingAs($this->user)
            ->withSession(['empresa_activa_id' => $this->empresa->id])
            ->get("/pos/imprimir-carta/{$this->venta->id}");

        $response->assertOk()
            ->assertSee('system-watermark-container')
            ->assertSee('custom-tenant-template')
            ->assertSee('DISEÑO EXCLUSIVO EMPRESA CUSTOM');
    } finally {
        if (file_exists($customBlade)) {
            unlink($customBlade);
        }
        if (is_dir($empresaDir)) {
            rmdir($empresaDir);
        }
    }
});

test('flujo retail clasico de megastore renderiza items, vendedor y garantia', function () {
    $this->seed(DemoEmpresasMultiplesSeeder::class);

    $empresa = Empresa::where('rif', 'J-405398434')->firstOrFail();
    $this->user->empresas()->syncWithoutDetaching([$empresa->id => ['es_predeterminada' => false, 'estado' => true]]);
    $venta = Venta::where('empresa_id', $empresa->id)->where('codigo', 'F00024760')->firstOrFail();

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/pos/imprimir-carta/{$venta->id}");

    $response->assertOk()
        ->assertSee('system-watermark-container')
        ->assertSee('MEGASTORE')
        ->assertSee('ALEXANDER')
        ->assertSee('NAYBETH MARIN')
        ->assertSee('CABLE VOLTAGE ELECTRONICS')
        ->assertSee('SN-VTCC6B-8891');

    $resGarantia = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/pos/garantia/{$venta->id}");

    $resGarantia->assertOk()
        ->assertSee('system-watermark-container')
        ->assertSee('TÉRMINOS Y CONDICIONES GENERALES DE GARANTÍA')
        ->assertSee('CABLE VOLTAGE ELECTRONICS');
});

test('flujo de concesionario con motos renderiza NIV, motor y garantia de vehiculo en formato forma libre', function () {
    $this->seed(DemoEmpresasMultiplesSeeder::class);

    $empresa = Empresa::where('rif', 'J-501889921')->firstOrFail();
    $this->user->empresas()->syncWithoutDetaching([$empresa->id => ['es_predeterminada' => false, 'estado' => true]]);
    $venta = Venta::where('empresa_id', $empresa->id)->where('codigo', 'MOTO-2026-0001')->firstOrFail();

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/pos/imprimir-carta/{$venta->id}");

    $response->assertOk()
        ->assertSee('Impresión: Factura Carta (Forma Libre Preimpresa)')
        ->assertSee('8X2BERASBR20269911')
        ->assertSee('162FMJ-26A08912')
        ->assertSee('CARLOS MENDOZA');

    $resGarantia = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/pos/garantia/{$venta->id}");

    $resGarantia->assertOk()
        ->assertSee('system-watermark-container')
        ->assertSee('IDENTIFICACIÓN TÉCNICA DEL VEHÍCULO')
        ->assertSee('8X2BERASBR20269911')
        ->assertSee('162FMJ-26A08912');
});

test('empresa con id 2 utiliza su plantilla exclusiva personalizada en empresa_2', function () {
    // Si la empresa es ID 2, DocumentoImpresionClass debe resolver a Sistema.impresiones.empresas.empresa_2.factura
    $documentoService = app(DocumentoImpresionClass::class);
    $vista = $documentoService->resolverVistaFactura(2);

    expect($vista)->toBe('Sistema.impresiones.empresas.empresa_2.factura');
});
