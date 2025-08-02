<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/users-json', [UserController::class, 'usersJson']);

Route::get('/users', function () {
    return Inertia::render('Users');
});

Route::get('/test-users', function() {
    return \App\Models\User::take(5)->get();
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
