<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    Route::middleware('jwt')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // 1. KATEGORIJOS (5 metodai)
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin');
        Route::put('/categories/{id}', [CategoryController::class, 'update'])->middleware('role:admin');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->middleware('role:admin');

        // 2. PRODUKTAI (Puslapiavimas + Filtravimas)
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::post('/products', [ProductController::class, 'store'])->middleware('role:admin,farmer');
        Route::put('/products/{id}', [ProductController::class, 'update'])->middleware('role:admin,farmer');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->middleware('role:admin,farmer');

        // 3. ATSILIEPIMAI IR HIERARCHIJA
        Route::get('/products/{productId}/reviews', [ReviewController::class, 'indexByProduct']);
        Route::get('/reviews', [ReviewController::class, 'index']);
        Route::get('/reviews/{id}', [ReviewController::class, 'show']);
        // --- BŪTINAS REIKALAVIMAS: 3 lygių hierarchinis metodas (Category -> Product -> Review) ---
        Route::get('/categories/{categoryId}/products/{productId}/reviews', [ReviewController::class, 'indexFullHierarchy']);
        Route::get('/categories/{id}/catalog', [CategoryController::class, 'catalog']);
        Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{id}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

        // 4. SUJUNGTAS RESURSAS (Panelė / Dashboard / Profilis iš kelių esybių)
        Route::get('/farmers/{farmerId}/dashboard', [ProductController::class, 'farmerDashboard']);

        // 5. NAUDOTOJAI
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    })->middleware('jwt');
});
