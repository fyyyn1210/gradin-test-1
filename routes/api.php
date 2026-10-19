<?php

use App\Http\Controllers\Api\CourierController;
use Illuminate\Support\Facades\Route;

Route::apiResource('couriers', CourierController::class);
