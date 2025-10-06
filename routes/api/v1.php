<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClassroomController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\SubjectController;
use App\Http\Controllers\API\TeacherController;
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

    // Protected resource routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('students', StudentController::class);
        Route::apiResource('teachers', TeacherController::class);
        Route::apiResource('classrooms', ClassroomController::class);
        Route::apiResource('subjects', SubjectController::class);
    });
});
