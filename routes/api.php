<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('users/{user}/cart', [UserController::class, 'getUserCart']);
Route::post('users/{user}/add_product_to_cart', [UserController::class, 'addProductToCart']);
Route::post('users/{user}/remove_product_from_cart', [UserController::class, 'removeProductFromCart']);
Route::post('users/{user}/set_cart_product_quantity', [UserController::class, 'setCartProductQuantity']);
