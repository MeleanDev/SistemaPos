<?php

use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->empresaA = Empresa::create([
        'rif' => 'J-111111111',
        'nombre' => 'Empresa A Central',
        'razon_social' => 'Empresa A Central C.A.',
        'direccion' => 'Sede A',
        'estado' => true,
    ]);

    $this->empresaB = Empresa::create([
        'rif' => 'J-222222222',
        'nombre' => 'Empresa B Sucursal',
        'razon_social' => 'Empresa B Sucursal C.A.',
        'direccion' => 'Sede B',
        'estado' => true,
    ]);

    $this->userAdminA = User::create([
        'name' => 'V-11112222',
        'nombre' => 'Admin',
        'apellido' => 'Empresa A',
        'email' => 'adminA@empresa.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->userAdminA->assignRole('Admin');
    $this->userAdminA->empresas()->attach($this->empresaA->id, ['es_predeterminada' => true, 'estado' => true]);

    $this->userAdminB = User::create([
        'name' => 'V-33334444',
        'nombre' => 'Admin',
        'apellido' => 'Empresa B',
        'email' => 'adminB@empresa.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->userAdminB->assignRole('Admin');
    $this->userAdminB->empresas()->attach($this->empresaB->id, ['es_predeterminada' => true, 'estado' => true]);
});

test('provider is created automatically scoped to active company', function () {
    $payload = [
        'rif' => 'J-998877665',
        'nombre' => 'Proveedor A1',
        'razon_social' => 'Proveedor A1 C.A.',
        'nombre_contacto' => 'Juan Perez',
        'telefono' => '+584121234567',
        'correo' => 'proveedora1@test.com',
        'direccion' => 'Zona Industrial A',
    ];

    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->postJson('/proveedores', $payload);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $proveedor = Proveedor::where('rif', 'J-998877665')->first();
    expect($proveedor)->not->toBeNull();
    expect($proveedor->empresa_id)->toBe($this->empresaA->id);
    expect($proveedor->nombre)->toBe('Proveedor A1');
});

test('providers list only returns records belonging to the active company', function () {
    $provA = Proveedor::create([
        'empresa_id' => $this->empresaA->id,
        'rif' => 'J-101010101',
        'nombre' => 'Proveedor Exclusivo A',
        'razon_social' => 'Proveedor Exclusivo A C.A.',
        'estado' => true,
    ]);

    $provB = Proveedor::create([
        'empresa_id' => $this->empresaB->id,
        'rif' => 'J-202020202',
        'nombre' => 'Proveedor Exclusivo B',
        'razon_social' => 'Proveedor Exclusivo B C.A.',
        'estado' => true,
    ]);

    // Admin A consulta lista
    $responseA = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->getJson('/proveedores/lista');

    $responseA->assertStatus(200);
    $dataA = $responseA->json('data');
    $nombresA = collect($dataA)->pluck('nombre')->all();

    expect($nombresA)->toContain('Proveedor Exclusivo A');
    expect($nombresA)->not->toContain('Proveedor Exclusivo B');

    // Admin B consulta lista
    $responseB = $this->actingAs($this->userAdminB)
        ->withSession(['empresa_activa_id' => $this->empresaB->id])
        ->getJson('/proveedores/lista');

    $responseB->assertStatus(200);
    $dataB = $responseB->json('data');
    $nombresB = collect($dataB)->pluck('nombre')->all();

    expect($nombresB)->toContain('Proveedor Exclusivo B');
    expect($nombresB)->not->toContain('Proveedor Exclusivo A');
});

test('inactive provider is reactivated within the same company', function () {
    $inactivo = Proveedor::create([
        'empresa_id' => $this->empresaA->id,
        'rif' => 'J-555666777',
        'nombre' => 'Proveedor Antiguo',
        'razon_social' => 'Proveedor Antiguo C.A.',
        'estado' => false,
    ]);

    $payload = [
        'rif' => 'J-555666777',
        'nombre' => 'Proveedor Reactivado',
        'razon_social' => 'Proveedor Reactivado C.A.',
        'nombre_contacto' => 'Nuevo Contacto',
        'telefono' => '+584149876543',
        'correo' => 'reactivado@empresa.com',
        'direccion' => 'Nueva Direccion',
    ];

    $response = $this->actingAs($this->userAdminA)
        ->withSession(['empresa_activa_id' => $this->empresaA->id])
        ->postJson('/proveedores', $payload);

    $response->assertStatus(200);
    $inactivo->refresh();

    expect($inactivo->estado)->toBeTrue();
    expect($inactivo->nombre)->toBe('Proveedor Reactivado');
});
