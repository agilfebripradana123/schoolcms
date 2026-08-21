<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\ClassSubjectController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\SubjectController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // =========================
    // AUTH
    // =========================
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);


    // =========================
    // CLASSES
    // =========================
    Route::get('/classes', [ClassController::class, 'index']);
    Route::get('/classes/{class}', [ClassController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/classes', [ClassController::class, 'store']);
        Route::put('/classes/{class}', [ClassController::class, 'update']);
        Route::patch('/classes/{class}', [ClassController::class, 'update']);
        Route::delete('/classes/{class}', [ClassController::class, 'destroy']);
    });


    // =========================
    // TEACHERS
    // =========================
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


    // =========================
    // STUDENTS
    // =========================
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{student}', [StudentController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/students', [StudentController::class, 'store']);
        Route::put('/students/{student}', [StudentController::class, 'update']);
        Route::patch('/students/{student}', [StudentController::class, 'update']);
        Route::delete('/students/{student}', [StudentController::class, 'destroy']);
    });


    // =========================
    // SUBJECTS
    // =========================
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::get('/subjects/{subject}', [SubjectController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/subjects', [SubjectController::class, 'store']);
        Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
        Route::patch('/subjects/{subject}', [SubjectController::class, 'update']);
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);
    });


    // =========================
    // CLASS SUBJECTS
    // =========================
    Route::get('/class-subjects', [ClassSubjectController::class, 'index']);
    Route::get('/class-subjects/{class_subject}', [ClassSubjectController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/class-subjects', [ClassSubjectController::class, 'store']);
        Route::put('/class-subjects/{class_subject}', [ClassSubjectController::class, 'update']);
        Route::patch('/class-subjects/{class_subject}', [ClassSubjectController::class, 'update']);
        Route::delete('/class-subjects/{class_subject}', [ClassSubjectController::class, 'destroy']);
    });

        // =========================
    // ATTENDANCE
    // =========================
    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::get('/attendances/{attendance}', [AttendanceController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/attendances', [AttendanceController::class, 'store']);
        Route::put('/attendances/{attendance}', [AttendanceController::class, 'update']);
        Route::patch('/attendances/{attendance}', [AttendanceController::class, 'update']);
        Route::delete('/attendances/{attendance}', [AttendanceController::class, 'destroy']);
    });


    // =========================
    // GRADES
    // =========================
    Route::get('/grades', [GradeController::class, 'index']);
    Route::get('/grades/{grade}', [GradeController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/grades', [GradeController::class, 'store']);
        Route::put('/grades/{grade}', [GradeController::class, 'update']);
        Route::patch('/grades/{grade}', [GradeController::class, 'update']);
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy']);
    });

        // =========================
    // ANNOUNCEMENTS
    // =========================
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
        Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update']);
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);
    });

    // Rooms API
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::patch('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
    });

    // Questions API
    Route::get('/questions', [QuestionController::class, 'index']);
    Route::get('/questions/{question}', [QuestionController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/questions', [QuestionController::class, 'store']);
        Route::put('/questions/{question}', [QuestionController::class, 'update']);
        Route::patch('/questions/{question}', [QuestionController::class, 'update']);
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);
    });
});
