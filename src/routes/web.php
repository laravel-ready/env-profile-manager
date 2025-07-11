<?php

use Illuminate\Support\Facades\Route;
use LaravelReady\EnvProfiles\Http\Controllers\EnvProfileController;

Route::group(['prefix' => config('env-profiles.route_prefix', 'env-profiles'), 'middleware' => config('env-profiles.middleware', ['web', 'auth'])], function () {
    Route::get('/', [EnvProfileController::class, 'index'])->name('env-profiles.index');
});