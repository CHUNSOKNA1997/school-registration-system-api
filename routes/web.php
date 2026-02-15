<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\StudentController;
use App\Http\Controllers\Web\StudentSubjectController;
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
});


// Default welcome route
Route::get('/', function () {
    return redirect('/dashboard');
});
