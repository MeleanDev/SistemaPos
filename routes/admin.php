<?php

use App\Http\Controllers\Administradores\EmpresaController;
use App\Http\Controllers\Administradores\UsuarioController;
use App\Http\Controllers\Empresa\AlmacenController;
use App\Http\Controllers\Empresa\CajaController;
use App\Http\Controllers\Empresa\CategoriaController;
use App\Http\Controllers\Empresa\ClienteController;
use App\Http\Controllers\Empresa\ConfiguracionController;
use App\Http\Controllers\Empresa\CuentaPorCobrarController;
use App\Http\Controllers\Empresa\CuentaPorPagarController;
use App\Http\Controllers\Empresa\FacturaController;
use App\Http\Controllers\Empresa\KardexController;
use App\Http\Controllers\Empresa\MetodoPagoController;
use App\Http\Controllers\Empresa\ModeloMotoController;
use App\Http\Controllers\Empresa\MotoController;
use App\Http\Controllers\Empresa\PosController;
use App\Http\Controllers\Empresa\ProductoController;
use App\Http\Controllers\Empresa\ProveedorController;
use App\Http\Controllers\Empresa\RecepcionController;
use App\Http\Controllers\Empresa\RecepcionMotoController;
use App\Http\Controllers\Empresa\ReporteVendedorController;
use App\Http\Controllers\Empresa\ServicioController;
use App\Http\Controllers\Empresa\VendedorController;
use App\Http\Controllers\PanelPrincipalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::controller(PanelPrincipalController::class)->group(function () {
        Route::get('/panel-principal', 'index')->name('dashboard');
    });

    Route::post('/cambiar-empresa', [UsuarioController::class, 'cambiarEmpresa'])->name('cambiar_empresa');

    Route::middleware('permission:pos.acceso')->controller(PosController::class)->group(function () {
        Route::get('/pos', 'index')->name('pos');
        Route::get('/pos/datos', 'datos');
        Route::get('/pos/buscar-clientes', 'buscarClientes');
        Route::post('/pos/guardar-cliente-rapido', 'guardarClienteRapido');
        Route::post('/pos/guardar', 'guardar');
        Route::post('/pos/en-espera/guardar', 'guardarEnEspera');
        Route::get('/pos/en-espera/lista', 'listarEnEspera');
        Route::get('/pos/en-espera/{id}/recuperar', 'recuperarEnEspera');
        Route::delete('/pos/en-espera/{id}', 'eliminarEnEspera');
        Route::get('/pos/devolucion/buscar', 'buscarFacturaDevolucion');
        Route::post('/pos/devolucion/procesar', 'procesarDevolucion');
        Route::get('/pos/imprimir/{id}', 'imprimir')->name('pos.imprimir');
        Route::get('/pos/imprimir-carta/{id}', 'imprimirCarta')->name('pos.imprimir_carta');
        Route::get('/pos/imprimir-ticket/{id}', 'imprimirTicket')->name('pos.imprimir_ticket');
    });

    Route::middleware('permission:ventas.ver')->controller(FacturaController::class)->group(function () {
        Route::get('/facturas', 'index')->name('factura');
        Route::get('/facturas/kpis', 'kpis');
        Route::get('/facturas/lista', 'lista');
        Route::get('/facturas/{id}', 'detalle');
    });

    Route::middleware('permission:configuracion.ver')->controller(ConfiguracionController::class)->group(function () {
        Route::get('/configuracion', 'index')->name('configuracion');
        Route::get('/configuracion/datos', 'datos');
        Route::post('/configuracion/empresa', 'actualizarEmpresa');
        Route::post('/configuracion/monedas', 'actualizarMonedas');
    });

    Route::middleware('permission:productos.ver')->controller(ProductoController::class)->group(function () {
        Route::get('/productos', 'index')->name('producto');
        Route::get('/productos/lista', 'lista');
        Route::get('/productos/catalogos', 'catalogos');
        Route::get('/productos/{id}', 'detalle');
        Route::post('/productos', 'guardar');
        Route::put('/productos/actualizar/{id}', 'actualizar');
        Route::delete('/productos/{id}', 'eliminar');
    });

    Route::middleware('permission:motos.ver')->controller(MotoController::class)->group(function () {
        Route::get('/motos', 'index')->name('moto');
        Route::get('/motos/lista', 'lista');
        Route::get('/motos/catalogos', 'catalogos');
        Route::get('/motos/{id}', 'detalle');
        Route::put('/motos/actualizar/{id}', 'actualizar');
        Route::post('/motos/{id}/cambiar-estado', 'cambiarEstado');
    });

    Route::middleware('permission:motos.ver')->controller(ModeloMotoController::class)->group(function () {
        Route::get('/modelos-motos/lista', 'lista');
        Route::get('/modelos-motos/catalogos', 'catalogos');
        Route::get('/modelos-motos/proxima-referencia', 'proximaReferencia');
        Route::get('/modelos-motos/{id}', 'detalle');
        Route::post('/modelos-motos', 'guardar');
        Route::put('/modelos-motos/actualizar/{id}', 'actualizar');
        Route::delete('/modelos-motos/{id}', 'eliminar');
    });

    Route::middleware('permission:compras.recepcion')->controller(RecepcionController::class)->group(function () {
        Route::get('/recepciones', 'index')->name('recepcion');
        Route::get('/recepciones/lista', 'lista');
        Route::get('/recepciones/catalogos', 'catalogos');
        Route::get('/recepciones/borradores', 'listarBorradores');
        Route::get('/recepciones/borradores/{id}', 'recuperarBorrador');
        Route::post('/recepciones/borradores', 'guardarBorrador');
        Route::delete('/recepciones/borradores/{id}', 'eliminarBorrador');
        Route::get('/recepciones/{id}', 'detalle');
        Route::get('/recepciones/{id}/imprimir', 'imprimir')->name('recepcion.imprimir');
        Route::post('/recepciones', 'guardar');
        Route::post('/recepciones/{id}/anular', 'anular');
    });

    Route::middleware('permission:motos.recepcion')->controller(RecepcionMotoController::class)->group(function () {
        Route::get('/recepciones-motos', 'index')->name('recepcion_moto');
        Route::get('/recepciones-motos/lista', 'lista');
        Route::get('/recepciones-motos/catalogos', 'catalogos');
        Route::get('/recepciones-motos/borradores', 'listarBorradores');
        Route::get('/recepciones-motos/borradores/{id}', 'recuperarBorrador');
        Route::post('/recepciones-motos/borradores', 'guardarBorrador');
        Route::delete('/recepciones-motos/borradores/{id}', 'eliminarBorrador');
        Route::get('/recepciones-motos/{id}', 'detalle');
        Route::get('/recepciones-motos/{id}/imprimir', 'imprimir')->name('recepcion_moto.imprimir');
        Route::post('/recepciones-motos', 'guardar');
        Route::post('/recepciones-motos/{id}/anular', 'anular');
    });

    Route::middleware('permission:servicios.ver')->controller(ServicioController::class)->group(function () {
        Route::get('/servicios', 'index')->name('servicio');
        Route::get('/servicios/lista', 'lista');
        Route::get('/servicios/catalogos', 'catalogos');
        Route::get('/servicios/{id}', 'detalle');
        Route::post('/servicios', 'guardar');
        Route::put('/servicios/actualizar/{id}', 'actualizar');
        Route::delete('/servicios/{id}', 'eliminar');
    });

    Route::middleware('permission:categorias.ver')->controller(CategoriaController::class)->group(function () {
        Route::get('/categorias', 'index')->name('categoria');
        Route::get('/categorias/lista', 'lista');
        Route::get('/categorias/{id}', 'detalle');
        Route::post('/categorias', 'guardar');
        Route::put('/categorias/actualizar/{id}', 'actualizar');
        Route::delete('/categorias/{id}', 'eliminar');
    });

    Route::middleware('permission:almacenes.ver')->controller(AlmacenController::class)->group(function () {
        Route::get('/almacenes', 'index')->name('almacen');
        Route::get('/almacenes/lista', 'lista');
        Route::get('/almacenes/{id}', 'detalle');
        Route::post('/almacenes', 'guardar');
        Route::put('/almacenes/actualizar/{id}', 'actualizar');
        Route::delete('/almacenes/{id}', 'eliminar');
    });

    Route::middleware('permission:inventario.kardex')->controller(KardexController::class)->group(function () {
        Route::get('/kardex', 'index')->name('kardex');
        Route::get('/kardex/lista', 'lista');
        Route::get('/kardex/kpis', 'kpis');
        Route::get('/kardex/catalogos', 'catalogos');
        Route::get('/kardex/stock-producto/{productoId}', 'stockProducto');
        Route::post('/kardex/ajuste', 'ajustar');
        Route::post('/kardex/traslado', 'trasladar');
        Route::get('/kardex/comprobante/{id}', 'comprobante')->name('kardex.comprobante');
    });

    Route::middleware('permission:usuarios.ver')->controller(UsuarioController::class)->group(function () {
        Route::get('/usuarios', 'index')->name('usuario');
        Route::get('/usuarios/lista', 'lista');
        Route::get('/usuarios/catalogos', 'catalogos');
        Route::get('/usuarios/{id}', 'detalle');
        Route::post('/usuarios', 'guardar');
        Route::put('/usuarios/actualizar/{id}', 'actualizar');
        Route::post('/usuarios/{id}/permisos', 'actualizarPermisos');
        Route::delete('/usuarios/{id}', 'eliminar');
    });

    Route::middleware('role:SuperAdmin')->controller(EmpresaController::class)->group(function () {
        Route::get('/empresas', 'index')->name('empresa');
        Route::get('/empresas/lista', 'lista');
        Route::get('/empresas/{id}', 'detalle');
        Route::post('/empresas', 'guardar');
        Route::put('/empresas/actualizar/{id}', 'actualizar');
        Route::delete('/empresas/{id}', 'eliminar');
    });

    Route::middleware('permission:clientes.ver')->controller(ClienteController::class)->group(function () {
        Route::get('/clientes', 'index')->name('cliente');
        Route::get('/clientes/lista', 'lista');
        Route::get('/clientes/{id}', 'detalle');
        Route::post('/clientes', 'guardar');
        Route::put('/clientes/actualizar/{id}', 'actualizar');
        Route::delete('/clientes/{id}', 'eliminar');
    });

    Route::middleware('permission:proveedores.ver')->controller(ProveedorController::class)->group(function () {
        Route::get('/proveedores', 'index')->name('proveedor');
        Route::get('/proveedores/lista', 'lista');
        Route::get('/proveedores/{id}', 'detalle');
        Route::post('/proveedores', 'guardar');
        Route::put('/proveedores/actualizar/{id}', 'actualizar');
        Route::delete('/proveedores/{id}', 'eliminar');
    });

    Route::middleware('permission:cxc.ver')->controller(CuentaPorCobrarController::class)->group(function () {
        Route::get('/cuentas-por-cobrar', 'index')->name('cxc');
        Route::get('/cuentas-por-cobrar/lista', 'lista');
        Route::get('/cuentas-por-cobrar/catalogos', 'catalogos');
        Route::get('/cuentas-por-cobrar/cliente/{id}', 'clienteDetalle');
        Route::post('/cuentas-por-cobrar/abonar-factura', 'abonarFactura');
        Route::post('/cuentas-por-cobrar/abonar-general', 'abonarGeneral');
        Route::get('/cuentas-por-cobrar/ticket/{id}', 'imprimirTicket')->name('cxc.ticket');
    });

    Route::middleware('permission:cxp.ver')->controller(CuentaPorPagarController::class)->group(function () {
        Route::get('/cuentas-por-pagar', 'index')->name('cxp');
        Route::get('/cuentas-por-pagar/lista', 'lista');
        Route::get('/cuentas-por-pagar/catalogos', 'catalogos');
        Route::get('/cuentas-por-pagar/proveedor/{id}', 'proveedorDetalle');
        Route::post('/cuentas-por-pagar/abonar-factura', 'abonarFactura');
        Route::post('/cuentas-por-pagar/abonar-general', 'abonarGeneral');
        Route::get('/cuentas-por-pagar/ticket/{id}', 'imprimirTicket')->name('cxp.ticket');
    });

    Route::middleware('permission:metodos_pago.ver')->controller(MetodoPagoController::class)->group(function () {
        Route::get('/metodos-pago', 'index')->name('metodo_pago');
        Route::get('/metodos-pago/lista', 'lista');
        Route::get('/metodos-pago/{id}', 'detalle');
        Route::post('/metodos-pago', 'guardar');
        Route::put('/metodos-pago/actualizar/{id}', 'actualizar');
        Route::delete('/metodos-pago/{id}', 'eliminar');
    });

    Route::middleware('permission:vendedores.ver')->controller(VendedorController::class)->group(function () {
        Route::get('/vendedores', 'index')->name('vendedor');
        Route::get('/vendedores/lista', 'lista');
        Route::get('/vendedores/activos', 'activos');
        Route::get('/vendedores/{id}', 'detalle');
        Route::post('/vendedores', 'guardar');
        Route::put('/vendedores/actualizar/{id}', 'actualizar');
        Route::delete('/vendedores/{id}', 'eliminar');
    });

    Route::middleware('permission:cajas.ver')->controller(CajaController::class)->group(function () {
        Route::get('/cajas', 'index')->name('caja');
        Route::get('/cajas/lista', 'lista');
        Route::get('/cajas/turnos/lista', 'listaTurnos');
        Route::get('/cajas/disponibles', 'cajasDisponibles');
        Route::get('/cajas/cajeros-disponibles', 'cajerosDisponibles');
        Route::get('/cajas/turno-activo', 'turnoActivo');
        Route::get('/cajas/turnos/{id}/reporte-x', 'reporteX');
        Route::get('/cajas/turnos/{id}/imprimir-x', 'imprimirReporteX')->name('cajas.imprimir_x');
        Route::get('/cajas/turnos/{id}/imprimir-z', 'imprimirReporteZ')->name('cajas.imprimir_z');
        Route::get('/cajas/{id}', 'detalle');
        Route::post('/cajas', 'guardar');
        Route::put('/cajas/actualizar/{id}', 'actualizar');
        Route::delete('/cajas/{id}', 'eliminar');
        Route::post('/cajas/turnos/aperturar', 'aperturarTurno');
        Route::post('/cajas/turnos/{id}/cerrar', 'cerrarTurno');
    });

    Route::middleware('permission:reportes.vendedores')->controller(ReporteVendedorController::class)->group(function () {
        Route::get('/reportes/vendedores', 'index')->name('reportes.vendedores');
        Route::get('/reportes/vendedores/kpis', 'kpis');
        Route::get('/reportes/vendedores/lista', 'lista');
        Route::get('/reportes/vendedores/resumen', 'resumenVendedores');
    });

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});
