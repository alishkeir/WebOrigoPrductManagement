<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::resource('/products', ProductController::class);
Route::resource('/orders', OrderController::class);

Route::post('/order/{orderId}/orderItem', [OrderController::class, 'addOrderItem']);
Route::delete('/order/{orderId}/orderItem/{orderItemId}', [OrderController::class, 'deleteOrderItem']);