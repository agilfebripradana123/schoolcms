<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\ClassSubjectController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ExamSessionController;
use App\Http\Controllers\Api\ExamScheduleController;
use App\Http\Controllers\Api\ExamInstructionController;
use App\Http\Controllers\Api\ExamParticipantController;
use App\Http\Controllers\Api\ExamResultController;
use App\Http\Controllers\Api\ExamAnswerController;

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


    // =========================
    // ROLES
    // =========================
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{role}', [RoleController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/roles', [RoleController::class, 'store']);
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
        Route::put('/roles/{role}', [RoleController::class, 'update']);
        Route::patch('/roles/{role}', [RoleController::class, 'update']);
        Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    });


    // =========================
    // PERMISSIONS
    // =========================
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::get('/permissions/{permission}', [PermissionController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/permissions', [PermissionController::class, 'store']);
        Route::put('/permissions/{permission}', [PermissionController::class, 'update']);
        Route::patch('/permissions/{permission}', [PermissionController::class, 'update']);
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);
    });


    // =========================
    // USERS MANAGEMENT
    // =========================
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });


    // =========================
    // EXAMS
    // =========================
    Route::get('/exams', [ExamController::class, 'index']);
    Route::get('/exams/{exam}', [ExamController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/exams', [ExamController::class, 'store']);
        Route::put('/exams/{exam}', [ExamController::class, 'update']);
        Route::patch('/exams/{exam}', [ExamController::class, 'update']);
        Route::delete('/exams/{exam}', [ExamController::class, 'destroy']);
    });


    // =========================
    // EXAM SESSIONS
    // =========================
    Route::get('/exam-sessions', [ExamSessionController::class, 'index']);
    Route::get('/exam-sessions/{exam_session}', [ExamSessionController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/exam-sessions', [ExamSessionController::class, 'store']);
        Route::put('/exam-sessions/{exam_session}', [ExamSessionController::class, 'update']);
        Route::patch('/exam-sessions/{exam_session}', [ExamSessionController::class, 'update']);
        Route::delete('/exam-sessions/{exam_session}', [ExamSessionController::class, 'destroy']);
    });


    // =========================
    // EXAM SCHEDULES
    // =========================
    Route::get('/exam-schedules', [ExamScheduleController::class, 'index']);
    Route::get('/exam-schedules/{exam_schedule}', [ExamScheduleController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/exam-schedules', [ExamScheduleController::class, 'store']);
        Route::put('/exam-schedules/{exam_schedule}', [ExamScheduleController::class, 'update']);
        Route::patch('/exam-schedules/{exam_schedule}', [ExamScheduleController::class, 'update']);
        Route::delete('/exam-schedules/{exam_schedule}', [ExamScheduleController::class, 'destroy']);
    });


    // =========================
    // EXAM INSTRUCTIONS
    // =========================
    Route::get('/exam-instructions', [ExamInstructionController::class, 'index']);
    Route::get('/exam-instructions/{exam_instruction}', [ExamInstructionController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/exam-instructions', [ExamInstructionController::class, 'store']);
        Route::put('/exam-instructions/{exam_instruction}', [ExamInstructionController::class, 'update']);
        Route::patch('/exam-instructions/{exam_instruction}', [ExamInstructionController::class, 'update']);
        Route::delete('/exam-instructions/{exam_instruction}', [ExamInstructionController::class, 'destroy']);
    });


    // =========================
    // EXAM PARTICIPANTS
    // =========================
    Route::get('/exam-participants', [ExamParticipantController::class, 'index']);
    Route::get('/exam-participants/{exam_participant}', [ExamParticipantController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/exam-participants', [ExamParticipantController::class, 'store']);
        Route::put('/exam-participants/{exam_participant}', [ExamParticipantController::class, 'update']);
        Route::patch('/exam-participants/{exam_participant}', [ExamParticipantController::class, 'update']);
        Route::delete('/exam-participants/{exam_participant}', [ExamParticipantController::class, 'destroy']);
    });


    // =========================
    // EXAM RESULTS
    // =========================
    Route::get('/exam-results', [ExamResultController::class, 'index']);
    Route::get('/exam-results/{exam_result}', [ExamResultController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/exam-results', [ExamResultController::class, 'store']);
        Route::put('/exam-results/{exam_result}', [ExamResultController::class, 'update']);
        Route::patch('/exam-results/{exam_result}', [ExamResultController::class, 'update']);
        Route::delete('/exam-results/{exam_result}', [ExamResultController::class, 'destroy']);
    });


    // =========================
    // EXAM ANSWERS
    // =========================
    Route::get('/exam-answers', [ExamAnswerController::class, 'index']);
    Route::get('/exam-answers/{exam_answer}', [ExamAnswerController::class, 'show']);

    Route::middleware('role:Admin,Administrator')->group(function () {
        Route::post('/exam-answers', [ExamAnswerController::class, 'store']);
        Route::put('/exam-answers/{exam_answer}', [ExamAnswerController::class, 'update']);
        Route::patch('/exam-answers/{exam_answer}', [ExamAnswerController::class, 'update']);
        Route::delete('/exam-answers/{exam_answer}', [ExamAnswerController::class, 'destroy']);
    });
});
