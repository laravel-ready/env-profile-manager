<?php

use Illuminate\Support\Facades\Route;
use LaravelReady\EnvProfiles\Http\Controllers\EnvProfileController;

Route::group(['prefix' => config('env-profiles.route_prefix', 'env-profiles'), 'middleware' => config('env-profiles.middleware', ['web', 'auth'])], function () {
    Route::get('/', [EnvProfileController::class, 'index'])->name('env-profiles.index');
    Route::post('/', [EnvProfileController::class, 'store'])->name('env-profiles.store');
    
    // Current env routes must come before {profile} routes
    Route::get('/current-env', [EnvProfileController::class, 'getCurrentEnv'])->name('env-profiles.current-env');
    Route::put('/current-env', [EnvProfileController::class, 'updateCurrentEnv'])->name('env-profiles.update-current-env');
    
    Route::get('/{profile}', [EnvProfileController::class, 'show'])->name('env-profiles.show');
    Route::put('/{profile}', [EnvProfileController::class, 'update'])->name('env-profiles.update');
    Route::delete('/{profile}', [EnvProfileController::class, 'destroy'])->name('env-profiles.destroy');
    Route::post('/{profile}/activate', [EnvProfileController::class, 'activate'])->name('env-profiles.activate');
});