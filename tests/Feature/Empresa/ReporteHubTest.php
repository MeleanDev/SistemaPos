<?php

use App\Models\Empresa;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('vista principal del centro de reportes carga correctamente', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99000001-1',
        'nombre' => 'Reportes View Test Corp',
        'razon_social' => 'Reportes View Test Corp C.A.',
        'direccion' => 'Av. Intercomunal',
        'maneja_motos' => false,
        'maneja_vendedores' => true,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-99000001']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes');

    $response->assertOk()
        ->assertViewIs('Sistema.pages.empresa.reportes')
        ->assertSee('Centro de Reportes', false)
        ->assertSee('Ingresos', false);
});

test('endpoints de reportes ingresos, creditos, inventario y rentabilidad responden exitosamente', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99000002-2',
        'nombre' => 'Reportes API Test Corp',
        'razon_social' => 'Reportes API Test Corp C.A.',
        'direccion' => 'Av. Libertador',
        'maneja_motos' => false,
        'maneja_vendedores' => true,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-99000002']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // 1. Ingresos
    $responseIngresos = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/ingresos');

    $responseIngresos->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'kpis' => [
                    'total_facturado_usd',
                    'total_facturado_bs',
                    'total_pagado_usd',
                    'total_pagado_bs',
                ],
                'desglose_metodos',
                'transacciones',
            ],
        ]);

    // 2. Créditos
    $responseCreditos = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/creditos');

    $responseCreditos->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'kpis' => [
                    'total_cxc_usd',
                    'total_cxp_usd',
                ],
                'antiguedad_cxc',
                'detalle_cxc',
                'detalle_cxp',
            ],
        ]);

    // 3. Inventario
    $responseInventario = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/inventario');

    $responseInventario->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'kpis' => [
                    'total_items',
                    'total_unidades',
                    'valor_costo_usd',
                    'valor_venta_usd',
                ],
                'inventario',
            ],
        ]);

    // 4. Rentabilidad
    $responseRentabilidad = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/rentabilidad');

    $responseRentabilidad->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'kpis' => [
                    'total_ingresos_usd',
                    'total_costo_usd',
                    'ganancia_bruta_usd',
                    'margen_bruto_porcentaje',
                ],
                'top_productos',
            ],
        ]);

    // 5. Vendedores JSON
    $responseVendedores = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/vendedores/json');

    $responseVendedores->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'kpis' => [
                    'total_ventas_usd',
                    'total_ventas_bs',
                    'total_comisiones_usd',
                    'total_comisiones_bs',
                    'conteo_facturas',
                    'top_vendedor',
                    'periodo_texto',
                ],
                'resumen',
            ],
        ]);
});

test('generacion de documentos pdf y descargas excel de reportes responden exitosamente', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99000003-3',
        'nombre' => 'Reportes Export Test Corp',
        'razon_social' => 'Reportes Export Test Corp C.A.',
        'direccion' => 'Zona Industrial',
        'maneja_motos' => false,
        'maneja_vendedores' => true,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-99000003']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // 1. PDF Ingresos
    $resPdfIng = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/ingresos/pdf');
    $resPdfIng->assertOk()->assertHeader('content-type', 'application/pdf');

    // 2. Excel Ingresos
    $resExcelIng = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/ingresos/excel');
    $resExcelIng->assertOk();

    // 3. PDF Créditos
    $resPdfCred = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/creditos/pdf');
    $resPdfCred->assertOk()->assertHeader('content-type', 'application/pdf');

    // 4. Excel Créditos
    $resExcelCred = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/creditos/excel');
    $resExcelCred->assertOk();

    // 5. PDF Inventario
    $resPdfInv = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/inventario/pdf');
    $resPdfInv->assertOk()->assertHeader('content-type', 'application/pdf');

    // 6. Excel Inventario
    $resExcelInv = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/inventario/excel');
    $resExcelInv->assertOk();

    // 7. PDF Rentabilidad
    $resPdfRent = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/rentabilidad/pdf');
    $resPdfRent->assertOk()->assertHeader('content-type', 'application/pdf');

    // 8. Excel Rentabilidad
    $resExcelRent = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/rentabilidad/excel');
    $resExcelRent->assertOk();

    // 9. PDF Vendedores
    $resPdfVend = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/vendedores/pdf');
    $resPdfVend->assertOk()->assertHeader('content-type', 'application/pdf');

    // 10. Excel Vendedores
    $resExcelVend = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/vendedores/excel');
    $resExcelVend->assertOk();
});
