<?php

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Models\DevolucionVenta;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Queries\VentaNetaQuery;
use App\Service\Empresa\PanelPrincipalClass;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

    $permisos = [
        'reportes.ver',
        'reportes.financieros',
        'reportes.inventario',
        'reportes.vendedores',
        'cajas.arqueo',
        'ventas.anular',
    ];

    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
});

test('B-30: VentaNetaQuery y Criterio Global 1: la suma por vendedor mas sin vendedor equivale al total neto', function () {
    $empresa = Empresa::create([
        'rif' => 'J-30000001-1',
        'nombre' => 'Fase 3 Criterio 1 Corp',
        'razon_social' => 'Fase 3 Criterio 1 Corp C.A.',
        'direccion' => 'Zona Centro',
        'maneja_motos' => false,
        'maneja_vendedores' => true,
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'cedula' => '30000001',
        'nombre' => 'Cliente Test',
        'apellido' => 'Criterio 1',
        'telefono' => '04141234567',
        'direccion' => 'Calle 1',
        'tipo_cliente' => 'natural',
        'estado' => true,
    ]);

    $vendedorA = Vendedor::create([
        'empresa_id' => $empresa->id,
        'tipo_documento' => 'V',
        'documento' => '30000002',
        'nombre' => 'Vendedor Juan',
        'telefono' => '04141234568',
        'comision_porcentaje' => 5.00,
        'estado' => true,
    ]);

    $vendedorB = Vendedor::create([
        'empresa_id' => $empresa->id,
        'tipo_documento' => 'V',
        'documento' => '30000003',
        'nombre' => 'Vendedora Ana',
        'telefono' => '04141234569',
        'comision_porcentaje' => 3.00,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-30000001']);
    $user->empresas()->attach($empresa->id);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-TEST-1',
        'nombre' => 'Almacen Principal Test',
        'es_predeterminado' => true,
        'estado' => true,
    ]);

    $hoy = now()->toDateString();

    // Venta 1: Vendedor A, total $100. Tiene devolucion de $20 => neta $80
    $v1 = Venta::create([
        'empresa_id' => $empresa->id,
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'vendedor_id' => $vendedorA->id,
        'codigo' => 'TCK-001',
        'fecha_emision' => $hoy,
        'hora_emision' => '10:00:00',
        'total_usd' => 100,
        'total_bs' => 3600,
        'estado' => 'devuelta_parcial',
    ]);
    DevolucionVenta::create([
        'empresa_id' => $empresa->id,
        'venta_id' => $v1->id,
        'user_id' => $user->id,
        'codigo' => 'DEV-001',
        'total_devuelto_usd' => 20,
        'total_devuelto_bs' => 720,
        'motivo' => 'defecto',
        'tipo_reembolso' => 'efectivo',
    ]);

    // Venta 2: Vendedor B, total $50. Sin devolucion => neta $50
    Venta::create([
        'empresa_id' => $empresa->id,
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'vendedor_id' => $vendedorB->id,
        'codigo' => 'TCK-002',
        'fecha_emision' => $hoy,
        'hora_emision' => '11:00:00',
        'total_usd' => 50,
        'total_bs' => 1800,
        'estado' => 'completada',
    ]);

    // Venta 3: Sin vendedor, total $70. Tiene devolucion de $10 => neta $60
    $v3 = Venta::create([
        'empresa_id' => $empresa->id,
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'vendedor_id' => null,
        'codigo' => 'TCK-003',
        'fecha_emision' => $hoy,
        'hora_emision' => '12:00:00',
        'total_usd' => 70,
        'total_bs' => 2520,
        'estado' => 'devuelta_parcial',
    ]);
    DevolucionVenta::create([
        'empresa_id' => $empresa->id,
        'venta_id' => $v3->id,
        'user_id' => $user->id,
        'codigo' => 'DEV-002',
        'total_devuelto_usd' => 10,
        'total_devuelto_bs' => 360,
        'motivo' => 'cambio',
        'tipo_reembolso' => 'efectivo',
    ]);

    // Venta 4: Anulada de $300 (NO debe sumar en ventas válidas)
    Venta::create([
        'empresa_id' => $empresa->id,
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'vendedor_id' => $vendedorA->id,
        'codigo' => 'TCK-004',
        'fecha_emision' => $hoy,
        'hora_emision' => '13:00:00',
        'total_usd' => 300,
        'total_bs' => 10800,
        'estado' => 'anulada',
    ]);

    // 1. Totales globales via VentaNetaQuery
    $query = VentaNetaQuery::paraPeriodo($empresa->id, $hoy, $hoy);
    $totales = VentaNetaQuery::calcularTotales($query);

    expect($totales['total_bruto_usd'])->toEqual(220.00) // 100 + 50 + 70
        ->and($totales['total_devuelto_usd'])->toEqual(30.00) // 20 + 10
        ->and($totales['total_neto_usd'])->toEqual(190.00); // 80 + 50 + 60

    // 2. Desglose por vendedor via VentaNetaQuery
    $desglose = VentaNetaQuery::desglosePorVendedor($empresa->id, $hoy, $hoy);

    $sumaDesgloseUsd = round($desglose->sum('total_neto_usd'), 2);
    expect($sumaDesgloseUsd)->toEqual($totales['total_neto_usd']);

    $vendedorAFila = $desglose->firstWhere('vendedor_id', $vendedorA->id);
    expect((float) $vendedorAFila['total_neto_usd'])->toEqual(80.00);

    $vendedorBFila = $desglose->firstWhere('vendedor_id', $vendedorB->id);
    expect((float) $vendedorBFila['total_neto_usd'])->toEqual(50.00);

    $sinVendedorFila = $desglose->firstWhere('vendedor_id', null);
    expect((float) $sinVendedorFila['total_neto_usd'])->toEqual(60.00);
});

test('B-31: PanelPrincipal calcula CXC y CXP incluyendo parciales y pendientes', function () {
    $empresa = Empresa::create([
        'rif' => 'J-31000001-1',
        'nombre' => 'Fase 3 CXC CXP Corp',
        'razon_social' => 'Fase 3 CXC CXP Corp C.A.',
        'direccion' => 'Zona Este',
        'maneja_motos' => false,
        'maneja_vendedores' => false,
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'cedula' => '31000001',
        'nombre' => 'Cliente',
        'apellido' => 'CXC',
        'telefono' => '04141234567',
        'direccion' => 'Av. Principal',
        'tipo_cliente' => 'natural',
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-31000001']);
    $user->empresas()->attach($empresa->id);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-TEST-2',
        'nombre' => 'Almacen CXC Test',
        'es_predeterminado' => true,
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-31000002-2',
        'nombre' => 'Proveedor CXP',
        'razon_social' => 'Proveedor CXP C.A.',
        'telefono' => '04141234568',
        'direccion' => 'Av. Industrial',
        'estado' => true,
    ]);

    $venta = Venta::create([
        'empresa_id' => $empresa->id,
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'codigo' => 'TCK-CXC-1',
        'condicion_pago' => 'credito',
        'fecha_emision' => now()->toDateString(),
        'hora_emision' => '10:00:00',
        'total_usd' => 100,
        'total_bs' => 3600,
        'estado' => 'completada',
    ]);

    // CXC 1: Pendiente $40
    CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'venta_id' => $venta->id,
        'numero_factura' => 'FAC-001',
        'monto_total_usd' => 40,
        'monto_total_bs' => 1440,
        'saldo_pendiente_usd' => 40,
        'saldo_pendiente_bs' => 1440,
        'fecha_emision' => now()->toDateString(),
        'fecha_vencimiento' => now()->addDays(15)->toDateString(),
        'estado' => 'pendiente',
    ]);

    // CXC 2: Parcial (Original $60, pendiente $25)
    CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'venta_id' => $venta->id,
        'numero_factura' => 'FAC-002',
        'monto_total_usd' => 60,
        'monto_total_bs' => 2160,
        'saldo_pendiente_usd' => 25,
        'saldo_pendiente_bs' => 900,
        'fecha_emision' => now()->toDateString(),
        'fecha_vencimiento' => now()->addDays(15)->toDateString(),
        'estado' => 'parcial',
    ]);

    // CXC 3: Pagada (No debe sumar)
    CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'venta_id' => $venta->id,
        'numero_factura' => 'FAC-003',
        'monto_total_usd' => 50,
        'monto_total_bs' => 1800,
        'saldo_pendiente_usd' => 0,
        'saldo_pendiente_bs' => 0,
        'fecha_emision' => now()->toDateString(),
        'fecha_vencimiento' => now()->addDays(15)->toDateString(),
        'estado' => 'pagada',
    ]);

    // CXP 1: Pendiente $30
    CuentaPorPagar::create([
        'empresa_id' => $empresa->id,
        'proveedor_id' => $proveedor->id,
        'numero_factura' => 'CXP-001',
        'monto_total_usd' => 30,
        'monto_total_bs' => 1080,
        'saldo_pendiente_usd' => 30,
        'saldo_pendiente_bs' => 1080,
        'fecha_emision' => now()->toDateString(),
        'fecha_vencimiento' => now()->addDays(15)->toDateString(),
        'estado' => 'pendiente',
    ]);

    // CXP 2: Parcial $15
    CuentaPorPagar::create([
        'empresa_id' => $empresa->id,
        'proveedor_id' => $proveedor->id,
        'numero_factura' => 'CXP-002',
        'monto_total_usd' => 50,
        'monto_total_bs' => 1800,
        'saldo_pendiente_usd' => 15,
        'saldo_pendiente_bs' => 540,
        'fecha_emision' => now()->toDateString(),
        'fecha_vencimiento' => now()->addDays(15)->toDateString(),
        'estado' => 'parcial',
    ]);

    $user->assignRole('SuperAdmin');
    $panelService = app(PanelPrincipalClass::class);
    $datosDashboard = $panelService->obtenerDatosDashboard($empresa->id, $user);
    $kpis = $datosDashboard['kpis'];

    // CXC esperada: 40 + 25 = 65
    expect((float) $kpis['cxc_pendiente_usd'])->toEqual(65.00);

    // CXP esperada: 30 + 15 = 45
    expect((float) $kpis['cxp_pendiente_usd'])->toEqual(45.00);
});

test('B-32: Nuevos reportes responden en JSON, PDF y Excel', function () {
    $empresa = Empresa::create([
        'rif' => 'J-32000001-1',
        'nombre' => 'Fase 3 Nuevos Reportes Corp',
        'razon_social' => 'Fase 3 Nuevos Reportes Corp C.A.',
        'direccion' => 'Zona Comercial',
        'maneja_motos' => false,
        'maneja_vendedores' => true,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-32000001']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // 1. Cuadre Diario
    $resCuadre = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/cuadre-diario');
    $resCuadre->assertOk()->assertJsonStructure(['success', 'data' => ['kpis', 'turnos']]);

    $resCuadrePdf = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/cuadre-diario/pdf');
    $resCuadrePdf->assertOk();

    $resCuadreExcel = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/cuadre-diario/excel');
    $resCuadreExcel->assertOk();

    // 2. Devoluciones y Anulaciones
    $resDevAnul = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/devoluciones-anulaciones');
    $resDevAnul->assertOk()->assertJsonStructure(['success', 'data' => ['kpis', 'items']]);

    $resDevAnulPdf = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/devoluciones-anulaciones/pdf');
    $resDevAnulPdf->assertOk();

    $resDevAnulExcel = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/devoluciones-anulaciones/excel');
    $resDevAnulExcel->assertOk();

    // 3. Flujo de Caja
    $resFlujo = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/flujo-caja');
    $resFlujo->assertOk()->assertJsonStructure(['success', 'data' => ['kpis', 'desglose_metodos', 'transacciones']]);

    $resFlujoPdf = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/flujo-caja/pdf');
    $resFlujoPdf->assertOk();

    $resFlujoExcel = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/flujo-caja/excel');
    $resFlujoExcel->assertOk();

    // 4. Libro de Ventas
    $resLibro = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/libro-ventas');
    $resLibro->assertOk()->assertJsonStructure(['success', 'data' => ['kpis', 'items']]);

    $resLibroPdf = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/libro-ventas/pdf');
    $resLibroPdf->assertOk();

    $resLibroExcel = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/libro-ventas/excel');
    $resLibroExcel->assertOk();
});

test('B-33: Permisos individuales protegen reportes financieros y de inventario', function () {
    $empresa = Empresa::create([
        'rif' => 'J-33000001-1',
        'nombre' => 'Fase 3 Permissions Corp',
        'razon_social' => 'Fase 3 Permissions Corp C.A.',
        'direccion' => 'Zona Norte',
        'maneja_motos' => false,
        'maneja_vendedores' => false,
        'estado' => true,
    ]);

    // Usuario Cajero: solo tiene reportes.ver
    $cajero = User::factory()->create(['name' => 'V-33000001']);
    $cajero->empresas()->attach($empresa->id);
    $cajero->givePermissionTo('reportes.ver');

    // Cajero PUEDE ver ingresos y flujo de caja
    $this->actingAs($cajero)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/ingresos')
        ->assertOk();

    $this->actingAs($cajero)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/flujo-caja')
        ->assertOk();

    // Cajero NO PUEDE ver rentabilidad ni libro de ventas ni créditos (reportes.financieros) -> 403
    $this->actingAs($cajero)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/rentabilidad')
        ->assertForbidden();

    $this->actingAs($cajero)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/libro-ventas')
        ->assertForbidden();

    $this->actingAs($cajero)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/creditos')
        ->assertForbidden();

    // Cajero NO PUEDE ver inventario ni stock (reportes.inventario) -> 403
    $this->actingAs($cajero)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/inventario')
        ->assertForbidden();

    // Usuario con reportes.financieros puede ver rentabilidad
    $gerenteFinanzas = User::factory()->create(['name' => 'V-33000002']);
    $gerenteFinanzas->empresas()->attach($empresa->id);
    $gerenteFinanzas->givePermissionTo('reportes.financieros');

    $this->actingAs($gerenteFinanzas)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/rentabilidad')
        ->assertOk();

    $this->actingAs($gerenteFinanzas)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/libro-ventas')
        ->assertOk();

    // Usuario con reportes.inventario puede ver inventario
    $jefeAlmacen = User::factory()->create(['name' => 'V-33000003']);
    $jefeAlmacen->empresas()->attach($empresa->id);
    $jefeAlmacen->givePermissionTo('reportes.inventario');

    $this->actingAs($jefeAlmacen)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/inventario')
        ->assertOk();

    $this->actingAs($jefeAlmacen)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/stock-almacenes')
        ->assertOk();
});
