<?php

use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->empresa = Empresa::create([
        'rif' => 'J-123456780',
        'nombre' => 'Empresa Config Test',
        'razon_social' => 'Empresa Config Test C.A.',
        'direccion' => 'Zona Industrial Central',
        'estado' => true,
    ]);

    $this->user = User::create([
        'name' => 'V-88776655',
        'nombre' => 'Admin',
        'apellido' => 'Config',
        'email' => 'admin@configuracion.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->user->assignRole('Admin');
    $this->user->empresas()->attach($this->empresa->id, ['es_predeterminada' => true, 'estado' => true]);
});

test('configuration index view loads correctly', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->get('/configuracion');

    $response->assertOk()
        ->assertViewIs('Sistema.pages.empresa.configuracion');
});

test('configuration data returns company and currencies payload', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->getJson('/configuracion/datos');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.empresa.nombre', 'Empresa Config Test');

    expect(EmpresaMoneda::where('empresa_id', $this->empresa->id)->count())->toBeGreaterThan(0);
});

test('company profile is updated successfully', function () {
    Storage::fake('public');

    $payload = [
        'rif' => 'J-123456780',
        'nombre' => 'Empresa Modificada',
        'razon_social' => 'Empresa Modificada C.A.',
        'direccion' => 'Nueva Dirección Fiscal 123',
        'telefono' => '+584121234567',
        'correo' => 'info@modificada.com',
        'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
    ];

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->post('/configuracion/empresa', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->empresa->refresh();
    expect($this->empresa->nombre)->toBe('Empresa Modificada')
        ->and($this->empresa->direccion)->toBe('Nueva Dirección Fiscal 123')
        ->and($this->empresa->logo)->not->toBeNull();
});

test('currency exchange rates are updated successfully', function () {
    $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->getJson('/configuracion/datos');

    $payload = [
        'monedas' => [
            [
                'codigo' => 'USD',
                'tasa_cambio' => 45.5000,
                'estado' => true,
            ],
            [
                'codigo' => 'EUR',
                'tasa_cambio' => 48.2000,
                'estado' => true,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/configuracion/monedas', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $monedaUsd = EmpresaMoneda::where('empresa_id', $this->empresa->id)->where('codigo', 'USD')->first();
    expect(floatval($monedaUsd->tasa_cambio))->toBe(45.5);
});
