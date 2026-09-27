<?php

use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\RecepcionMotoBorrador;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('usuario puede guardar un borrador de recepcion de motos y recuperarlo', function () {
    $empresa = Empresa::create([
        'rif' => 'J-11112222-3',
        'nombre' => 'Moto Concesionario VIP',
        'razon_social' => 'Moto Concesionario VIP C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-VIP',
        'nombre' => 'Almacén VIP',
        'tipo' => 'principal',
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-33334444-5',
        'nombre' => 'Distribuidora Empire Keeway',
        'razon_social' => 'Distribuidora Empire Keeway C.A.',
        'direccion' => 'Zona Industrial',
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $payloadBorrador = [
        'referencia' => 'Borrador Factura EK-9988 (10 unidades)',
        'proveedor_id' => $proveedor->id,
        'numero_documento' => 'EK-9988',
        'total_unidades' => 10,
        'datos_json' => [
            'numero_documento' => 'EK-9988',
            'proveedor_id' => $proveedor->id,
            'almacen_id' => $almacen->id,
            'moneda_documento' => 'USD',
            'tasa_compra' => 50.00,
            'tasa_venta' => 50.00,
            'lotesAgregados' => [
                [
                    'referencia' => '1',
                    'marca' => 'Empire',
                    'modelo' => 'Horse 150',
                    'cantidad' => 10,
                    'costo_unitario_usd' => 800.00,
                    'seriales' => [
                        ['numero_niv' => '8A1EMP150SBR0001', 'numero_chasis' => 'CHASIS-EMP-001', 'numero_motor' => 'MOTOR-EMP-001'],
                    ],
                ],
            ],
        ],
    ];

    // 1. Guardar Borrador
    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/recepciones-motos/borradores', $payloadBorrador);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $borradorId = $response->json('data.id');

    $this->assertDatabaseHas('recepcion_motos_borradores', [
        'id' => $borradorId,
        'empresa_id' => $empresa->id,
        'numero_documento' => 'EK-9988',
        'total_unidades' => 10,
    ]);

    // 2. Listar Borradores
    $responseList = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/recepciones-motos/borradores');

    $responseList->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonCount(1, 'data');

    // 3. Recuperar Borrador
    $responseGet = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/recepciones-motos/borradores/{$borradorId}");

    $responseGet->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $borradorId,
                'numero_documento' => 'EK-9988',
                'total_unidades' => 10,
            ],
        ]);

    // 4. Actualizar Borrador
    $payloadBorrador['borrador_id'] = $borradorId;
    $payloadBorrador['total_unidades'] = 15;
    $payloadBorrador['referencia'] = 'Borrador Actualizado EK-9988 (15 unidades)';

    $responseUpdate = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/recepciones-motos/borradores', $payloadBorrador);

    $responseUpdate->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('recepcion_motos_borradores', [
        'id' => $borradorId,
        'total_unidades' => 15,
        'referencia' => 'Borrador Actualizado EK-9988 (15 unidades)',
    ]);

    // 5. Eliminar Borrador
    $responseDelete = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->deleteJson("/recepciones-motos/borradores/{$borradorId}");

    $responseDelete->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('recepcion_motos_borradores', [
        'id' => $borradorId,
    ]);
});

test('procesar recepcion final elimina automaticamente el borrador vinculado', function () {
    $empresa = Empresa::create([
        'rif' => 'J-22223333-4',
        'nombre' => 'Motos Del Centro',
        'razon_social' => 'Motos Del Centro C.A.',
        'direccion' => 'Av. Bolívar',
        'maneja_motos' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-CENTRO',
        'nombre' => 'Almacén Centro',
        'tipo' => 'principal',
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-66667777-8',
        'nombre' => 'Bera Motors',
        'razon_social' => 'Bera Motors C.A.',
        'direccion' => 'Zona Industrial',
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $borrador = RecepcionMotoBorrador::create([
        'empresa_id' => $empresa->id,
        'user_id' => $user->id,
        'proveedor_id' => $proveedor->id,
        'referencia' => 'Borrador Final BERA-001',
        'numero_documento' => 'FACT-BERA-FINAL',
        'total_unidades' => 1,
        'datos_json' => ['test' => true],
    ]);

    $payloadFinal = [
        'borrador_id' => $borrador->id,
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'tipo_documento' => 'factura',
        'numero_documento' => 'FACT-BERA-FINAL',
        'numero_control' => '00-0099',
        'moneda_documento' => 'USD',
        'fecha_emision' => '2026-09-26',
        'fecha_recepcion' => '2026-09-26',
        'condicion_pago' => 'contado',
        'tasa_cambio' => 50.00,
        'tasa_compra' => 50.00,
        'tasa_venta' => 50.00,
        'monto_bruto_usd' => 986.00,
        'detalles' => [
            [
                'tipo_item' => 'moto',
                'referencia' => '1',
                'marca' => 'Bera',
                'modelo' => 'SBR 150',
                'anio' => '2026',
                'color' => 'Negro',
                'cilindrada' => '150cc',
                'cantidad' => 1,
                'costo_unitario_usd' => 850.00,
                'flete_unitario_usd' => 0,
                'descuento_porcentaje' => 0,
                'aplica_iva' => true,
                'iva_porcentaje' => 16.00,
                'margen_detal' => 20.00,
                'precio_detal_usd' => 1020.00,
                'margen_mayorista' => 15.00,
                'precio_mayorista_usd' => 977.50,
                'seriales' => [
                    [
                        'numero_niv' => '8A1BERAFINAL0001',
                        'numero_chasis' => 'CHASIS-FINAL-001',
                        'numero_motor' => 'MOTOR-FINAL-001',
                        'certificado_origen' => 'CERT-FINAL-001',
                        'placa' => null,
                        'almacen_id' => $almacen->id,
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('recepcion_moto'), $payloadFinal);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('recepcion_motos_borradores', [
        'id' => $borrador->id,
    ]);

    $this->assertDatabaseHas('recepcion_motos', [
        'empresa_id' => $empresa->id,
        'numero_documento' => 'FACT-BERA-FINAL',
    ]);
});
