<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SubjectController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

     Route::apiResource('classes', ClassController::class);
     Route::apiResource('students', StudentController::class);
     Route::apiResource('subjects', SubjectController::class);
});