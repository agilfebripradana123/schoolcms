<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\ClassSubjectController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SubjectController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Classes API
    Route::apiResource('classes', ClassController::class);

    // Class Subjects API
    Route::get('/class-subjects', [ClassSubjectController::class, 'index']);
    Route::get('/class-subjects/{class_subject}', [ClassSubjectController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/class-subjects', [ClassSubjectController::class, 'store']);
        Route::put('/class-subjects/{class_subject}', [ClassSubjectController::class, 'update']);
        Route::patch('/class-subjects/{class_subject}', [ClassSubjectController::class, 'update']);
        Route::delete('/class-subjects/{class_subject}', [ClassSubjectController::class, 'destroy']);
    });

    // Teachers API
    Route::get('/teachers', [TeacherController::class, 'index']);
    Route::get('/teachers/export', [TeacherController::class, 'export']);
    Route::get('/teachers/{teacher}', [TeacherController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/teachers/import', [TeacherController::class, 'import']);
        Route::post('/teachers', [TeacherController::class, 'store']);
        Route::put('/teachers/{teacher}', [TeacherController::class, 'update']);
        Route::patch('/teachers/{teacher}', [TeacherController::class, 'update']);
        Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy']);
    });

    // Grades API
    Route::get('/grades', [GradeController::class, 'index']);
    Route::get('/grades/{grade}', [GradeController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/grades', [GradeController::class, 'store']);
        Route::put('/grades/{grade}', [GradeController::class, 'update']);
        Route::patch('/grades/{grade}', [GradeController::class, 'update']);
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy']);
    });
});
