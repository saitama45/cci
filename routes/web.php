<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    
    Route::resource('roles', RoleController::class)->except(['show', 'create', 'edit']);
    Route::resource('companies', \App\Http\Controllers\CompanyController::class);
    Route::resource('projects', \App\Http\Controllers\ProjectController::class);
    Route::resource('units', \App\Http\Controllers\UnitController::class);
    Route::resource('price-lists', \App\Http\Controllers\PriceListController::class);
    Route::get('/api/projects/{project}/units', [\App\Http\Controllers\UnitController::class, 'getUnitsByProject'])->name('api.projects.units'); // Moving getUnitsByProject to UnitController might be cleaner, but for now lets stick to the plan or put it in UnitController.
    
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

require __DIR__.'/auth.php';
