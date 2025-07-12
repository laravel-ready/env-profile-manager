<?php

use Illuminate\Support\Facades\Route;
use LaravelReady\EnvProfiles\Http\Controllers\EnvProfileController;

Route::group(['prefix' => config('env-profile-manager.api_prefix', 'api/env-profile-manager'), 'middleware' => config('env-profile-manager.api_middleware', ['api', 'auth:sanctum'])], function () {
    Route::get('/', [EnvProfileController::class, 'apiIndex']);
    Route::post('/', [EnvProfileController::class, 'store']);
    Route::get('/current-env', [EnvProfileController::class, 'getCurrentEnv']);
    Route::put('/current-env', [EnvProfileController::class, 'updateCurrentEnv']);
    Route::get('/{profile}', [EnvProfileController::class, 'show']);
    Route::put('/{profile}', [EnvProfileController::class, 'update']);
    Route::delete('/{profile}', [EnvProfileController::class, 'destroy']);
    Route::post('/{profile}/activate', [EnvProfileController::class, 'activate']);
});
