<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Presentation\Http\InvoiceController;

Route::prefix('invoices')
    ->name('invoices.')
    ->group(static function (): void {
        Route::post('/', [InvoiceController::class, 'create'])->name('create');
        Route::get('/{invoiceId}', [InvoiceController::class, 'view'])->name('view');
        Route::post('/{invoiceId}/send', [InvoiceController::class, 'send'])->name('send');
    });
