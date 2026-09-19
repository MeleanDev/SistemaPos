<?php

use App\Http\Controllers\Administradores\EmpresaController;
use App\Http\Controllers\Administradores\UsuarioController;
use App\Http\Controllers\Empresa\ClienteController;
use App\Http\Controllers\Empresa\MetodoPagoController;
use App\Http\Controllers\Empresa\ProveedorController;
use App\Http\Controllers\PanelPrincipalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::controller(PanelPrincipalController::class)->group(function () {
        Route::get('/panel-principal', 'index')->name('dashboard');
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
