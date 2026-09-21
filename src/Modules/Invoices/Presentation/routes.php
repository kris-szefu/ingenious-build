<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Presentation\Http\Controllers\InvoiceController;

Route::get('/invoices/{invoiceId}', [InvoiceController::class, 'show'])
    ->whereUuid('invoiceId')
    ->name('invoices.show');
