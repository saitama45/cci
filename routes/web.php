<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReservationController;
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
            Route::resource('customers', CustomerController::class);
            Route::resource('reservations', ReservationController::class);
            Route::post('reservations/{reservation}/contract', [ReservationController::class, 'contract'])->name('reservations.contract');
            Route::post('reservations/{reservation}/cancel-accounting', [ReservationController::class, 'cancel'])->name('reservations.cancel-accounting');
            Route::resource('document-requirements', \App\Http\Controllers\DocumentRequirementController::class);
            Route::resource('customer-documents', \App\Http\Controllers\CustomerDocumentController::class)->only(['store', 'destroy']);
            
            // Accounting & Finance
            Route::resource('journal-entries', \App\Http\Controllers\JournalEntryController::class);
            Route::resource('payments', \App\Http\Controllers\PaymentController::class);
            Route::resource('chart-of-accounts', \App\Http\Controllers\ChartOfAccountController::class);

            // Accounting Reports
            Route::get('accounting/trial-balance', [\App\Http\Controllers\AccountingReportController::class, 'trialBalance'])->name('accounting.trial-balance');
            Route::get('accounting/trial-balance/export', [\App\Http\Controllers\AccountingReportController::class, 'exportTrialBalance'])->name('accounting.trial-balance.export');
            Route::get('accounting/general-ledger', [\App\Http\Controllers\AccountingReportController::class, 'generalLedger'])->name('accounting.general-ledger');
            Route::get('accounting/general-ledger/export', [\App\Http\Controllers\AccountingReportController::class, 'exportGeneralLedger'])->name('accounting.general-ledger.export');

            Route::get('/api/projects/{project}/units', [\App\Http\Controllers\UnitController::class, 'getUnitsByProject'])->name('api.projects.units'); 
         
     // Moving getUnitsByProject to UnitController might be cleaner, but for now lets stick to the plan or put it in UnitController.
    
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

require __DIR__.'/auth.php';
