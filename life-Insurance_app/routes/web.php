<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerIntentionController;
use App\Http\Controllers\InsuranceContractController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\MaintenanceHistoryController;
use App\Http\Controllers\Settings\InsurancePlanController;
use App\Http\Controllers\Settings\InsurancePlanPriceController;
use App\Http\Controllers\Settings\RetentionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    // 初期パスワード変更（must_change_password の解除）
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function (): void {
        Route::view('/', 'index')->name('dashboard');

        // 顧客詳細・編集など個人情報を表示する画面は Cache-Control: no-store
        Route::get('/customers/export', [CustomerController::class, 'export'])
            ->middleware('no-store')
            ->name('customers.export');

        Route::resource('customers', CustomerController::class)
            ->middleware('no-store');

        Route::post(
            '/customers/{customer}/intentions',
            [CustomerIntentionController::class, 'store']
        )->name('customers.intentions.store');

        Route::get(
            '/customers/{customer}/contracts/create',
            [InsuranceContractController::class, 'create']
        )->middleware('no-store')->name('customers.contracts.create');

        Route::post(
            '/customers/{customer}/contracts',
            [InsuranceContractController::class, 'store']
        )->name('customers.contracts.store');

        Route::get(
            '/customers/{customer}/contracts/{contract}/edit',
            [InsuranceContractController::class, 'edit']
        )->middleware('no-store')->name('customers.contracts.edit');

        Route::put(
            '/customers/{customer}/contracts/{contract}',
            [InsuranceContractController::class, 'update']
        )->name('customers.contracts.update');

        Route::post(
            '/customers/{customer}/interactions',
            [InteractionController::class, 'store']
        )->name('customers.interactions.store');

        Route::post(
            '/customers/{customer}/maintenance-histories',
            [MaintenanceHistoryController::class, 'store']
        )->name('customers.maintenance-histories.store');

        Route::middleware('can:manage-settings')->prefix('settings')->name('settings.')->group(
            function (): void {
                Route::resource('plans', InsurancePlanController::class)
                    ->except(['show']);

                Route::post(
                    '/plans/{plan}/prices',
                    [InsurancePlanPriceController::class, 'store']
                )->name('plans.prices.store');

                Route::patch(
                    '/plans/{plan}/status',
                    [InsurancePlanController::class, 'updateStatus']
                )->name('plans.status.update');

                Route::get('/retention', [RetentionController::class, 'edit'])->name('retention.edit');
                Route::put('/retention', [RetentionController::class, 'update'])->name('retention.update');
            }
        );

        Route::middleware('can:manage-users')->group(function (): void {
            Route::resource('users', UserController::class)->except(['show']);
        });

        Route::middleware(['can:view-audit-logs', 'no-store'])->group(function (): void {
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });
    });
});
