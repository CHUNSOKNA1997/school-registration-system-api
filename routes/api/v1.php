<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\ClassroomController;
use App\Http\Controllers\API\V1\DashboardController;
use App\Http\Controllers\API\V1\StudentController;
use App\Http\Controllers\API\V1\StudentSubjectController;
use App\Http\Controllers\API\V1\SubjectController;
use App\Http\Controllers\API\V1\TeacherController;
use App\Http\Controllers\API\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1', 'as' => 'v1.'], function () {
    Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
        // Public routes
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        // Protected routes
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::delete('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
        });
    });

    // Protected resource routes (Staff & Admin)
    Route::middleware(['auth:sanctum'])->group(function () {
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('dashboard/registration-trends', [DashboardController::class, 'registrationTrends']);
        Route::get('dashboard/payment-trends', [DashboardController::class, 'paymentTrends']);
        Route::get('dashboard/top-students', [DashboardController::class, 'topStudents']);

        // Profile
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);

        // Students - Staff can create/view, Admin can update/delete
        Route::get('students', [StudentController::class, 'index']);
        Route::get('students/{student}', [StudentController::class, 'show']);
        Route::post('students', [StudentController::class, 'store']);

        // Student-Subject enrollment routes (Staff & Admin)
        Route::prefix('students/{student}')->group(function () {
            Route::get('enrollments', [StudentSubjectController::class, 'index']);
            Route::post('enrollments', [StudentSubjectController::class, 'store']);
            Route::put('enrollments/{enrollment}', [StudentSubjectController::class, 'update']);
            Route::delete('enrollments/{enrollment}', [StudentSubjectController::class, 'destroy']);
            Route::get('transcript', [StudentSubjectController::class, 'transcript']);
        });

        // Bulk enrollment (Staff & Admin)
        Route::post('enrollments/bulk', [StudentSubjectController::class, 'bulkEnroll']);
    });

    // Admin-only routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Students - Update/Delete (Admin only)
        Route::put('students/{student}', [StudentController::class, 'update']);
        Route::delete('students/{student}', [StudentController::class, 'destroy']);

        // User Management (Admin only)
        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{id}', [UserController::class, 'show']);
        Route::put('users/{id}', [UserController::class, 'update']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);
        Route::post('users/{id}/activate', [UserController::class, 'activate']);

        Route::apiResource('teachers', TeacherController::class);
        Route::apiResource('classrooms', ClassroomController::class);
        Route::apiResource('subjects', SubjectController::class);
    });
});
