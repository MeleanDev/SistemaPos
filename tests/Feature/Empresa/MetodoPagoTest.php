<?php

use App\Models\Empresa;
use App\Models\MetodoPago;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->empresa = Empresa::create([
        'rif' => 'J-123456789',
        'nombre' => 'Empresa Test',
        'razon_social' => 'Empresa Test C.A.',
        'direccion' => 'Sede Principal',
        'estado' => true,
    ]);

    $this->user = User::create([
        'name' => 'V-99887766',
        'nombre' => 'Admin',
        'apellido' => 'MetodoPago',
        'email' => 'admin@metodopago.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->user->assignRole('Admin');
    $this->user->empresas()->attach($this->empresa->id, ['es_predeterminada' => true, 'estado' => true]);
});

test('payment methods index view loads correctly', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->get('/metodos-pago');

    $response->assertOk()
        ->assertViewIs('Sistema.pages.empresa.metodo_pago');
});

test('payment method is created successfully', function () {
    $payload = [
        'nombre' => 'Criptomoneda USDT',
        'descripcion' => 'Transferencia TRC20 / ERC20',
    ];

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/metodos-pago', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $metodo = MetodoPago::where('nombre', 'Criptomoneda USDT')->first();
    expect($metodo)->not->toBeNull()
        ->and($metodo->descripcion)->toBe('Transferencia TRC20 / ERC20')
        ->and($metodo->estado)->toBeTrue();
});

test('payment methods list returns active payment methods', function () {
    MetodoPago::create([
        'nombre' => 'Efectivo USD Test',
        'descripcion' => 'Dólares en físico',
        'estado' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->getJson('/metodos-pago/lista');

    $response->assertOk();
    $data = $response->json('data');
    $nombres = collect($data)->pluck('nombre')->all();

    expect($nombres)->toContain('Efectivo USD Test');
});

test('inactive payment method is reactivated on save', function () {
    $inactivo = MetodoPago::create([
        'nombre' => 'Zelle Inactivo',
        'descripcion' => 'Pago vía correo Zelle',
        'estado' => false,
    ]);

    $payload = [
        'nombre' => 'Zelle Inactivo',
        'descripcion' => 'Pago vía correo Zelle Reactivado',
    ];

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/metodos-pago', $payload);

    $response->assertOk();
    $inactivo->refresh();

    expect($inactivo->estado)->toBeTrue()
        ->and($inactivo->descripcion)->toBe('Pago vía correo Zelle Reactivado');
});
