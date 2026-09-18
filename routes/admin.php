<?php

use App\Http\Controllers\Empresa\ClienteController;
use App\Http\Controllers\PanelPrincipalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::controller(PanelPrincipalController::class)->group(function () {
        Route::get('/panel-principal', 'index')->name('dashboard');
    });

    Route::controller(ClienteController::class)->group(function () {
        Route::get('/clientes', 'index')->name('cliente');
        Route::get('/clientes/lista', 'lista');
        Route::get('/clientes/{id}', 'detalle');
        Route::post('/clientes', 'guardar');
        Route::put('/clientes/actualizar/{id}', 'actualizar');
        Route::delete('/clientes/{id}', 'eliminar');
    });

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});
