<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Invoices\Application\UseCases\GetInvoice;
use Modules\Invoices\Presentation\Http\Resources\InvoiceResource;

final readonly class InvoiceController
{
    public function __construct(
        private GetInvoice $getInvoice,
    ) {}

    public function show(string $invoiceId): JsonResponse
    {
        $invoice = $this->getInvoice->handle($invoiceId);

        return new InvoiceResource($invoice)->response();
    }
}
