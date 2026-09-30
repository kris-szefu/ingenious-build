<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Presentation\Http\CreateInvoiceController;
use Modules\Invoices\Presentation\Http\ViewInvoiceController;

Route::post('/invoices', CreateInvoiceController::class)->name('invoices.create');
Route::get('/invoices/{id}', ViewInvoiceController::class)->name('invoices.view');
