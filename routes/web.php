<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ClassroomController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\StudentController;
use App\Http\Controllers\Web\StudentSubjectController;
use App\Http\Controllers\Web\SubjectController;
use App\Http\Controllers\Web\TeacherController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Students - Staff can view, create. Admin can update, delete
    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('students', [StudentController::class, 'store'])->name('students.store');
    Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
    
    // Admin only routes
    Route::middleware('admin')->group(function () {
        Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    });
    
    // Student Enrollments - All staff
    Route::get('students/{student}/enrollments', [StudentController::class, 'enrollments'])
        ->name('students.enrollments');
    Route::post('students/{student}/enrollments', [StudentSubjectController::class, 'store'])
        ->name('students.enrollments.store');
    Route::put('students/{student}/enrollments/{enrollment}', [StudentSubjectController::class, 'update'])
        ->name('students.enrollments.update');
    Route::delete('students/{student}/enrollments/{enrollment}', [StudentSubjectController::class, 'destroy'])
        ->name('students.enrollments.destroy');

    // Teachers
    Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
    Route::get('teachers/create', [TeacherController::class, 'create'])->name('teachers.create');
    Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');
    Route::get('teachers/{teacher}', [TeacherController::class, 'show'])->name('teachers.show');

    // Subjects
    Route::get('subjects', [SubjectController::class, 'index'])->name('subjects.index');
    Route::get('subjects/create', [SubjectController::class, 'create'])->name('subjects.create');
    Route::post('subjects', [SubjectController::class, 'store'])->name('subjects.store');
    Route::get('subjects/{subject}', [SubjectController::class, 'show'])->name('subjects.show');

    // Classes
    Route::get('classes', [ClassroomController::class, 'index'])->name('classes.index');
    Route::get('classes/create', [ClassroomController::class, 'create'])->name('classes.create');
    Route::post('classes', [ClassroomController::class, 'store'])->name('classes.store');
    Route::get('classes/{classroom}', [ClassroomController::class, 'show'])->name('classes.show');

    // Admin only routes
    Route::middleware('admin')->group(function () {
        Route::get('teachers/{teacher}/edit', [TeacherController::class, 'edit'])->name('teachers.edit');
        Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');

        Route::get('subjects/{subject}/edit', [SubjectController::class, 'edit'])->name('subjects.edit');
        Route::put('subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::get('classes/{classroom}/edit', [ClassroomController::class, 'edit'])->name('classes.edit');
        Route::put('classes/{classroom}', [ClassroomController::class, 'update'])->name('classes.update');
        Route::delete('classes/{classroom}', [ClassroomController::class, 'destroy'])->name('classes.destroy');
    });
});


// Default welcome route
Route::get('/', function () {
    return redirect('/dashboard');
});
