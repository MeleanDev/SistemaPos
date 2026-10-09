<?php

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\ProductoStockAlmacen;
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
    $resPdfIng->assertOk();

    // 2. Excel Ingresos
    $resExcelIng = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/ingresos/excel');
    $resExcelIng->assertOk();

    // 3. PDF Créditos
    $resPdfCred = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/creditos/pdf');
    $resPdfCred->assertOk();

    // 4. Excel Créditos
    $resExcelCred = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/creditos/excel');
    $resExcelCred->assertOk();

    // 5. PDF Inventario
    $resPdfInv = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/inventario/pdf');
    $resPdfInv->assertOk();

    // 6. Excel Inventario
    $resExcelInv = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/inventario/excel');
    $resExcelInv->assertOk();

    // 7. PDF Rentabilidad
    $resPdfRent = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/rentabilidad/pdf');
    $resPdfRent->assertOk();

    // 8. Excel Rentabilidad
    $resExcelRent = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/rentabilidad/excel');
    $resExcelRent->assertOk();

    // 9. PDF Vendedores
    $resPdfVend = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/vendedores/pdf');
    $resPdfVend->assertOk();

    // 10. Excel Vendedores
    $resExcelVend = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/vendedores/excel');
    $resExcelVend->assertOk();
});

test('reporte de inventario y valorizacion calcula correctamente las cantidades fisicas y montos de costo y venta', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99000004-4',
        'nombre' => 'Stock Valuation Test Corp',
        'razon_social' => 'Stock Valuation Test Corp C.A.',
        'direccion' => 'Av. 5 de Julio',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-TEST',
        'nombre' => 'Piso de Venta',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-TEST',
        'nombre' => 'Aires',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'AIRE-001',
        'nombre' => 'Aire Acondicionado 12000 BTU',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 200.00,
        'precio_detal_usd' => 250.00,
        'stock_minimo' => 2,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 5,
        'cantidad_reservada' => 0,
        'ubicacion_pasillo' => 'Pasillo 1',
    ]);

    $user = User::factory()->create(['name' => 'V-99000004']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/inventario');

    $response->assertOk()
        ->assertJsonPath('data.kpis.total_items', 1)
        ->assertJsonPath('data.kpis.total_unidades', 5)
        ->assertJsonPath('data.kpis.valor_costo_usd', 1000)
        ->assertJsonPath('data.kpis.valor_venta_usd', 1250)
        ->assertJsonPath('data.kpis.margen_proyectado_usd', 250)
        ->assertJsonPath('data.inventario.0.stock_actual', 5)
        ->assertJsonPath('data.inventario.0.costo_subtotal_usd', 1000)
        ->assertJsonPath('data.inventario.0.venta_subtotal_usd', 1250)
        ->assertJsonPath('data.inventario.0.ubicacion', 'Pasillo 1');
});

test('reporte de stock por almacenes desglosa correctamente las existencias en cada almacen y filtra por productos seleccionados', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99000005-5',
        'nombre' => 'Multi Warehouse Test Corp',
        'razon_social' => 'Multi Warehouse Test Corp C.A.',
        'direccion' => 'Zona Portuaria',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $alm1 = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-01',
        'nombre' => 'Piso de Venta',
        'estado' => true,
    ]);

    $alm2 = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-02',
        'nombre' => 'Bodega Central',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-ELECTRO',
        'nombre' => 'Electrodomésticos',
        'estado' => true,
    ]);

    $prod1 = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PROD-A',
        'nombre' => 'Televisor Smart 50',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 300.00,
        'precio_detal_usd' => 450.00,
        'estado' => true,
    ]);

    $prod2 = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PROD-B',
        'nombre' => 'Nevera No Frost',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 500.00,
        'precio_detal_usd' => 700.00,
        'estado' => true,
    ]);

    // Prod1: 10 en Alm1, 15 en Alm2 = 25
    ProductoStockAlmacen::create([
        'producto_id' => $prod1->id,
        'almacen_id' => $alm1->id,
        'cantidad_actual' => 10,
        'cantidad_reservada' => 0,
    ]);
    ProductoStockAlmacen::create([
        'producto_id' => $prod1->id,
        'almacen_id' => $alm2->id,
        'cantidad_actual' => 15,
        'cantidad_reservada' => 0,
    ]);

    // Prod2: 5 en Alm1, 0 en Alm2 = 5
    ProductoStockAlmacen::create([
        'producto_id' => $prod2->id,
        'almacen_id' => $alm1->id,
        'cantidad_actual' => 5,
        'cantidad_reservada' => 0,
    ]);

    $user = User::factory()->create(['name' => 'V-99000005']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // Consulta global
    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/stock-almacenes');

    $response->assertOk()
        ->assertJsonPath('data.kpis.total_productos', 2)
        ->assertJsonPath('data.kpis.total_unidades', 30)
        ->assertJsonPath('data.kpis.total_almacenes', 2)
        ->assertJsonPath('data.kpis.productos_sin_stock', 0);

    // Consulta filtrando solo Prod1
    $responseFiltrado = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/stock-almacenes?producto_ids='.$prod1->id);

    $responseFiltrado->assertOk()
        ->assertJsonPath('data.kpis.total_productos', 1)
        ->assertJsonPath('data.kpis.total_unidades', 25)
        ->assertJsonPath('data.items.0.codigo_interno', 'PROD-A')
        ->assertJsonPath('data.items.0.stocks_por_almacen.'.$alm1->id, 10)
        ->assertJsonPath('data.items.0.stocks_por_almacen.'.$alm2->id, 15)
        ->assertJsonPath('data.items.0.stock_total', 25);

    // Consulta filtrando por categoria_ids
    $responseCat = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/stock-almacenes?categoria_ids='.$categoria->id);

    $responseCat->assertOk()
        ->assertJsonPath('data.kpis.total_productos', 2);

    // PDF y Excel endpoints
    $resPdf = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/stock-almacenes/pdf?producto_ids='.$prod1->id);
    $resPdf->assertOk();

    $resExcel = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/stock-almacenes/excel?producto_ids='.$prod1->id);
    $resExcel->assertOk();
});
