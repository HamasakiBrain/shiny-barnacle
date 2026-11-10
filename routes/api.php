<?php

use App\Http\Controllers\AdminCatalogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('throttle:api')
    ->group(function () {
        Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
        Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
        Route::get('categories', [CatalogController::class, 'categories']);
        Route::get('products', [CatalogController::class, 'products']);
        Route::get('products/{product}', [CatalogController::class, 'product'])->whereNumber('product');
        Route::get('products/{product}/reviews', [EngagementController::class, 'reviews'])
            ->whereNumber('product');
        Route::middleware('auth:sanctum')->group(function () {
            Route::put('products/{product}/review', [EngagementController::class, 'review'])
                ->whereNumber('product');
            Route::delete('reviews/{review}', [EngagementController::class, 'deleteReview'])
                ->whereNumber('review');
            Route::get('favorites', [EngagementController::class, 'favorites']);
            Route::put('favorites/{product}', [EngagementController::class, 'favorite'])->whereNumber('product');
            Route::delete('favorites/{product}', [EngagementController::class, 'unfavorite'])
                ->whereNumber('product');
            Route::get('notifications', [EngagementController::class, 'notifications']);
            Route::get('cart', [CartController::class, 'index']);
            Route::put('cart/items', [CartController::class, 'put']);
            Route::delete('cart/items/{product}', [CartController::class, 'remove'])->whereNumber('product');
            Route::delete('cart', [CartController::class, 'clear']);
            Route::get('orders', [OrderController::class, 'index']);
            Route::post('orders', [OrderController::class, 'store']);
            Route::get('orders/{order}', [OrderController::class, 'show'])->whereNumber('order');
            Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->whereNumber('order');
            Route::post('orders/{order}/payments/mock', [OrderController::class, 'pay'])->whereNumber('order');
            Route::get('auth/me', [AuthController::class, 'me']);
            Route::post('auth/logout', [AuthController::class, 'logout']);
            Route::prefix('admin')
                ->middleware('can:admin')
                ->group(function () {
                    Route::get('orders', [OrderController::class, 'adminIndex']);
                    Route::patch('orders/{order}/status', [OrderController::class, 'status'])->whereNumber('order');
                    Route::post('products', [AdminCatalogController::class, 'storeProduct']);
                    Route::patch('products/{product}', [AdminCatalogController::class, 'updateProduct'])
                        ->whereNumber('product');
                    Route::delete('products/{product}', [AdminCatalogController::class, 'destroyProduct'])
                        ->whereNumber('product');
                    Route::post('categories', [AdminCatalogController::class, 'storeCategory']);
                    Route::patch('categories/{category}', [AdminCatalogController::class, 'updateCategory'])
                        ->whereNumber('category');
                    Route::delete('categories/{category}', [AdminCatalogController::class, 'destroyCategory'])
                        ->whereNumber('category');
                });
        });
    });
