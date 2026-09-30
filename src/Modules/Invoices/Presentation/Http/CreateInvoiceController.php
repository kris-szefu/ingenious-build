<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Modules\Invoices\Application\UseCases\CreateInvoice\CreateInvoiceCommand;
use Modules\Invoices\Application\UseCases\CreateInvoice\CreateInvoiceHandler;
use Modules\Invoices\Application\UseCases\CreateInvoice\ProductLineInput;
use Modules\Invoices\Application\UseCases\GetInvoice\GetInvoiceHandler;
use Modules\Invoices\Presentation\Http\Requests\CreateInvoiceRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class CreateInvoiceController
{
    public function __construct(
        private CreateInvoiceHandler $createInvoiceHandler,
        private GetInvoiceHandler $getInvoiceHandler,
    ) {}

    public function __invoke(CreateInvoiceRequest $request): JsonResponse
    {
        $productLines = array_map(
            static fn (array $line): ProductLineInput => new ProductLineInput(
                productName: $line['product_name'],
                quantity: (int) $line['quantity'],
                unitPrice: (int) $line['unit_price'],
            ),
            $request->input('product_lines', []),
        );

        $invoiceId = $this->createInvoiceHandler->handle(new CreateInvoiceCommand(
            customerName: $request->input('customer_name'),
            customerEmail: $request->input('customer_email'),
            productLines: $productLines,
        ));

        $invoice = $this->getInvoiceHandler->handle($invoiceId);

        return new JsonResponse($invoice->toArray(), Response::HTTP_CREATED);
    }
}
