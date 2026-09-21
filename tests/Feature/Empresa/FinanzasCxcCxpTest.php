<?php

use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorCobrarAbono;
use App\Models\CuentaPorPagar;
use App\Models\CuentaPorPagarAbono;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\MetodoPago;
use App\Models\Proveedor;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('vistas principales de cxc y cxp cargan correctamente', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000001-1',
        'nombre' => 'Finanzas View Test Corp',
        'razon_social' => 'Finanzas View Test Corp C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88000001']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $responseCxc = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/cuentas-por-cobrar');

    $responseCxc->assertOk()
        ->assertViewIs('Sistema.pages.empresa.cxc')
        ->assertSee('Cuentas por Cobrar (CXC)');

    $responseCxp = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/cuentas-por-pagar');

    $responseCxp->assertOk()
        ->assertViewIs('Sistema.pages.empresa.cxp')
        ->assertSee('Cuentas por Pagar (CXP)');
});

test('cxc lista agrupa correctamente los saldos por cliente y calcula kpis', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000002-2',
        'nombre' => 'CXC Grouping Corp',
        'razon_social' => 'CXC Grouping Corp C.A.',
        'direccion' => 'Av. Bolívar',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'tasa_respecto_moneda_base' => 1.0000,
        'es_moneda_base' => true,
        'permite_cobro' => true,
        'permite_vuelto' => true,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Bolívares Digitales',
        'codigo' => 'VES',
        'simbolo' => 'Bs.',
        'tasa_respecto_moneda_base' => 50.0000,
        'es_moneda_base' => false,
        'permite_cobro' => true,
        'permite_vuelto' => true,
        'estado' => true,
    ]);

    $clienteA = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-11111111',
        'nombre' => 'Carlos',
        'apellido' => 'Perez',
        'telefono' => '04141111111',
        'tipo_cliente' => 'detal',
        'limite_credito' => 500,
        'dias_credito' => 15,
        'estado' => true,
    ]);

    $clienteB = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-22222222',
        'nombre' => 'Maria',
        'apellido' => 'Gonzalez',
        'telefono' => '04142222222',
        'tipo_cliente' => 'mayorista',
        'limite_credito' => 1000,
        'dias_credito' => 30,
        'estado' => true,
    ]);

    // Crear facturas para Cliente A ($100 y $50 = $150 total)
    CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $clienteA->id,
        'numero_factura' => 'VEN-00001',
        'fecha_emision' => '2026-09-01',
        'fecha_vencimiento' => '2026-09-15',
        'monto_total_usd' => 100,
        'monto_total_bs' => 5000,
        'monto_pagado_usd' => 0,
        'monto_pagado_bs' => 0,
        'saldo_pendiente_usd' => 100,
        'saldo_pendiente_bs' => 5000,
        'estado' => 'pendiente',
    ]);

    CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $clienteA->id,
        'numero_factura' => 'VEN-00002',
        'fecha_emision' => '2026-09-10',
        'fecha_vencimiento' => '2026-09-25',
        'monto_total_usd' => 50,
        'monto_total_bs' => 2500,
        'monto_pagado_usd' => 0,
        'monto_pagado_bs' => 0,
        'saldo_pendiente_usd' => 50,
        'saldo_pendiente_bs' => 2500,
        'estado' => 'pendiente',
    ]);

    // Crear factura para Cliente B ($80)
    CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $clienteB->id,
        'numero_factura' => 'VEN-00003',
        'fecha_emision' => '2026-09-12',
        'fecha_vencimiento' => '2026-10-12',
        'monto_total_usd' => 80,
        'monto_total_bs' => 4000,
        'monto_pagado_usd' => 0,
        'monto_pagado_bs' => 0,
        'saldo_pendiente_usd' => 80,
        'saldo_pendiente_bs' => 4000,
        'estado' => 'pendiente',
    ]);

    $user = User::factory()->create(['name' => 'V-88000002']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/cuentas-por-cobrar/lista');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.kpis.total_por_cobrar_usd', 230)
        ->assertJsonPath('data.kpis.clientes_deudores_count', 2)
        ->assertJsonPath('data.kpis.facturas_pendientes_count', 3);

    $clientes = $response->json('data.clientes');
    expect($clientes[0]['cliente_nombre'])->toBe('Carlos Perez')
        ->and($clientes[0]['total_deuda_usd'])->toBe(150)
        ->and($clientes[0]['total_facturas_pendientes'])->toBe(2)
        ->and($clientes[1]['cliente_nombre'])->toBe('Maria Gonzalez')
        ->and($clientes[1]['total_deuda_usd'])->toBe(80)
        ->and($clientes[1]['total_facturas_pendientes'])->toBe(1);
});

test('abono a factura especifica cxc actualiza saldos y cambia de estado', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000003-3',
        'nombre' => 'Abono Especifico Corp',
        'razon_social' => 'Abono Especifico Corp C.A.',
        'direccion' => 'Calle 10',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $metodoPago = MetodoPago::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Efectivo',
        'tipo' => 'efectivo',
        'icono' => 'fas fa-dollar-sign',
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-33333333',
        'nombre' => 'Pedro',
        'apellido' => 'Infante',
        'telefono' => '04143333333',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $cxc = CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'numero_factura' => 'VEN-00010',
        'fecha_emision' => '2026-09-15',
        'fecha_vencimiento' => '2026-09-30',
        'monto_total_usd' => 100,
        'monto_total_bs' => 5000,
        'monto_pagado_usd' => 0,
        'monto_pagado_bs' => 0,
        'saldo_pendiente_usd' => 100,
        'saldo_pendiente_bs' => 5000,
        'estado' => 'pendiente',
    ]);

    $user = User::factory()->create(['name' => 'V-88000003']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // 1. Abono parcial de $40
    $resp1 = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cuentas-por-cobrar/abonar-factura', [
            'cuenta_id' => $cxc->id,
            'metodo_pago_id' => $metodoPago->id,
            'moneda' => 'USD',
            'monto' => 40,
            'tasa_cambio' => 50.0,
            'fecha_abono' => '2026-09-20',
            'referencia' => 'EFE-001',
        ]);

    $resp1->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.nuevo_saldo_usd', 60)
        ->assertJsonPath('data.nuevo_estado', 'parcial');

    $cxc->refresh();
    expect((float) $cxc->monto_pagado_usd)->toBe(40.0)
        ->and((float) $cxc->saldo_pendiente_usd)->toBe(60.0)
        ->and($cxc->estado)->toBe('parcial');

    // 2. Abono restante de $60 para liquidar
    $resp2 = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cuentas-por-cobrar/abonar-factura', [
            'cuenta_id' => $cxc->id,
            'metodo_pago_id' => $metodoPago->id,
            'moneda' => 'USD',
            'monto' => 60,
            'tasa_cambio' => 50.0,
            'fecha_abono' => '2026-09-21',
            'referencia' => 'EFE-002',
        ]);

    $resp2->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.nuevo_saldo_usd', 0)
        ->assertJsonPath('data.nuevo_estado', 'pagada');

    $cxc->refresh();
    expect((float) $cxc->monto_pagado_usd)->toBe(100.0)
        ->and((float) $cxc->saldo_pendiente_usd)->toBe(0.0)
        ->and($cxc->estado)->toBe('pagada')
        ->and($cxc->abonos)->toHaveCount(2);
});

test('abono general cxc distribuye en cascada fifo desde la factura mas antigua', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000004-4',
        'nombre' => 'FIFO CXC Corp',
        'razon_social' => 'FIFO CXC Corp C.A.',
        'direccion' => 'Av. Las Américas',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $metodoPago = MetodoPago::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Zelle USD',
        'tipo' => 'transferencia',
        'icono' => 'fas fa-mobile-alt',
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-44444444',
        'nombre' => 'Alejandro',
        'apellido' => 'Sanz',
        'telefono' => '04144444444',
        'tipo_cliente' => 'mayorista',
        'estado' => true,
    ]);

    // Factura 1 (Más antigua): $50
    $fac1 = CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'numero_factura' => 'VEN-FIFO-01',
        'fecha_emision' => '2026-08-01',
        'fecha_vencimiento' => '2026-08-15',
        'monto_total_usd' => 50,
        'monto_total_bs' => 2500,
        'monto_pagado_usd' => 0,
        'monto_pagado_bs' => 0,
        'saldo_pendiente_usd' => 50,
        'saldo_pendiente_bs' => 2500,
        'estado' => 'pendiente',
    ]);

    // Factura 2 (Intermedia): $30
    $fac2 = CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'numero_factura' => 'VEN-FIFO-02',
        'fecha_emision' => '2026-08-15',
        'fecha_vencimiento' => '2026-08-30',
        'monto_total_usd' => 30,
        'monto_total_bs' => 1500,
        'monto_pagado_usd' => 0,
        'monto_pagado_bs' => 0,
        'saldo_pendiente_usd' => 30,
        'saldo_pendiente_bs' => 1500,
        'estado' => 'pendiente',
    ]);

    // Factura 3 (Más reciente): $20
    $fac3 = CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'numero_factura' => 'VEN-FIFO-03',
        'fecha_emision' => '2026-09-01',
        'fecha_vencimiento' => '2026-09-15',
        'monto_total_usd' => 20,
        'monto_total_bs' => 1000,
        'monto_pagado_usd' => 0,
        'monto_pagado_bs' => 0,
        'saldo_pendiente_usd' => 20,
        'saldo_pendiente_bs' => 1000,
        'estado' => 'pendiente',
    ]);

    // Total adeudado = $50 + $30 + $20 = $100.
    // Realizaremos un abono general de $65.
    // Debería cubrir:
    // - Fac 1: $50 completada (quedando saldo 0, pagada)
    // - Fac 2: $15 abonados de los $30 (quedando saldo 15, parcial)
    // - Fac 3: $0 abonados (quedando saldo 20, pendiente intacta)
    // Deuda restante del cliente = $35

    $user = User::factory()->create(['name' => 'V-88000004']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cuentas-por-cobrar/abonar-general', [
            'cliente_id' => $cliente->id,
            'metodo_pago_id' => $metodoPago->id,
            'moneda' => 'USD',
            'monto' => 65,
            'tasa_cambio' => 50.0,
            'fecha_abono' => '2026-09-21',
            'referencia' => 'ZELLE-FIFO-99',
            'observaciones' => 'Abono general del cliente',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.monto_total_abonado_usd', 65)
        ->assertJsonPath('data.deuda_anterior_usd', 100)
        ->assertJsonPath('data.deuda_restante_usd', 35);

    $fac1->refresh();
    $fac2->refresh();
    $fac3->refresh();

    expect((float) $fac1->saldo_pendiente_usd)->toBe(0.0)
        ->and((float) $fac1->monto_pagado_usd)->toBe(50.0)
        ->and($fac1->estado)->toBe('pagada')
        ->and((float) $fac2->saldo_pendiente_usd)->toBe(15.0)
        ->and((float) $fac2->monto_pagado_usd)->toBe(15.0)
        ->and($fac2->estado)->toBe('parcial')
        ->and((float) $fac3->saldo_pendiente_usd)->toBe(20.0)
        ->and((float) $fac3->monto_pagado_usd)->toBe(0.0)
        ->and($fac3->estado)->toBe('pendiente');

    // Verificar que se crearon los registros de abono atómicos
    $abonosFac1 = CuentaPorCobrarAbono::where('cuenta_por_cobrar_id', $fac1->id)->get();
    expect($abonosFac1)->toHaveCount(1)
        ->and((float) $abonosFac1[0]->monto_usd)->toBe(50.0);

    $abonosFac2 = CuentaPorCobrarAbono::where('cuenta_por_cobrar_id', $fac2->id)->get();
    expect($abonosFac2)->toHaveCount(1)
        ->and((float) $abonosFac2[0]->monto_usd)->toBe(15.0);
});

test('abono general cxp a proveedores distribuye en cascada fifo correctamente', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000005-5',
        'nombre' => 'FIFO CXP Corp',
        'razon_social' => 'FIFO CXP Corp C.A.',
        'direccion' => 'Zona Industrial',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $metodoPago = MetodoPago::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Transferencia Banesco',
        'tipo' => 'transferencia',
        'icono' => 'fas fa-university',
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-55555555-5',
        'nombre' => 'Distribuidora Mayorista Central',
        'razon_social' => 'Distribuidora Mayorista Central C.A.',
        'telefono' => '02415555555',
        'estado' => true,
    ]);

    // Factura Compra 1: $120
    $compra1 = CuentaPorPagar::create([
        'empresa_id' => $empresa->id,
        'proveedor_id' => $proveedor->id,
        'numero_factura' => 'FAC-COMPRA-01',
        'fecha_emision' => '2026-07-01',
        'fecha_vencimiento' => '2026-07-30',
        'monto_total_usd' => 120,
        'monto_total_bs' => 6000,
        'monto_pagado_usd' => 0,
        'monto_pagado_bs' => 0,
        'saldo_pendiente_usd' => 120,
        'saldo_pendiente_bs' => 6000,
        'estado' => 'pendiente',
    ]);

    // Factura Compra 2: $80
    $compra2 = CuentaPorPagar::create([
        'empresa_id' => $empresa->id,
        'proveedor_id' => $proveedor->id,
        'numero_factura' => 'FAC-COMPRA-02',
        'fecha_emision' => '2026-08-01',
        'fecha_vencimiento' => '2026-08-30',
        'monto_total_usd' => 80,
        'monto_total_bs' => 4000,
        'monto_pagado_usd' => 0,
        'monto_pagado_bs' => 0,
        'saldo_pendiente_usd' => 80,
        'saldo_pendiente_bs' => 4000,
        'estado' => 'pendiente',
    ]);

    $user = User::factory()->create(['name' => 'V-88000005']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // Abono general de $150
    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cuentas-por-pagar/abonar-general', [
            'proveedor_id' => $proveedor->id,
            'metodo_pago_id' => $metodoPago->id,
            'moneda' => 'USD',
            'monto' => 150,
            'tasa_cambio' => 50.0,
            'fecha_abono' => '2026-09-21',
            'referencia' => 'TRF-BAN-888',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.monto_total_abonado_usd', 150)
        ->assertJsonPath('data.deuda_anterior_usd', 200)
        ->assertJsonPath('data.deuda_restante_usd', 50);

    $compra1->refresh();
    $compra2->refresh();

    expect((float) $compra1->saldo_pendiente_usd)->toBe(0.0)
        ->and((float) $compra1->monto_pagado_usd)->toBe(120.0)
        ->and($compra1->estado)->toBe('pagada')
        ->and((float) $compra2->saldo_pendiente_usd)->toBe(50.0)
        ->and((float) $compra2->monto_pagado_usd)->toBe(30.0)
        ->and($compra2->estado)->toBe('parcial');

    $abonosProv = CuentaPorPagarAbono::all();
    expect($abonosProv)->toHaveCount(2);
});
