<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Presentation\Http\CreateInvoiceController;

Route::post('/invoices', CreateInvoiceController::class)->name('invoices.create');

