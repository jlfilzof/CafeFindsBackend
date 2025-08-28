<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:sanctum'])->group(function () {
    // user endpoints
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/edit-profile', [AuthController::class, 'edit_profile']);
    Route::get('/profile', function (Request $request) {
        return $request->user();
    });
    // address
    Route::apiResource('address', AddressController::class);
});
