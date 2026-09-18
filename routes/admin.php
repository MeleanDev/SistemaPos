<?php

use App\Http\Controllers\PanelPrincipalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::controller(PanelPrincipalController::class)->group(function () {
        Route::get('/panel-principal', 'index')->name('dashboard');
    });

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});
