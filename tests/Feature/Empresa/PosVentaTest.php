<?php

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\Kardex;
use App\Models\MetodoPago;
use App\Models\Moto;
use App\Models\Producto;
use App\Models\ProductoStockAlmacen;
use App\Models\Proveedor;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('vista principal de punto de venta carga correctamente', function () {
    $empresa = Empresa::create([
        'rif' => 'J-90000000-0',
        'nombre' => 'Empresa POS View Test',
        'razon_social' => 'Empresa POS View Test C.A.',
        'direccion' => 'Av. Central',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-90000000']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/pos');

    $response->assertOk()
        ->assertViewIs('Sistema.pages.empresa.pos')
        ->assertSee('Punto de Venta (POS)');
});

test('pos carga datos iniciales correctamente', function () {
    $empresa = Empresa::create([
        'rif' => 'J-90000001-1',
        'nombre' => 'Empresa POS Demo',
        'razon_social' => 'Empresa POS Demo C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'es_principal' => false,
        'tasa_cambio' => 36.5000,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-POS-1',
        'nombre' => 'Piso de Venta',
        'es_principal' => true,
        'estado' => true,
    ]);

    $metodo = MetodoPago::firstOrCreate([
        'nombre' => 'Efectivo Divisas ($)',
    ], [
        'descripcion' => 'Pago en dólares efectivo',
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-90000001']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/pos/datos');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'cliente_defecto',
                'almacenes',
                'metodos_pago',
                'tasa_usd',
                'proximo_codigo',
                'productos',
            ],
        ]);
});

test('pos procesa venta al contado descontando inventario y registrando kardex y pagos', function () {
    $empresa = Empresa::create([
        'rif' => 'J-90000002-2',
        'nombre' => 'Empresa POS Venta Contado',
        'razon_social' => 'Empresa POS Venta Contado C.A.',
        'direccion' => 'Av. Comercial',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'es_principal' => false,
        'tasa_cambio' => 40.0000,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-POS-2',
        'nombre' => 'Mostrador 1',
        'es_principal' => true,
        'estado' => true,
    ]);

    $metodoUsd = MetodoPago::firstOrCreate([
        'nombre' => 'Efectivo USD',
    ], ['estado' => true]);

    $metodoBs = MetodoPago::firstOrCreate([
        'nombre' => 'Pago Móvil VES',
    ], ['estado' => true]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-P1',
        'nombre' => 'Snacks',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-SNK-01',
        'nombre' => 'Papas Fritas 150g',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 1.00,
        'precio_costo_bs' => 40.00,
        'precio_detal_usd' => 2.00,
        'precio_detal_bs' => 80.00,
        'precio_mayorista_usd' => 1.50,
        'precio_mayorista_bs' => 60.00,
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'estado' => true,
    ]);

    // Stock inicial de 50 unidades
    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 50,
    ]);

    $cliente = Cliente::create([
        'cedula' => 'V-11223344',
        'nombre' => 'María',
        'apellido' => 'González',
        'telefono' => '0414-0001122',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-90000002']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // Venta de 5 unidades al detal a $2.00 c/u = $10.00 + IVA 16% ($1.60) = $11.60
    // Pagos: $10.00 Efectivo USD + Bs. 64.00 Pago Móvil (equiv $1.60 a tasa 40)
    $payload = [
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'tipo_venta' => 'detal',
        'tasa_cambio' => 40.0000,
        'condicion_pago' => 'contado',
        'items' => [
            [
                'producto_id' => $producto->id,
                'tipo_item' => 'producto',
                'cantidad' => 5,
                'precio_unitario_usd' => 2.00,
                'descuento_porcentaje' => 0,
                'almacen_id' => $almacen->id,
            ],
        ],
        'pagos' => [
            [
                'metodo_pago_id' => $metodoUsd->id,
                'moneda' => 'USD',
                'tasa_cambio' => 40.0000,
                'monto' => 10.00,
                'referencia' => 'Billete 10',
            ],
            [
                'metodo_pago_id' => $metodoBs->id,
                'moneda' => 'VES',
                'tasa_cambio' => 40.0000,
                'monto' => 64.00,
                'referencia' => 'PM-1234',
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $venta = Venta::with(['detalles', 'pagos'])->where('empresa_id', $empresa->id)->first();
    expect($venta)->not->toBeNull();
    expect((float) $venta->total_usd)->toBe(11.60);
    expect((float) $venta->total_bs)->toBe(464.00);
    expect((float) $venta->monto_pagado_usd)->toBe(11.60);
    expect((float) $venta->saldo_pendiente_usd)->toBe(0.00);
    expect($venta->condicion_pago)->toBe('contado');

    // Verificar descuento de stock en almacén: 50 - 5 = 45
    $stockActualizado = ProductoStockAlmacen::where('producto_id', $producto->id)
        ->where('almacen_id', $almacen->id)
        ->first();
    expect((float) $stockActualizado->cantidad_actual)->toBe(45.000);

    // Verificar asiento de Kardex salida_venta
    $kardex = Kardex::where('empresa_id', $empresa->id)
        ->where('producto_id', $producto->id)
        ->where('tipo_movimiento', 'salida_venta')
        ->first();
    expect($kardex)->not->toBeNull();
    expect((float) $kardex->cantidad)->toBe(5.000);
    expect((float) $kardex->stock_anterior)->toBe(50.000);
    expect((float) $kardex->stock_nuevo)->toBe(45.000);
});

test('pos procesa venta a credito y genera cuenta por cobrar vinculada', function () {
    $empresa = Empresa::create([
        'rif' => 'J-90000003-3',
        'nombre' => 'Empresa POS Credito',
        'razon_social' => 'Empresa POS Credito C.A.',
        'direccion' => 'Zona Comercial',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'es_principal' => false,
        'tasa_cambio' => 50.0000,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-POS-3',
        'nombre' => 'Bodega',
        'es_principal' => true,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-C1',
        'nombre' => 'Ferretería',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-TAL-01',
        'nombre' => 'Taladro Percutor 500W',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 30.00,
        'precio_costo_bs' => 1500.00,
        'precio_detal_usd' => 50.00,
        'precio_detal_bs' => 2500.00,
        'precio_mayorista_usd' => 45.00,
        'precio_mayorista_bs' => 2250.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 10,
    ]);

    $cliente = Cliente::create([
        'cedula' => 'V-99887766',
        'nombre' => 'Carlos',
        'apellido' => 'Mendoza',
        'telefono' => '0424-9988776',
        'tipo_cliente' => 'mayorista',
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-90000003']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // Venta al mayor a crédito: 2 taladros a $45 c/u = $90.00 (sin abono inicial)
    $payload = [
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'tipo_venta' => 'mayor',
        'tasa_cambio' => 50.0000,
        'condicion_pago' => 'credito',
        'dias_credito' => 30,
        'items' => [
            [
                'producto_id' => $producto->id,
                'tipo_item' => 'producto',
                'cantidad' => 2,
                'precio_unitario_usd' => 45.00,
                'descuento_porcentaje' => 0,
                'almacen_id' => $almacen->id,
            ],
        ],
        'pagos' => [],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $venta = Venta::with(['cuentaPorCobrar'])->where('empresa_id', $empresa->id)->first();
    expect($venta)->not->toBeNull();
    expect((float) $venta->total_usd)->toBe(90.00);
    expect((float) $venta->saldo_pendiente_usd)->toBe(90.00);
    expect($venta->condicion_pago)->toBe('credito');

    // Verificar Cuenta por Cobrar
    $cxc = $venta->cuentaPorCobrar;
    expect($cxc)->not->toBeNull();
    expect((float) $cxc->monto_total_usd)->toBe(90.00);
    expect((float) $cxc->saldo_pendiente_usd)->toBe(90.00);
    expect($cxc->estado)->toBe('pendiente');
});

test('pos procesa devolucion de factura y revierte el stock en kardex', function () {
    $empresa = Empresa::create([
        'rif' => 'J-90000004-4',
        'nombre' => 'Empresa POS Devolucion',
        'razon_social' => 'Empresa POS Devolucion C.A.',
        'direccion' => 'Av. Devoluciones',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'es_principal' => false,
        'tasa_cambio' => 40.0000,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-POS-4',
        'nombre' => 'Almacén Principal',
        'es_principal' => true,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-D1',
        'nombre' => 'Textil',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-CAM-01',
        'nombre' => 'Camisa Oxford M',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 10.00,
        'precio_costo_bs' => 400.00,
        'precio_detal_usd' => 20.00,
        'precio_detal_bs' => 800.00,
        'precio_mayorista_usd' => 15.00,
        'precio_mayorista_bs' => 600.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 20,
    ]);

    $cliente = Cliente::create([
        'cedula' => 'V-44556677',
        'nombre' => 'Pedro',
        'apellido' => 'Ramírez',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-90000004']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // 1. Crear venta de 4 camisas (Stock 20 -> 16)
    $payloadVenta = [
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'tipo_venta' => 'detal',
        'tasa_cambio' => 40.0000,
        'condicion_pago' => 'contado',
        'items' => [
            [
                'producto_id' => $producto->id,
                'tipo_item' => 'producto',
                'cantidad' => 4,
                'precio_unitario_usd' => 20.00,
                'descuento_porcentaje' => 0,
                'almacen_id' => $almacen->id,
            ],
        ],
        'pagos' => [],
    ];

    $resVenta = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $payloadVenta);

    $resVenta->assertOk();
    $venta = Venta::with(['detalles'])->where('empresa_id', $empresa->id)->first();
    $detalleId = $venta->detalles->first()->id;

    // Stock actual debe ser 16
    expect((float) ProductoStockAlmacen::where('producto_id', $producto->id)->where('almacen_id', $almacen->id)->value('cantidad_actual'))->toBe(16.000);

    // 2. Procesar devolución de 2 camisas (Stock 16 -> 18)
    $payloadDevolucion = [
        'venta_id' => $venta->id,
        'motivo' => 'Talla incorrecta solicitada por el cliente',
        'items' => [
            [
                'venta_detalle_id' => $detalleId,
                'cantidad' => 2,
            ],
        ],
    ];

    $resDev = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/devolucion/procesar', $payloadDevolucion);

    $resDev->assertOk()
        ->assertJsonPath('success', true);

    // Stock reintegrado: 16 + 2 = 18
    expect((float) ProductoStockAlmacen::where('producto_id', $producto->id)->where('almacen_id', $almacen->id)->value('cantidad_actual'))->toBe(18.000);

    // Verificar asiento de Kardex anulacion_venta
    $kardexDev = Kardex::where('empresa_id', $empresa->id)
        ->where('producto_id', $producto->id)
        ->where('tipo_movimiento', 'anulacion_venta')
        ->latest('id')
        ->first();

    expect($kardexDev)->not->toBeNull();
    expect((float) $kardexDev->cantidad)->toBe(2.000);
    expect((float) $kardexDev->stock_anterior)->toBe(16.000);
    expect((float) $kardexDev->stock_nuevo)->toBe(18.000);

    // Estado de la venta debe ser devuelta_parcial
    $ventaActualizada = Venta::find($venta->id);
    expect($ventaActualizada->estado)->toBe('devuelta_parcial');
});

test('pos procesa venta de moto con seriales cambiando estado a vendida y permitiendo devolucion a disponible', function () {
    $empresa = Empresa::create([
        'rif' => 'J-90000008-8',
        'nombre' => 'Moto & Service Hub POS',
        'razon_social' => 'Moto & Service Hub C.A.',
        'direccion' => 'Av. Libertador',
        'maneja_motos' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-MOTO-POS',
        'nombre' => 'Showroom Motos',
        'es_principal' => true,
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-12345678-0',
        'nombre' => 'Ensambladora Bera',
        'razon_social' => 'Bera Motors C.A.',
        'estado' => true,
    ]);

    $moto = Moto::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'referencia' => 'BR-150-2026',
        'marca' => 'Bera',
        'modelo' => 'SBR 150',
        'anio' => '2026',
        'color' => 'Azul Eléctrico',
        'cilindrada' => '150cc',
        'numero_niv' => 'BERA150NIV2026001',
        'numero_chasis' => 'CHASIS-BERA-001',
        'numero_motor' => 'MOTOR-BERA-001',
        'certificado_origen' => 'CERT-BERA-001',
        'precio_costo_usd' => 800.00,
        'precio_costo_bs' => 29200.00,
        'precio_detal_usd' => 1100.00,
        'precio_detal_bs' => 40150.00,
        'precio_mayorista_usd' => 1000.00,
        'precio_mayorista_bs' => 36500.00,
        'estado' => 'disponible',
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-18888999',
        'nombre' => 'Carlos',
        'apellido' => 'Mendoza',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodo = MetodoPago::firstOrCreate(['nombre' => 'Transferencia USD'], ['estado' => true]);

    $user = User::factory()->create(['name' => 'V-90000008']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // 1. Facturar la Moto en POS
    $payload = [
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'tipo_venta' => 'detal',
        'tasa_cambio' => 36.5000,
        'condicion_pago' => 'contado',
        'items' => [
            [
                'producto_id' => $moto->id,
                'tipo_item' => 'moto',
                'cantidad' => 1,
                'precio_unitario_usd' => 1100.00,
                'descuento_porcentaje' => 0,
                'almacen_id' => $almacen->id,
            ],
        ],
        'pagos' => [
            [
                'metodo_pago_id' => $metodo->id,
                'moneda' => 'USD',
                'tasa_cambio' => 1.0000,
                'monto' => 1276.00, // 1100 + 16% IVA = 1276
                'referencia' => 'TRF-MOTO-001',
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    // Verificar que la moto ahora está vendida
    expect($moto->fresh()->estado)->toBe('vendida');

    // Verificar detalle de la venta
    $venta = Venta::with('detalles')->where('empresa_id', $empresa->id)->first();
    expect($venta)->not->toBeNull();
    $det = $venta->detalles->first();
    expect($det->tipo_item)->toBe('moto');
    expect($det->moto_id)->toBe($moto->id);
    expect($det->serial_identificador)->toBe('BERA150NIV2026001');

    // 2. Procesar Devolución de la Moto
    $payloadDev = [
        'venta_id' => $venta->id,
        'motivo' => 'Cancelación de compra por el cliente',
        'items' => [
            [
                'venta_detalle_id' => $det->id,
                'cantidad' => 1,
            ],
        ],
    ];

    $resDev = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/devolucion/procesar', $payloadDev);

    $resDev->assertOk()->assertJsonPath('success', true);

    // Verificar que la moto regresa a disponible
    expect($moto->fresh()->estado)->toBe('disponible');
});

test('pos procesa venta de servicios correctamente', function () {
    $empresa = Empresa::create([
        'rif' => 'J-90000009-9',
        'nombre' => 'Taller Mecánico & Servicios',
        'razon_social' => 'Taller Mecánico & Servicios C.A.',
        'direccion' => 'Av. Sucre',
        'maneja_motos' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-SRV-POS',
        'nombre' => 'Principal',
        'es_principal' => true,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-MAN-01',
        'nombre' => 'Mantenimiento',
        'estado' => true,
    ]);

    $servicio = Servicio::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo' => 'SRV-MAN-001',
        'nombre' => 'Cambio de Aceite y Filtro',
        'precio_costo_usd' => 5.00,
        'precio_costo_bs' => 182.50,
        'precio_venta_usd' => 25.00,
        'precio_venta_bs' => 912.50,
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-22334455',
        'nombre' => 'Pedro',
        'apellido' => 'Pérez',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodo = MetodoPago::firstOrCreate(['nombre' => 'Efectivo Divisas ($)'], ['estado' => true]);

    $user = User::factory()->create(['name' => 'V-90000009']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $payload = [
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'tipo_venta' => 'detal',
        'tasa_cambio' => 36.5000,
        'condicion_pago' => 'contado',
        'items' => [
            [
                'producto_id' => $servicio->id,
                'tipo_item' => 'servicio',
                'cantidad' => 2,
                'precio_unitario_usd' => 25.00,
                'descuento_porcentaje' => 0,
                'almacen_id' => $almacen->id,
            ],
        ],
        'pagos' => [
            [
                'metodo_pago_id' => $metodo->id,
                'moneda' => 'USD',
                'tasa_cambio' => 1.0000,
                'monto' => 58.00, // (25 * 2) + 16% IVA = 58.00
                'referencia' => 'EF-SRV-001',
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $payload);

    $response->assertOk()->assertJsonPath('success', true);

    $venta = Venta::with('detalles')->where('empresa_id', $empresa->id)->first();
    expect($venta)->not->toBeNull();
    $det = $venta->detalles->first();
    expect($det->tipo_item)->toBe('servicio');
    expect($det->servicio_id)->toBe($servicio->id);
    expect($det->nombre_item)->toBe('Cambio de Aceite y Filtro');
    expect((float) $det->cantidad)->toBe(2.000);
    expect((float) $venta->iva_monto_usd)->toBe(8.00); // 16% de 50 = 8.00
});

test('pos procesa servicio exento de iva con 0 impuestos', function () {
    $empresa = Empresa::create([
        'rif' => 'J-90000010-0',
        'nombre' => 'Servicios Médicos & Asesorías',
        'razon_social' => 'Servicios Médicos & Asesorías C.A.',
        'direccion' => 'Av. Fuerzas Armadas',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-MED-POS',
        'nombre' => 'Consultorio',
        'es_principal' => true,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-MED-01',
        'nombre' => 'Consultas',
        'estado' => true,
    ]);

    // Servicio EXENTO de IVA
    $servicioExento = Servicio::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo' => 'SRV-CONS-001',
        'nombre' => 'Consulta Médica Especializada',
        'precio_costo_usd' => 0.00,
        'precio_costo_bs' => 0.00,
        'precio_venta_usd' => 40.00,
        'precio_venta_bs' => 1460.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0.00,
        'aplica_igtf' => false,
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-33445566',
        'nombre' => 'María',
        'apellido' => 'González',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodo = MetodoPago::firstOrCreate(['nombre' => 'Efectivo Divisas ($)'], ['estado' => true]);

    $user = User::factory()->create(['name' => 'V-90000010']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // 1. Verificar catálogo inicial de POS (aplica_iva = false)
    $resDatos = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/pos/datos');

    $resDatos->assertOk();
    $prodPos = collect($resDatos->json('data.productos'))->firstWhere('id', $servicioExento->id);
    expect($prodPos['aplica_iva'])->toBeFalse();
    expect((float) $prodPos['iva_porcentaje'])->toBe(0.0);

    // 2. Facturar el servicio exento
    $payload = [
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'tipo_venta' => 'detal',
        'tasa_cambio' => 36.5000,
        'condicion_pago' => 'contado',
        'items' => [
            [
                'producto_id' => $servicioExento->id,
                'tipo_item' => 'servicio',
                'cantidad' => 1,
                'precio_unitario_usd' => 40.00,
                'descuento_porcentaje' => 0,
                'almacen_id' => $almacen->id,
            ],
        ],
        'pagos' => [
            [
                'metodo_pago_id' => $metodo->id,
                'moneda' => 'USD',
                'tasa_cambio' => 1.0000,
                'monto' => 40.00, // Exactamente 40.00 sin IVA
                'referencia' => 'EF-CONS-001',
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $payload);

    $response->assertOk()->assertJsonPath('success', true);

    $venta = Venta::with('detalles')->where('empresa_id', $empresa->id)->first();
    expect($venta)->not->toBeNull();
    expect((float) $venta->iva_monto_usd)->toBe(0.00);
    expect((float) $venta->total_usd)->toBe(40.00);

    $det = $venta->detalles->first();
    expect($det->aplica_iva)->toBeFalse();
    expect((float) $det->iva_monto_usd)->toBe(0.00);
    expect((float) $det->subtotal_usd)->toBe(40.00);
});

test('pos puede pausar venta en espera, listarla y recuperarla con cliente y productos completos', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99911122-1',
        'nombre' => 'Empresa Cuentas Espera Test',
        'razon_social' => 'Empresa Cuentas Espera Test C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['estado' => true]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-99887766',
        'nombre' => 'María',
        'apellido' => 'González',
        'telefono' => '04121112233',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $payload = [
        'cliente_id' => $cliente->id,
        'cliente' => $cliente->toArray(),
        'tipo_venta' => 'detal',
        'nota_referencia' => 'Cliente María González',
        'total_usd' => 150.00,
        'total_bs' => 6000.00,
        'carrito' => [
            [
                'producto_id' => 10,
                'tipo_item' => 'producto',
                'almacen_id' => 1,
                'codigo' => 'PROD-001',
                'nombre' => 'Taladro Percutor',
                'unidad' => 'UND',
                'cantidad' => 2,
                'precio_unitario_usd' => 75.00,
                'precio_detal_usd' => 75.00,
                'precio_mayorista_usd' => 65.00,
                'aplica_iva' => true,
                'iva_porcentaje' => 16.0,
                'descuento_porcentaje' => 0,
                'subtotal_usd' => 150.00,
                'subtotal_bs' => 6000.00,
            ],
        ],
    ];

    // 1. Guardar en espera
    $resGuardar = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/en-espera/guardar', $payload);

    $resGuardar->assertOk()->assertJsonPath('success', true);
    $esperaId = $resGuardar->json('data.id');

    // 2. Listar cuentas en espera
    $resLista = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/pos/en-espera/lista');

    $resLista->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');

    // 3. Recuperar cuenta en espera
    $resRecuperar = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/pos/en-espera/{$esperaId}/recuperar");

    $resRecuperar->assertOk()->assertJsonPath('success', true);
    $recuperado = $resRecuperar->json('data');

    expect($recuperado['cliente']['nombre'])->toBe('María');
    expect($recuperado['carrito'])->toHaveCount(1);
    expect($recuperado['carrito'][0]['nombre'])->toBe('Taladro Percutor');
    expect((int) $recuperado['carrito'][0]['cantidad'])->toBe(2);
});

test('pos puede renderizar formato factura carta y formato ticket termico', function () {
    $empresa = Empresa::create([
        'rif' => 'J-507699633',
        'nombre' => 'MULTIREPUESTOS LA LIMPIA 2024, C.A.',
        'razon_social' => 'MULTIREPUESTOS LA LIMPIA 2024, C.A.',
        'direccion' => 'Av. 28 LOCAL NRO 59-120 SECTOR LA LIMPIA',
        'telefono' => '0424-6747438',
        'maneja_motos' => true,
        'estado' => true,
    ]);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['estado' => true]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-20442702',
        'nombre' => 'Karla Andreina',
        'apellido' => 'Pérez Briceño',
        'direccion' => 'Av Ppal Casa S/N La Concepción',
        'telefono' => '0424-6747438',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-01',
        'nombre' => 'Almacén Principal',
        'es_principal' => true,
        'estado' => true,
    ]);

    $venta = Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'user_id' => $user->id,
        'codigo' => 'VEN-01494',
        'numero_control' => '00001607',
        'tipo_venta' => 'detal',
        'moneda' => 'USD',
        'tasa_cambio' => 592.5200,
        'fecha_emision' => '2026-06-16',
        'hora_emision' => '10:30:00',
        'monto_bruto_usd' => 900.13,
        'monto_bruto_bs' => 533346.54,
        'subtotal_neto_usd' => 900.13,
        'subtotal_neto_bs' => 533346.54,
        'iva_monto_usd' => 139.87,
        'iva_monto_bs' => 82874.26,
        'total_usd' => 1040.00,
        'total_bs' => 616220.80,
        'condicion_pago' => 'contado',
        'monto_pagado_usd' => 1040.00,
        'monto_pagado_bs' => 616220.80,
        'estado' => 'completada',
    ]);

    VentaDetalle::create([
        'venta_id' => $venta->id,
        'almacen_id' => $almacen->id,
        'tipo_item' => 'producto',
        'nombre_item' => 'Derecho de Registro',
        'serial_identificador' => '01',
        'cantidad' => 1,
        'costo_unitario_usd' => 20.00,
        'costo_unitario_bs' => 11850.40,
        'precio_unitario_usd' => 25.96,
        'precio_unitario_bs' => 15382.40,
        'aplica_iva' => false,
        'iva_porcentaje' => 0.00,
        'iva_monto_usd' => 0.00,
        'iva_monto_bs' => 0.00,
        'subtotal_usd' => 25.96,
        'subtotal_bs' => 15382.40,
    ]);

    // 1. Probar Factura Carta
    $resCarta = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/pos/imprimir-carta/{$venta->id}");

    $resCarta->assertOk()
        ->assertSee('MULTIREPUESTOS LA LIMPIA 2024, C.A.')
        ->assertSee('N° CONTROL 00-')
        ->assertSee('00001607')
        ->assertSee('Karla Andreina')
        ->assertSee('Subtotal USD:')
        ->assertSee('Subtotal BS:')
        ->assertSee('Monto Exento USD:')
        ->assertSee('ESTA FACTURA VA SIN TACHADURAS NI ENMENDADURAS');

    // 2. Probar Ticket Térmico
    $resTicket = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/pos/imprimir-ticket/{$venta->id}");

    $resTicket->assertOk()
        ->assertSee('MULTIREPUESTOS LA LIMPIA 2024, C.A.')
        ->assertSee('FACTURA NRO:')
        ->assertSee('TOTAL USD:')
        ->assertSee('TOTAL BS:');
});
