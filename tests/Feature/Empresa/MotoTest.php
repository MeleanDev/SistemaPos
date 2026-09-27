<?php

use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\Moto;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->empresaA = Empresa::create([
        'rif' => 'J-111111111',
        'nombre' => 'Moto Corp A',
        'razon_social' => 'Moto Corp A C.A.',
        'direccion' => 'Sede A',
        'maneja_motos' => true,
        'estado' => true,
    ]);

    $this->empresaB = Empresa::create([
        'rif' => 'J-222222222',
        'nombre' => 'Moto Corp B',
        'razon_social' => 'Moto Corp B C.A.',
        'direccion' => 'Sede B',
        'maneja_motos' => true,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $this->empresaA->id,
        'codigo' => 'VES',
        'nombre' => 'Bolívares',
        'simbolo' => 'Bs.',
        'tasa_cambio' => 1.0,
        'es_principal' => true,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $this->empresaA->id,
        'codigo' => 'USD',
        'nombre' => 'Dólares',
        'simbolo' => '$',
        'tasa_cambio' => 60.00,
        'es_principal' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $this->empresaB->id,
        'codigo' => 'VES',
        'nombre' => 'Bolívares',
        'simbolo' => 'Bs.',
        'tasa_cambio' => 1.0,
        'es_principal' => true,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $this->empresaB->id,
        'codigo' => 'USD',
        'nombre' => 'Dólares',
        'simbolo' => '$',
        'tasa_cambio' => 60.00,
        'es_principal' => false,
        'estado' => true,
    ]);

    $this->almacenA = Almacen::create([
        'empresa_id' => $this->empresaA->id,
        'nombre' => 'Almacén Principal A',
        'codigo' => 'ALM-A',
        'estado' => true,
    ]);

    $this->almacenB = Almacen::create([
        'empresa_id' => $this->empresaB->id,
        'nombre' => 'Almacén Principal B',
        'codigo' => 'ALM-B',
        'estado' => true,
    ]);

    $this->proveedorA = Proveedor::create([
        'empresa_id' => $this->empresaA->id,
        'rif' => 'J-999999991',
        'nombre' => 'Ensambladora Bera A',
        'razon_social' => 'Ensambladora Bera A C.A.',
        'telefono' => '04140000001',
        'estado' => true,
    ]);

    $this->proveedorB = Proveedor::create([
        'empresa_id' => $this->empresaB->id,
        'rif' => 'J-999999992',
        'nombre' => 'Ensambladora Empire B',
        'razon_social' => 'Ensambladora Empire B C.A.',
        'telefono' => '04140000002',
        'estado' => true,
    ]);

    $this->userAdminA = User::create([
        'name' => 'V-11111111',
        'nombre' => 'Admin',
        'apellido' => 'Moto A',
        'email' => 'adminA@motos.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->userAdminA->assignRole('Admin');
    $this->userAdminA->empresas()->attach($this->empresaA->id, ['es_predeterminada' => true, 'estado' => true]);

    $this->userAdminB = User::create([
        'name' => 'V-22222222',
        'nombre' => 'Admin',
        'apellido' => 'Moto B',
        'email' => 'adminB@motos.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->userAdminB->assignRole('Admin');
    $this->userAdminB->empresas()->attach($this->empresaB->id, ['es_predeterminada' => true, 'estado' => true]);
});

test('moto index view loads correctly for authorized user', function () {
    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->get('/motos');

    $response->assertOk()
        ->assertViewIs('Sistema.pages.empresa.moto');
});

test('moto catalogos endpoint returns active warehouses and exchange rate', function () {
    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->getJson('/motos/catalogos');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'tasa_bcv' => 60,
                'moneda_simbolo' => 'Bs.',
            ],
        ])
        ->assertJsonCount(1, 'data.almacenes');
});

test('moto datatable list is properly scoped to the active tenant', function () {
    Moto::create([
        'empresa_id' => $this->empresaA->id,
        'almacen_id' => $this->almacenA->id,
        'proveedor_id' => $this->proveedorA->id,
        'referencia' => 'REF-001',
        'marca' => 'Bera',
        'modelo' => 'SBR 150',
        'anio' => 2025,
        'color' => 'Azul',
        'cilindrada' => '150cc',
        'certificado_origen' => 'CO-12345',
        'numero_niv' => 'NIV-EMPRESA-A-001',
        'numero_chasis' => 'CHASIS-A-001',
        'numero_motor' => 'MOTOR-A-001',
        'precio_costo_usd' => 800,
        'precio_costo_bs' => 48000,
        'precio_detal_usd' => 950,
        'precio_detal_bs' => 57000,
        'precio_mayorista_usd' => 900,
        'precio_mayorista_bs' => 54000,
        'estado' => 'disponible',
    ]);

    Moto::create([
        'empresa_id' => $this->empresaB->id,
        'almacen_id' => $this->almacenB->id,
        'proveedor_id' => $this->proveedorB->id,
        'referencia' => 'REF-002',
        'marca' => 'Empire',
        'modelo' => 'Keeway Horse',
        'anio' => 2025,
        'color' => 'Rojo',
        'cilindrada' => '150cc',
        'certificado_origen' => 'CO-12345',
        'numero_niv' => 'NIV-EMPRESA-B-001',
        'numero_chasis' => 'CHASIS-B-001',
        'numero_motor' => 'MOTOR-B-001',
        'precio_costo_usd' => 850,
        'precio_costo_bs' => 51000,
        'precio_detal_usd' => 1000,
        'precio_detal_bs' => 60000,
        'precio_mayorista_usd' => 950,
        'precio_mayorista_bs' => 57000,
        'estado' => 'disponible',
    ]);

    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->getJson('/motos/lista');

    $response->assertOk();
    $data = $response->json('data');
    expect(count($data))->toBe(1)
        ->and($data[0]['numero_niv'])->toBe('NIV-EMPRESA-A-001');
});

test('moto detail endpoint returns 360 data for company moto', function () {
    $moto = Moto::create([
        'empresa_id' => $this->empresaA->id,
        'almacen_id' => $this->almacenA->id,
        'proveedor_id' => $this->proveedorA->id,
        'referencia' => 'REF-YAM',
        'marca' => 'Yamaha',
        'modelo' => 'FZ-25',
        'anio' => 2024,
        'color' => 'Negro',
        'cilindrada' => '250cc',
        'certificado_origen' => 'CO-YAM-001',
        'numero_niv' => 'NIV-YAMAHA-001',
        'numero_chasis' => 'CHASIS-YAM-001',
        'numero_motor' => 'MOTOR-YAM-001',
        'precio_costo_usd' => 2500,
        'precio_costo_bs' => 150000,
        'precio_detal_usd' => 3200,
        'precio_detal_bs' => 192000,
        'precio_mayorista_usd' => 3000,
        'precio_mayorista_bs' => 180000,
        'estado' => 'disponible',
    ]);

    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->getJson("/motos/{$moto->id}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $moto->id,
                'numero_niv' => 'NIV-YAMAHA-001',
                'marca' => 'Yamaha',
            ],
        ]);

    $responseAlien = $this->actingAs($this->userAdminB)
        ->withSession(['empresa_activa_id' => $this->empresaB->id])
        ->getJson("/motos/{$moto->id}");

    $responseAlien->assertNotFound();
});

test('can update moto details and recalculate prices successfully', function () {
    $moto = Moto::create([
        'empresa_id' => $this->empresaA->id,
        'almacen_id' => $this->almacenA->id,
        'proveedor_id' => $this->proveedorA->id,
        'referencia' => 'REF-UPD',
        'marca' => 'Bera',
        'modelo' => 'SBR 150',
        'anio' => 2024,
        'color' => 'Verde',
        'cilindrada' => '150cc',
        'certificado_origen' => 'CO-12345',
        'numero_niv' => 'NIV-UPDATE-001',
        'numero_chasis' => 'CHASIS-UPD-001',
        'numero_motor' => 'MOTOR-UPD-001',
        'precio_costo_usd' => 700,
        'precio_costo_bs' => 42000,
        'precio_detal_usd' => 850,
        'precio_detal_bs' => 51000,
        'precio_mayorista_usd' => 800,
        'precio_mayorista_bs' => 48000,
        'estado' => 'disponible',
    ]);

    $payload = [
        'marca' => 'Bera Modificada',
        'modelo' => 'SBR 150 Pro',
        'referencia' => 'REF-999',
        'anio' => 2025,
        'color' => 'Negro Mate',
        'cilindrada' => '150cc',
        'certificado_origen' => 'CO-12345',
        'numero_niv' => 'NIV-UPDATE-001',
        'numero_chasis' => 'CHASIS-UPD-001',
        'numero_motor' => 'MOTOR-UPD-001',
        'certificado_origen' => 'CO-998877',
        'placa' => 'AB1C23D',
        'almacen_id' => $this->almacenA->id,
        'estado' => 'reservada',
        'precio_costo_usd' => 750,
        'precio_detal_usd' => 950,
        'precio_mayorista_usd' => 900,
        'observaciones' => 'Vehículo reservado con abono inicial.',
    ];

    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->putJson("/motos/actualizar/{$moto->id}", $payload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('motos', [
        'id' => $moto->id,
        'empresa_id' => $this->empresaA->id,
        'marca' => 'Bera Modificada',
        'modelo' => 'SBR 150 Pro',
        'color' => 'Negro Mate',
        'estado' => 'reservada',
        'precio_costo_usd' => 750,
        'precio_detal_usd' => 950,
        'precio_mayorista_usd' => 900,
    ]);
});

test('can change moto status independently', function () {
    $moto = Moto::create([
        'empresa_id' => $this->empresaA->id,
        'almacen_id' => $this->almacenA->id,
        'proveedor_id' => $this->proveedorA->id,
        'referencia' => 'REF-STAT',
        'marca' => 'Empire',
        'modelo' => 'Express',
        'anio' => 2024,
        'color' => 'Negro',
        'cilindrada' => '150cc',
        'certificado_origen' => 'CO-12345',
        'numero_niv' => 'NIV-STATUS-001',
        'numero_chasis' => 'CHASIS-STAT-001',
        'numero_motor' => 'MOTOR-STAT-001',
        'precio_detal_usd' => 900,
        'precio_mayorista_usd' => 850,
        'estado' => 'disponible',
    ]);

    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->postJson("/motos/{$moto->id}/cambiar-estado", [
            'estado' => 'en_mantenimiento',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'estado' => 'en_mantenimiento',
            ],
        ]);

    $this->assertDatabaseHas('motos', [
        'id' => $moto->id,
        'estado' => 'en_mantenimiento',
    ]);
});
