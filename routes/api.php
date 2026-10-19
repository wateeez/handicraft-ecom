<?php

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

use App\Http\Controllers\Api\PayPalController;

// Lets the checkout frontend know whether to show PayPal
Route::get('/payment-methods', fn () => response()->json(['paypal' => \App\Models\SiteSetting::paypalEnabled()]));

// Postman test routes for PayPal Backend Processing
Route::post('/paypal/orders', [PayPalController::class, 'createOrder']);
Route::post('/paypal/orders/{orderId}/capture', [PayPalController::class, 'capturePayment']);
Route::post('/orders/{order}/cancel-pending', [PayPalController::class, 'cancelPending']);
