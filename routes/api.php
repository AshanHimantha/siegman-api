<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



// Auth APIs
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth');

// Authenticated group (any logged-in user)
Route::middleware('auth')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Staff group (any staff)
Route::middleware('staff')->group(function () {
    Route::get('/staff/roles', function (Request $request) {
        return [
            'user_id' => $request->user()->id,
            'roles' => $request->attributes->get('staff_roles'),
        ];
    });
});

// Editor group
Route::middleware('editor')->group(function () {
    Route::get('/editor/demo', function (Request $request) {
        return ['message' => 'Hello Editor', 'user_id' => $request->user()->id];
    });
});

// Admin group
Route::middleware('admin')->group(function () {
    Route::get('/admin/demo', function (Request $request) {
        return ['message' => 'Hello Admin', 'user_id' => $request->user()->id];
    });
});
