<?php

use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('plans', PlanController::class);

Route::get('subscriptions', [SubscriptionController::class, 'index']);
Route::post('subscriptions', [SubscriptionController::class, 'store']);
Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show']);
Route::get('subscriptions/{subscription}/access', [SubscriptionController::class, 'access']);
Route::post('subscriptions/{subscription}/payment-success', [SubscriptionController::class, 'paymentSuccess']);
Route::post('subscriptions/{subscription}/payment-failed', [SubscriptionController::class, 'paymentFailed']);
Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
