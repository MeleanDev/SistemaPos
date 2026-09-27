<?php

use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('usuario puede guardar un borrador de recepcion de mercancia y recuperarlo', function () {
    $empresa = Empresa::create([
        'rif' => 'J-12345678-0',
        'nombre' => 'Comercializadora Central',
        'razon_social' => 'Comercializadora Central C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-CENTRAL',
        'nombre' => 'Almacén Central',
        'tipo' => 'principal',
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-99887766-5',
        'nombre' => 'Distribuidora Polar',
        'razon_social' => 'Distribuidora Polar C.A.',
        'direccion' => 'Zona Industrial',
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $payloadBorrador = [
        'referencia' => 'Borrador Factura FAC-1001 (50 unidades)',
        'proveedor_id' => $proveedor->id,
        'numero_documento' => 'FAC-1001',
        'total_unidades' => 50,
        'datos_json' => [
            'numero_documento' => 'FAC-1001',
            'proveedor_id' => $proveedor->id,
            'almacen_id' => $almacen->id,
            'moneda_documento' => 'USD',
            'tasa_compra' => 50.00,
            'tasa_venta' => 50.00,
            'listaProductosCargados' => [
                [
                    'producto_id' => 1,
                    'cantidad' => 50,
                    'costo_unitario_usd' => 1.20,
                    'precio_detal_usd' => 1.80,
                ],
            ],
        ],
    ];

    // 1. Guardar Borrador
    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/recepciones/borradores', $payloadBorrador);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $borradorId = $response->json('data.id');

    $this->assertDatabaseHas('recepciones_borradores', [
        'id' => $borradorId,
        'empresa_id' => $empresa->id,
        'numero_documento' => 'FAC-1001',
        'total_unidades' => 50,
    ]);

    // 2. Listar Borradores
    $responseList = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/recepciones/borradores');

    $responseList->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonCount(1, 'data');

    // 3. Recuperar Borrador
    $responseGet = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/recepciones/borradores/{$borradorId}");

    $responseGet->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $borradorId,
                'numero_documento' => 'FAC-1001',
                'total_unidades' => 50,
            ],
        ]);

    // 4. Actualizar Borrador
    $payloadBorrador['borrador_id'] = $borradorId;
    $payloadBorrador['total_unidades'] = 80;
    $payloadBorrador['referencia'] = 'Borrador Actualizado FAC-1001 (80 unidades)';

    $responseUpdate = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/recepciones/borradores', $payloadBorrador);

    $responseUpdate->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('recepciones_borradores', [
        'id' => $borradorId,
        'total_unidades' => 80,
        'referencia' => 'Borrador Actualizado FAC-1001 (80 unidades)',
    ]);

    // 5. Eliminar Borrador
    $responseDelete = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->deleteJson("/recepciones/borradores/{$borradorId}");

    $responseDelete->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('recepciones_borradores', [
        'id' => $borradorId,
    ]);
});
