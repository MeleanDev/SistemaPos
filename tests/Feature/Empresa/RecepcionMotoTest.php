<?php

use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('empresa con maneja_motos en false es redirigida limpiamente a recepcion general', function () {
    $empresa = Empresa::create([
        'rif' => 'J-12345678-0',
        'nombre' => 'Tienda General',
        'razon_social' => 'Tienda General C.A.',
        'direccion' => 'Calle Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get(route('recepcion_moto'));

    $response->assertRedirect(route('recepcion'));
});

test('empresa con maneja_motos en true puede acceder y procesar recepcion de motos con seriales', function () {
    $empresa = Empresa::create([
        'rif' => 'J-98765432-1',
        'nombre' => 'Motos La Central',
        'razon_social' => 'Motos La Central C.A.',
        'direccion' => 'Av. Intercomunal',
        'maneja_motos' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-CENTRAL',
        'nombre' => 'Almacén Principal',
        'tipo' => 'principal',
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-55555555-5',
        'nombre' => 'Ensambladora Bera',
        'razon_social' => 'Ensambladora Bera C.A.',
        'direccion' => 'Zona Industrial',
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get(route('recepcion_moto'))
        ->assertStatus(200);

    // Procesar recepción de lote con 2 motos
    $payload = [
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'tipo_documento' => 'factura',
        'numero_documento' => 'FACT-BERA-001',
        'numero_control' => '00-001',
        'moneda_documento' => 'USD',
        'tasa_cambio' => 50.00,
        'fecha_emision' => now()->toDateString(),
        'fecha_recepcion' => now()->toDateString(),
        'condicion_pago' => 'contado',
        'monto_bruto_usd' => 1600.00,
        'detalles' => [
            [
                'referencia' => 'SBR-150-2025',
                'marca' => 'Bera',
                'modelo' => 'SBR 150',
                'anio' => '2025',
                'color' => 'Negro',
                'cilindrada' => '150cc',
                'cantidad' => 2,
                'costo_unitario_usd' => 800.00,
                'descuento_porcentaje' => 0,
                'aplica_iva' => true,
                'iva_porcentaje' => 16.00,
                'margen_detal' => 25.00,
                'precio_detal_usd' => 1000.00,
                'margen_mayorista' => 15.00,
                'precio_mayorista_usd' => 920.00,
                'seriales' => [
                    [
                        'numero_niv' => '8A1BERA150SBR0001',
                        'numero_chasis' => 'CHASIS-BERA-001',
                        'numero_motor' => 'MOTOR-BERA-001',
                        'certificado_origen' => 'CERT-0001',
                        'placa' => null,
                        'almacen_id' => $almacen->id,
                    ],
                    [
                        'numero_niv' => '8A1BERA150SBR0002',
                        'numero_chasis' => 'CHASIS-BERA-002',
                        'numero_motor' => 'MOTOR-BERA-002',
                        'certificado_origen' => 'CERT-0002',
                        'placa' => null,
                        'almacen_id' => $almacen->id,
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('recepcion_moto'), $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('recepcion_motos', [
        'empresa_id' => $empresa->id,
        'numero_documento' => 'FACT-BERA-001',
        'total_unidades' => 2,
        'estado' => 'procesada',
    ]);

    $this->assertDatabaseHas('motos', [
        'empresa_id' => $empresa->id,
        'numero_niv' => '8A1BERA150SBR0001',
        'numero_chasis' => 'CHASIS-BERA-001',
        'estado' => 'disponible',
    ]);

    $this->assertDatabaseHas('motos', [
        'empresa_id' => $empresa->id,
        'numero_niv' => '8A1BERA150SBR0002',
        'numero_chasis' => 'CHASIS-BERA-002',
        'estado' => 'disponible',
    ]);
});

test('puede procesar recepcion de factura con multiples modelos de motos en lotes distintos', function () {
    $empresa = Empresa::create([
        'rif' => 'J-11223344-5',
        'nombre' => 'Concesionario Multi Marcas',
        'razon_social' => 'Concesionario Multi Marcas C.A.',
        'direccion' => 'Zona Comercial',
        'maneja_motos' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-MULTI',
        'nombre' => 'Almacén Central',
        'tipo' => 'principal',
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-99887766-0',
        'nombre' => 'Distribuidora Global Motos',
        'razon_social' => 'Distribuidora Global Motos C.A.',
        'direccion' => 'Zona Industrial',
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    // Factura con 2 modelos distintos: 2 Bera SBR + 2 Kavak Leon
    $payload = [
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'tipo_documento' => 'factura',
        'numero_documento' => 'FACT-MULTI-2025',
        'numero_control' => '00-0089',
        'moneda_documento' => 'USD',
        'tasa_cambio' => 50.00,
        'fecha_emision' => now()->toDateString(),
        'fecha_recepcion' => now()->toDateString(),
        'condicion_pago' => 'contado',
        'monto_bruto_usd' => 3600.00,
        'detalles' => [
            [
                'referencia' => 'BERA-SBR-150',
                'marca' => 'Bera',
                'modelo' => 'SBR 150',
                'anio' => '2025',
                'color' => 'Azul',
                'cilindrada' => '150cc',
                'cantidad' => 2,
                'costo_unitario_usd' => 800.00,
                'descuento_porcentaje' => 0,
                'aplica_iva' => true,
                'iva_porcentaje' => 16.00,
                'margen_detal' => 25.00,
                'precio_detal_usd' => 1000.00,
                'margen_mayorista' => 15.00,
                'precio_mayorista_usd' => 920.00,
                'seriales' => [
                    [
                        'numero_niv' => '8A1BERA150SBR9901',
                        'numero_chasis' => 'CHASIS-BERA-9901',
                        'numero_motor' => 'MOTOR-BERA-9901',
                        'certificado_origen' => 'CERT-BERA-9901',
                        'placa' => null,
                        'almacen_id' => $almacen->id,
                    ],
                    [
                        'numero_niv' => '8A1BERA150SBR9902',
                        'numero_chasis' => 'CHASIS-BERA-9902',
                        'numero_motor' => 'MOTOR-BERA-9902',
                        'certificado_origen' => 'CERT-BERA-9902',
                        'placa' => null,
                        'almacen_id' => $almacen->id,
                    ],
                ],
            ],
            [
                'referencia' => 'KAVAK-LEON-150',
                'marca' => 'Kavak',
                'modelo' => 'Leon 150',
                'anio' => '2025',
                'color' => 'Rojo',
                'cilindrada' => '150cc',
                'cantidad' => 2,
                'costo_unitario_usd' => 1000.00,
                'descuento_porcentaje' => 0,
                'aplica_iva' => true,
                'iva_porcentaje' => 16.00,
                'margen_detal' => 25.00,
                'precio_detal_usd' => 1250.00,
                'margen_mayorista' => 15.00,
                'precio_mayorista_usd' => 1150.00,
                'seriales' => [
                    [
                        'numero_niv' => '8A1KAVAK150LEON01',
                        'numero_chasis' => 'CHASIS-KAVAK-01',
                        'numero_motor' => 'MOTOR-KAVAK-01',
                        'certificado_origen' => 'CERT-KAVAK-01',
                        'placa' => null,
                        'almacen_id' => $almacen->id,
                    ],
                    [
                        'numero_niv' => '8A1KAVAK150LEON02',
                        'numero_chasis' => 'CHASIS-KAVAK-02',
                        'numero_motor' => 'MOTOR-KAVAK-02',
                        'certificado_origen' => 'CERT-KAVAK-02',
                        'placa' => null,
                        'almacen_id' => $almacen->id,
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('recepcion_moto'), $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('recepcion_motos', [
        'empresa_id' => $empresa->id,
        'numero_documento' => 'FACT-MULTI-2025',
        'total_unidades' => 4,
        'estado' => 'procesada',
    ]);

    // Verificar que existen ambos detalles de renglones
    $this->assertDatabaseHas('recepcion_moto_detalles', [
        'marca' => 'Bera',
        'modelo' => 'SBR 150',
        'cantidad' => 2,
    ]);

    $this->assertDatabaseHas('recepcion_moto_detalles', [
        'marca' => 'Kavak',
        'modelo' => 'Leon 150',
        'cantidad' => 2,
    ]);

    // Verificar seriales de ambos modelos en la tabla motos
    $this->assertDatabaseHas('motos', [
        'empresa_id' => $empresa->id,
        'numero_niv' => '8A1BERA150SBR9901',
        'modelo' => 'SBR 150',
    ]);

    $this->assertDatabaseHas('motos', [
        'empresa_id' => $empresa->id,
        'numero_niv' => '8A1KAVAK150LEON01',
        'modelo' => 'Leon 150',
    ]);
});
