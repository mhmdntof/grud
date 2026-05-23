<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [AuthController::class, 'loginWeb']);
Route::middleware('auth:sanctum')
    ->get('/me', [AuthController::class, 'me']);
