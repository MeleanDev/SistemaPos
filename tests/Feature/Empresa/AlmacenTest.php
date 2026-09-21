<?php

use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->empresaA = Empresa::create([
        'rif' => 'J-121212121',
        'nombre' => 'Almacen Corp A',
        'razon_social' => 'Almacen Corp A C.A.',
        'direccion' => 'Sede A',
        'estado' => true,
    ]);

    $this->empresaB = Empresa::create([
        'rif' => 'J-232323232',
        'nombre' => 'Almacen Corp B',
        'razon_social' => 'Almacen Corp B C.A.',
        'direccion' => 'Sede B',
        'estado' => true,
    ]);

    $this->userAdminA = User::create([
        'name' => 'V-10101010',
        'nombre' => 'Admin',
        'apellido' => 'Almacen A',
        'email' => 'adminA@almacen.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->userAdminA->assignRole('Admin');
    $this->userAdminA->empresas()->attach($this->empresaA->id, ['es_predeterminada' => true, 'estado' => true]);

    $this->userAdminB = User::create([
        'name' => 'V-20202020',
        'nombre' => 'Admin',
        'apellido' => 'Almacen B',
        'email' => 'adminB@almacen.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->userAdminB->assignRole('Admin');
    $this->userAdminB->empresas()->attach($this->empresaB->id, ['es_predeterminada' => true, 'estado' => true]);
});

test('warehouse index view loads correctly', function () {
    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->get('/almacenes');

    $response->assertOk()
        ->assertViewIs('Sistema.pages.empresa.almacen');
});

test('warehouse is created with multi-tenancy scoping', function () {
    $payload = [
        'codigo' => 'BOD-01',
        'nombre' => 'Bodega Central',
        'direccion' => 'Zona Industrial Galpón 4',
    ];

    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->postJson('/almacenes', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $almacen = Almacen::where('codigo', 'BOD-01')->first();
    expect($almacen)->not->toBeNull()
        ->and($almacen->empresa_id)->toBe($this->empresaA->id)
        ->and($almacen->nombre)->toBe('Bodega Central');
});

test('warehouses list only returns records belonging to the active company', function () {
    Almacen::create([
        'empresa_id' => $this->empresaA->id,
        'codigo' => 'ALM-A',
        'nombre' => 'Almacen Exclusivo A',
        'estado' => true,
    ]);

    Almacen::create([
        'empresa_id' => $this->empresaB->id,
        'codigo' => 'ALM-B',
        'nombre' => 'Almacen Exclusivo B',
        'estado' => true,
    ]);

    $responseA = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->getJson('/almacenes/lista');

    $responseA->assertOk();
    $dataA = $responseA->json('data');
    $codigosA = collect($dataA)->pluck('codigo')->all();

    expect($codigosA)->toContain('ALM-A')
        ->and($codigosA)->not->toContain('ALM-B');
});

test('inactive warehouse is reactivated within the same company', function () {
    $inactivo = Almacen::create([
        'empresa_id' => $this->empresaA->id,
        'codigo' => 'ALM-REACT',
        'nombre' => 'Almacen Inactivo',
        'estado' => false,
    ]);

    $payload = [
        'codigo' => 'ALM-REACT',
        'nombre' => 'Almacen Reactivado',
        'direccion' => 'Nueva Dirección',
    ];

    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->postJson('/almacenes', $payload);

    $response->assertOk();
    $inactivo->refresh();

    expect($inactivo->estado)->toBeTrue()
        ->and($inactivo->nombre)->toBe('Almacen Reactivado');
});
