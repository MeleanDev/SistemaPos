<?php

use App\Http\Controllers\Setup\ConfiguracionInicialController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::controller(ConfiguracionInicialController::class)->group(function () {
    Route::get('/configuracion-inicial', 'index')->name('setup.index');
    Route::post('/configuracion-inicial', 'procesar')->name('setup.procesar');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
