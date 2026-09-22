<?php

use App\Http\Controllers\Administradores\EmpresaController;
use App\Http\Controllers\Administradores\UsuarioController;
use App\Http\Controllers\Empresa\AlmacenController;
use App\Http\Controllers\Empresa\CategoriaController;
use App\Http\Controllers\Empresa\ClienteController;
use App\Http\Controllers\Empresa\ConfiguracionController;
use App\Http\Controllers\Empresa\CuentaPorCobrarController;
use App\Http\Controllers\Empresa\CuentaPorPagarController;
use App\Http\Controllers\Empresa\FacturaController;
use App\Http\Controllers\Empresa\MetodoPagoController;
use App\Http\Controllers\Empresa\MotoController;
use App\Http\Controllers\Empresa\PosController;
use App\Http\Controllers\Empresa\ProductoController;
use App\Http\Controllers\Empresa\ProveedorController;
use App\Http\Controllers\Empresa\RecepcionController;
use App\Http\Controllers\Empresa\RecepcionMotoController;
use App\Http\Controllers\Empresa\ServicioController;
use App\Http\Controllers\PanelPrincipalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::controller(PanelPrincipalController::class)->group(function () {
        Route::get('/panel-principal', 'index')->name('dashboard');
    });

    Route::controller(PosController::class)->group(function () {
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

    Route::controller(FacturaController::class)->group(function () {
        Route::get('/facturas', 'index')->name('factura');
        Route::get('/facturas/kpis', 'kpis');
        Route::get('/facturas/lista', 'lista');
        Route::get('/facturas/{id}', 'detalle');
    });

    Route::controller(ConfiguracionController::class)->group(function () {
        Route::get('/configuracion', 'index')->name('configuracion');
        Route::get('/configuracion/datos', 'datos');
        Route::post('/configuracion/empresa', 'actualizarEmpresa');
        Route::post('/configuracion/monedas', 'actualizarMonedas');
    });

    Route::controller(ProductoController::class)->group(function () {
        Route::get('/productos', 'index')->name('producto');
        Route::get('/productos/lista', 'lista');
        Route::get('/productos/catalogos', 'catalogos');
        Route::get('/productos/{id}', 'detalle');
        Route::post('/productos', 'guardar');
        Route::put('/productos/actualizar/{id}', 'actualizar');
        Route::delete('/productos/{id}', 'eliminar');
    });

    Route::controller(MotoController::class)->group(function () {
        Route::get('/motos', 'index')->name('moto');
        Route::get('/motos/lista', 'lista');
        Route::get('/motos/{id}', 'detalle');
        Route::put('/motos/actualizar/{id}', 'actualizar');
        Route::post('/motos/{id}/cambiar-estado', 'cambiarEstado');
    });

    Route::controller(RecepcionController::class)->group(function () {
        Route::get('/recepciones', 'index')->name('recepcion');
        Route::get('/recepciones/lista', 'lista');
        Route::get('/recepciones/catalogos', 'catalogos');
        Route::get('/recepciones/{id}', 'detalle');
        Route::get('/recepciones/{id}/imprimir', 'imprimir')->name('recepcion.imprimir');
        Route::post('/recepciones', 'guardar');
        Route::post('/recepciones/{id}/anular', 'anular');
    });

    Route::controller(RecepcionMotoController::class)->group(function () {
        Route::get('/recepciones-motos', 'index')->name('recepcion_moto');
        Route::get('/recepciones-motos/lista', 'lista');
        Route::get('/recepciones-motos/catalogos', 'catalogos');
        Route::get('/recepciones-motos/{id}', 'detalle');
        Route::get('/recepciones-motos/{id}/imprimir', 'imprimir')->name('recepcion_moto.imprimir');
        Route::post('/recepciones-motos', 'guardar');
        Route::post('/recepciones-motos/{id}/anular', 'anular');
    });

    Route::controller(ServicioController::class)->group(function () {
        Route::get('/servicios', 'index')->name('servicio');
        Route::get('/servicios/lista', 'lista');
        Route::get('/servicios/catalogos', 'catalogos');
        Route::get('/servicios/{id}', 'detalle');
        Route::post('/servicios', 'guardar');
        Route::put('/servicios/actualizar/{id}', 'actualizar');
        Route::delete('/servicios/{id}', 'eliminar');
    });

    Route::controller(CategoriaController::class)->group(function () {
        Route::get('/categorias', 'index')->name('categoria');
        Route::get('/categorias/lista', 'lista');
        Route::get('/categorias/{id}', 'detalle');
        Route::post('/categorias', 'guardar');
        Route::put('/categorias/actualizar/{id}', 'actualizar');
        Route::delete('/categorias/{id}', 'eliminar');
    });

    Route::controller(AlmacenController::class)->group(function () {
        Route::get('/almacenes', 'index')->name('almacen');
        Route::get('/almacenes/lista', 'lista');
        Route::get('/almacenes/{id}', 'detalle');
        Route::post('/almacenes', 'guardar');
        Route::put('/almacenes/actualizar/{id}', 'actualizar');
        Route::delete('/almacenes/{id}', 'eliminar');
    });

    Route::controller(UsuarioController::class)->group(function () {
        Route::get('/usuarios', 'index')->name('usuario');
        Route::get('/usuarios/lista', 'lista');
        Route::get('/usuarios/catalogos', 'catalogos');
        Route::get('/usuarios/{id}', 'detalle');
        Route::post('/usuarios', 'guardar');
        Route::put('/usuarios/actualizar/{id}', 'actualizar');
        Route::post('/usuarios/{id}/permisos', 'actualizarPermisos');
        Route::delete('/usuarios/{id}', 'eliminar');
        Route::post('/cambiar-empresa', 'cambiarEmpresa')->name('cambiar_empresa');
    });

    Route::controller(EmpresaController::class)->group(function () {
        Route::get('/empresas', 'index')->name('empresa');
        Route::get('/empresas/lista', 'lista');
        Route::get('/empresas/{id}', 'detalle');
        Route::post('/empresas', 'guardar');
        Route::put('/empresas/actualizar/{id}', 'actualizar');
        Route::delete('/empresas/{id}', 'eliminar');
    });

    Route::controller(ClienteController::class)->group(function () {
        Route::get('/clientes', 'index')->name('cliente');
        Route::get('/clientes/lista', 'lista');
        Route::get('/clientes/{id}', 'detalle');
        Route::post('/clientes', 'guardar');
        Route::put('/clientes/actualizar/{id}', 'actualizar');
        Route::delete('/clientes/{id}', 'eliminar');
    });

    Route::controller(ProveedorController::class)->group(function () {
        Route::get('/proveedores', 'index')->name('proveedor');
        Route::get('/proveedores/lista', 'lista');
        Route::get('/proveedores/{id}', 'detalle');
        Route::post('/proveedores', 'guardar');
        Route::put('/proveedores/actualizar/{id}', 'actualizar');
        Route::delete('/proveedores/{id}', 'eliminar');
    });

    Route::controller(CuentaPorCobrarController::class)->group(function () {
        Route::get('/cuentas-por-cobrar', 'index')->name('cxc');
        Route::get('/cuentas-por-cobrar/lista', 'lista');
        Route::get('/cuentas-por-cobrar/catalogos', 'catalogos');
        Route::get('/cuentas-por-cobrar/cliente/{id}', 'clienteDetalle');
        Route::post('/cuentas-por-cobrar/abonar-factura', 'abonarFactura');
        Route::post('/cuentas-por-cobrar/abonar-general', 'abonarGeneral');
        Route::get('/cuentas-por-cobrar/ticket/{id}', 'imprimirTicket')->name('cxc.ticket');
    });

    Route::controller(CuentaPorPagarController::class)->group(function () {
        Route::get('/cuentas-por-pagar', 'index')->name('cxp');
        Route::get('/cuentas-por-pagar/lista', 'lista');
        Route::get('/cuentas-por-pagar/catalogos', 'catalogos');
        Route::get('/cuentas-por-pagar/proveedor/{id}', 'proveedorDetalle');
        Route::post('/cuentas-por-pagar/abonar-factura', 'abonarFactura');
        Route::post('/cuentas-por-pagar/abonar-general', 'abonarGeneral');
        Route::get('/cuentas-por-pagar/ticket/{id}', 'imprimirTicket')->name('cxp.ticket');
    });

    Route::controller(MetodoPagoController::class)->group(function () {

        Route::get('/metodos-pago', 'index')->name('metodo_pago');
        Route::get('/metodos-pago/lista', 'lista');
        Route::get('/metodos-pago/{id}', 'detalle');
        Route::post('/metodos-pago', 'guardar');
        Route::put('/metodos-pago/actualizar/{id}', 'actualizar');
        Route::delete('/metodos-pago/{id}', 'eliminar');
    });

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});
