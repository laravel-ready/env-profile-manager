<?php

use Illuminate\Support\Facades\Route;
use LaravelReady\EnvProfiles\Http\Controllers\EnvProfileController;

Route::group(['prefix' => config('env-profile-manager.route_prefix', 'env-profile-manager'), 'middleware' => config('env-profile-manager.middleware', ['web', 'auth'])], function () {
    Route::get('/', [EnvProfileController::class, 'index'])->name('env-profile-manager.index');
    Route::post('/', [EnvProfileController::class, 'store'])->name('env-profile-manager.store');

    // Current env routes must come before {profile} routes
    Route::get('/current-env', [EnvProfileController::class, 'getCurrentEnv'])->name('env-profile-manager.current-env');
    Route::put('/current-env', [EnvProfileController::class, 'updateCurrentEnv'])->name('env-profile-manager.update-current-env');

    Route::get('/{profile}', [EnvProfileController::class, 'show'])->name('env-profile-manager.show');
    Route::put('/{profile}', [EnvProfileController::class, 'update'])->name('env-profile-manager.update');
    Route::delete('/{profile}', [EnvProfileController::class, 'destroy'])->name('env-profile-manager.destroy');
    Route::post('/{profile}/activate', [EnvProfileController::class, 'activate'])->name('env-profile-manager.activate');
});
