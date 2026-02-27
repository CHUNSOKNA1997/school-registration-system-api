<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\ClassroomController;
use App\Http\Controllers\API\V1\DashboardController;
use App\Http\Controllers\API\V1\PaymentController;
use App\Http\Controllers\API\V1\StudentController;
use App\Http\Controllers\API\V1\StudentSubjectController;
use App\Http\Controllers\API\V1\SubjectController;
use App\Http\Controllers\API\V1\TeacherController;
use App\Http\Controllers\API\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1', 'as' => 'v1.'], function () {
    // Canonical auth/session routes
    Route::post('registrations', [AuthController::class, 'createRegistration']);
    Route::post('sessions', [AuthController::class, 'createSession']);
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('sessions/current', [AuthController::class, 'showCurrentSession']);
        Route::delete('sessions/current', [AuthController::class, 'destroyCurrentSession']);
    });

    // Protected resource routes (Staff & Admin)
    Route::middleware(['auth:sanctum'])->group(function () {
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('dashboard/registration-trends', [DashboardController::class, 'registrationTrends']);
        Route::get('dashboard/payment-trends', [DashboardController::class, 'paymentTrends']);
        Route::get('dashboard/top-students', [DashboardController::class, 'topStudents']);

        // Canonical self-profile routes
        Route::get('users/me', [UserController::class, 'userMe']);
        Route::patch('users/me', [UserController::class, 'updateUserMe']);

        // Canonical payment routes (REST-style)
        Route::post('payments/{payment_uuid}/checkout-sessions', [PaymentController::class, 'createCheckoutSession']);
        Route::get('payments/{payment_uuid}', [PaymentController::class, 'showPayment']);

        // Payment plan options for student registration UI
        Route::get('payment-plans', [StudentController::class, 'paymentPlans']);
        Route::post('student-registrations', [StudentController::class, 'selfRegister']);

        // Students - Staff can create/view, Admin can update/delete
        Route::get('students', [StudentController::class, 'index']);
        Route::get('students/{student}', [StudentController::class, 'show']);
        Route::post('students', [StudentController::class, 'store']);

        // Student-Subject enrollment routes (Staff & Admin)
        Route::prefix('students/{student}')->group(function () {
            Route::get('enrollments', [StudentSubjectController::class, 'index']);
            Route::post('enrollments', [StudentSubjectController::class, 'store']);
            Route::patch('enrollments/{enrollment}', [StudentSubjectController::class, 'updatePartial']);
            Route::delete('enrollments/{enrollment}', [StudentSubjectController::class, 'destroy']);
            Route::get('transcript', [StudentSubjectController::class, 'transcript']);
        });

        // Canonical bulk enrollment batch endpoint
        Route::post('enrollment-batches', [StudentSubjectController::class, 'createEnrollmentBatch']);
    });

    // Admin-only routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Students - Update/Delete (Admin only)
        Route::patch('students/{student}', [StudentController::class, 'update']);
        Route::delete('students/{student}', [StudentController::class, 'destroy']);

        // User Management (Admin only)
        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{id}', [UserController::class, 'show']);
        Route::patch('users/{id}', [UserController::class, 'update']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);

        Route::apiResource('teachers', TeacherController::class);
        Route::apiResource('classrooms', ClassroomController::class);
        Route::apiResource('subjects', SubjectController::class);
    });
});
