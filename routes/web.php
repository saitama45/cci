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
    Route::get('/api/global-search', [\App\Http\Controllers\GlobalSearchController::class, 'search'])->name('api.global-search');
    Route::get('admin/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('admin.settings.index');
    Route::put('admin/settings/audit', [\App\Http\Controllers\SettingController::class, 'updateAuditSettings'])->name('admin.settings.audit.update');
    Route::post('admin/settings/audit/prune', [\App\Http\Controllers\SettingController::class, 'pruneLogs'])->name('admin.settings.audit.prune');
    Route::get('admin/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('admin.activity-logs.index');

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
            Route::get('contracted-sales/{contracted_sale}/ledger', [\App\Http\Controllers\ContractedSalesController::class, 'ledger'])->name('contracted-sales.ledger');
            Route::get('contracted-sales/{contracted_sale}/soa', [\App\Http\Controllers\ContractedSalesController::class, 'exportSOA'])->name('contracted-sales.soa');
            Route::post('contracted-sales/{id}/reprice', [\App\Http\Controllers\ContractedSalesController::class, 'reprice'])->name('contracted-sales.reprice');
            Route::post('reservations/{reservation}/contract', [ReservationController::class, 'contract'])->name('reservations.contract');
            Route::post('reservations/{reservation}/cancel-accounting', [ReservationController::class, 'cancel'])->name('reservations.cancel-accounting');
            Route::post('reservations/{reservation}/record-payment', [ReservationController::class, 'recordPayment'])->name('reservations.record-payment');
            Route::resource('document-requirements', \App\Http\Controllers\DocumentRequirementController::class);
            Route::resource('customer-documents', \App\Http\Controllers\CustomerDocumentController::class)->only(['store', 'destroy']);
            
            // Accounting & Finance
            Route::resource('vendors', \App\Http\Controllers\VendorController::class);
            Route::post('vendors/{vendor}/documents', [\App\Http\Controllers\VendorController::class, 'uploadDocument'])->name('vendors.documents.upload');
            Route::delete('vendors/documents/{document}', [\App\Http\Controllers\VendorController::class, 'deleteDocument'])->name('vendors.documents.delete');
            Route::resource('journal-entries', \App\Http\Controllers\JournalEntryController::class);
            Route::resource('payments', \App\Http\Controllers\PaymentController::class);
            Route::get('api/customers/{customer}/contracts', [\App\Http\Controllers\PaymentController::class, 'getCustomerContracts'])->name('api.customers.contracts');
            Route::get('api/contracts/{contract}/schedules', [\App\Http\Controllers\PaymentController::class, 'getContractSchedules'])->name('api.contracts.schedules');
            Route::resource('chart-of-accounts', \App\Http\Controllers\ChartOfAccountController::class);
            Route::resource('bills', \App\Http\Controllers\BillController::class)->names([
                'index' => 'accounting.bills.index',
                'create' => 'accounting.bills.create',
                'store' => 'accounting.bills.store',
                'show' => 'accounting.bills.show',
                'edit' => 'accounting.bills.edit',
                'update' => 'accounting.bills.update',
                'destroy' => 'accounting.bills.destroy',
            ]);

            Route::resource('purchase-orders', \App\Http\Controllers\PurchaseOrderController::class)->names([
                'index' => 'accounting.purchase-orders.index',
                'create' => 'accounting.purchase-orders.create',
                'store' => 'accounting.purchase-orders.store',
                'show' => 'accounting.purchase-orders.show',
                'edit' => 'accounting.purchase-orders.edit',
                'update' => 'accounting.purchase-orders.update',
                'destroy' => 'accounting.purchase-orders.destroy',
            ]);
            Route::get('purchase-orders/{purchase_order}/print', [\App\Http\Controllers\PurchaseOrderController::class, 'print'])->name('accounting.purchase-orders.print');
            Route::post('purchase-orders/{purchase_order}/approve', [\App\Http\Controllers\PurchaseOrderController::class, 'approve'])->name('accounting.purchase-orders.approve');
            Route::post('purchase-orders/{purchase_order}/convert-to-bill', [\App\Http\Controllers\PurchaseOrderController::class, 'convertToBill'])->name('accounting.purchase-orders.convert-to-bill');

            Route::resource('disbursements', \App\Http\Controllers\DisbursementController::class)->names([
                'index' => 'accounting.disbursements.index',
                'create' => 'accounting.disbursements.create',
                'store' => 'accounting.disbursements.store',
                'show' => 'accounting.disbursements.show',
            ]);
            Route::post('disbursements/{disbursement}/approve', [\App\Http\Controllers\DisbursementController::class, 'approve'])->name('accounting.disbursements.approve');
            Route::get('disbursements/{disbursement}/print', [\App\Http\Controllers\DisbursementController::class, 'print'])->name('accounting.disbursements.print');
            Route::get('disbursements/{disbursement}/print-check', [\App\Http\Controllers\DisbursementController::class, 'printCheck'])->name('accounting.disbursements.print-check');
            Route::get('disbursements-vault', [\App\Http\Controllers\DisbursementController::class, 'vault'])->name('accounting.disbursements.vault');
            Route::post('pdc-vault/{pdc}/clear', [\App\Http\Controllers\DisbursementController::class, 'markAsCleared'])->name('accounting.disbursements.pdc-clear');
            Route::post('pdc-vault/{pdc}/bounce', [\App\Http\Controllers\DisbursementController::class, 'markAsBounced'])->name('accounting.disbursements.pdc-bounce');
            Route::get('api/vendors/{vendor}/bills', [\App\Http\Controllers\DisbursementController::class, 'getVendorBills'])->name('api.vendors.bills');

            // Bank Reconciliation
            Route::resource('reconciliations', \App\Http\Controllers\BankReconciliationController::class)->names([
                'index' => 'accounting.reconciliations.index',
                'create' => 'accounting.reconciliations.create',
                'store' => 'accounting.reconciliations.store',
                'show' => 'accounting.reconciliations.show',
                'update' => 'accounting.reconciliations.update',
            ])->except(['edit', 'destroy']);
            Route::post('reconciliations/{reconciliation}/lines/{line}/toggle', [\App\Http\Controllers\BankReconciliationController::class, 'toggleLine'])->name('accounting.reconciliations.toggle-line');
            Route::post('reconciliations/{reconciliation}/bulk-toggle', [\App\Http\Controllers\BankReconciliationController::class, 'bulkToggle'])->name('accounting.reconciliations.bulk-toggle');
            Route::post('reconciliations/{reconciliation}/complete', [\App\Http\Controllers\BankReconciliationController::class, 'complete'])->name('accounting.reconciliations.complete');

            // Bank Management
            Route::resource('banks', \App\Http\Controllers\BankController::class)->names([
                'index' => 'accounting.banks.index',
                'store' => 'accounting.banks.store',
                'update' => 'accounting.banks.update',
                'destroy' => 'accounting.banks.destroy',
            ])->except(['create', 'show', 'edit']);

            // Accounting Reports
            Route::get('accounting/trial-balance', [\App\Http\Controllers\AccountingReportController::class, 'trialBalance'])->name('accounting.trial-balance');
            Route::get('accounting/trial-balance/export', [\App\Http\Controllers\AccountingReportController::class, 'exportTrialBalance'])->name('accounting.trial-balance.export');
            Route::get('accounting/general-ledger', [\App\Http\Controllers\AccountingReportController::class, 'generalLedger'])->name('accounting.general-ledger');
            Route::get('accounting/general-ledger/export', [\App\Http\Controllers\AccountingReportController::class, 'exportGeneralLedger'])->name('accounting.general-ledger.export');
            
            Route::get('accounting/ar-aging', [\App\Http\Controllers\AccountingReportController::class, 'arAging'])->name('accounting.ar-aging');
            Route::get('accounting/ap-aging', [\App\Http\Controllers\AccountingReportController::class, 'apAging'])->name('accounting.ap-aging');
            Route::get('accounting/project-pl', [\App\Http\Controllers\AccountingReportController::class, 'projectPL'])->name('accounting.project-pl');
            
            Route::get('accounting/ar-aging/export', [\App\Http\Controllers\AccountingReportController::class, 'exportAgingReport'])->name('accounting.ar-aging.export');
            Route::get('accounting/ap-aging/export', [\App\Http\Controllers\AccountingReportController::class, 'exportAPAging'])->name('accounting.ap-aging.export');
            Route::get('accounting/project-pl/export', [\App\Http\Controllers\AccountingReportController::class, 'exportProjectPL'])->name('accounting.project-pl.export');
            
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
