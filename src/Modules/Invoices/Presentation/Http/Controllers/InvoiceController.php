<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Invoices\Application\UseCases\CreateInvoice;
use Modules\Invoices\Application\UseCases\GetInvoice;
use Modules\Invoices\Application\UseCases\SendInvoice;
use Modules\Invoices\Presentation\Http\Requests\CreateInvoiceRequest;
use Modules\Invoices\Presentation\Http\Resources\InvoiceResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class InvoiceController
{
    public function __construct(
        private CreateInvoice $createInvoice,
        private GetInvoice $getInvoice,
        private SendInvoice $sendInvoice,
    ) {}

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->createInvoice->handle(
            customerName: $request->string('customer_name')->toString(),
            customerEmail: $request->string('customer_email')->toString(),
            productLines: $request->productLines(),
        );

        return new InvoiceResource($invoice)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $invoiceId): JsonResponse
    {
        $invoice = $this->getInvoice->handle($invoiceId);

        return new InvoiceResource($invoice)->response();
    }

    public function send(string $invoiceId): JsonResponse
    {
        $invoice = $this->sendInvoice->handle($invoiceId);

        return new InvoiceResource($invoice)->response();
    }
}
