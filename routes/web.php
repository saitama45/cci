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
            Route::resource('brokers', \App\Http\Controllers\BrokerController::class);
            Route::resource('reservations', ReservationController::class);
            Route::resource('contracted-sales', \App\Http\Controllers\ContractedSalesController::class)->only(['index', 'show']);
            Route::post('contracted-sales/{id}/reprice', [\App\Http\Controllers\ContractedSalesController::class, 'reprice'])->name('contracted-sales.reprice');
            Route::post('reservations/{reservation}/contract', [ReservationController::class, 'contract'])->name('reservations.contract');
            Route::post('reservations/{reservation}/cancel-accounting', [ReservationController::class, 'cancel'])->name('reservations.cancel-accounting');
            Route::post('reservations/{reservation}/record-payment', [ReservationController::class, 'recordPayment'])->name('reservations.record-payment');
            Route::resource('document-requirements', \App\Http\Controllers\DocumentRequirementController::class);
            Route::resource('customer-documents', \App\Http\Controllers\CustomerDocumentController::class)->only(['store', 'destroy']);
            
            // Accounting & Finance
            Route::resource('journal-entries', \App\Http\Controllers\JournalEntryController::class);
            Route::resource('payments', \App\Http\Controllers\PaymentController::class);
            Route::get('api/customers/{customer}/contracts', [\App\Http\Controllers\PaymentController::class, 'getCustomerContracts'])->name('api.customers.contracts');
            Route::get('api/contracts/{contract}/schedules', [\App\Http\Controllers\PaymentController::class, 'getContractSchedules'])->name('api.contracts.schedules');
            Route::resource('chart-of-accounts', \App\Http\Controllers\ChartOfAccountController::class);

            // Accounting Reports
            Route::get('accounting/trial-balance', [\App\Http\Controllers\AccountingReportController::class, 'trialBalance'])->name('accounting.trial-balance');
            Route::get('accounting/trial-balance/export', [\App\Http\Controllers\AccountingReportController::class, 'exportTrialBalance'])->name('accounting.trial-balance.export');
            Route::get('accounting/general-ledger', [\App\Http\Controllers\AccountingReportController::class, 'generalLedger'])->name('accounting.general-ledger');
            Route::get('accounting/general-ledger/export', [\App\Http\Controllers\AccountingReportController::class, 'exportGeneralLedger'])->name('accounting.general-ledger.export');
            Route::get('accounting/aging-report', [\App\Http\Controllers\AccountingReportController::class, 'agingReport'])->name('accounting.aging-report');
            Route::get('accounting/aging-report/export', [\App\Http\Controllers\AccountingReportController::class, 'exportAgingReport'])->name('accounting.aging-report.export');
            Route::get('accounting/overall-receivables', [\App\Http\Controllers\AccountingReportController::class, 'overallReceivables'])->name('accounting.overall-receivables');
            Route::get('accounting/overall-receivables/export', [\App\Http\Controllers\AccountingReportController::class, 'exportOverallReceivables'])->name('accounting.overall-receivables.export');

            Route::get('/api/projects/{project}/units', [\App\Http\Controllers\UnitController::class, 'getUnitsByProject'])->name('api.projects.units'); 
         
     // Moving getUnitsByProject to UnitController might be cleaner, but for now lets stick to the plan or put it in UnitController.
    
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

require __DIR__.'/auth.php';
