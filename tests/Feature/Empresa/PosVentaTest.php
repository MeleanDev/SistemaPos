<?php

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\Kardex;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\ProductoStockAlmacen;
use App\Models\User;
use App\Models\Venta;
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
