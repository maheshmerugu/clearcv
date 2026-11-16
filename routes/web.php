<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ReviewController::class, 'edit'])->name('profile');
    Route::post('/profile/review', [ReviewController::class, 'store'])->name('profile.review.store');
    Route::delete('/profile/review', [ReviewController::class, 'destroy'])->name('profile.review.destroy');
    Route::get('/dashboard', [ResumeController::class, 'dashboard'])->name('dashboard');
    Route::post('/resumes', [ResumeController::class, 'store'])->name('resumes.store');
    Route::get('/resumes/{resume}', [ResumeController::class, 'show'])->whereNumber('resume')->name('resumes.show');
    Route::put('/resumes/{resume}', [ResumeController::class, 'update'])->whereNumber('resume')->name('resumes.update');
    Route::post('/resumes/{resume}/tailor', [ResumeController::class, 'tailor'])->whereNumber('resume')->name('resumes.tailor');
    Route::get('/resumes/{resume}/download', [ResumeController::class, 'download'])->whereNumber('resume')->name('resumes.download');
    Route::delete('/resumes/{resume}', [ResumeController::class, 'destroy'])->whereNumber('resume')->name('resumes.destroy');
});
