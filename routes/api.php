<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CabangController;
use App\Http\Controllers\Api\TransaksiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider or bootstrap/app.php
| within a group which is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ==========================================
// PUBLIC ROUTES
// ==========================================
Route::post('/login', [AuthController::class, 'login']);

// ==========================================
// PROTECTED ROUTES (HARUS MEMBAWA BEARER TOKEN SANCTUM)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth Profile & Logout
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Menu
    Route::get('/menu', [MenuController::class, 'index']);
    Route::get('/menu/{id}', [MenuController::class, 'show']);

    // Cabang
    Route::get('/cabang', [CabangController::class, 'index']);
    Route::get('/cabang/{id}', [CabangController::class, 'show']);

    // Transaksi & Checkout
    Route::get('/transaksi', [TransaksiController::class, 'index']);
    Route::post('/checkout', [TransaksiController::class, 'checkout']);
});
