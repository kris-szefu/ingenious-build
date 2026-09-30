<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Modules\Invoices\Application\UseCases\CreateInvoice\CreateInvoiceCommand;
use Modules\Invoices\Application\UseCases\CreateInvoice\CreateInvoiceHandler;
use Modules\Invoices\Application\UseCases\CreateInvoice\ProductLineInput;
use Modules\Invoices\Presentation\Http\Requests\CreateInvoiceRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class CreateInvoiceController
{
    public function __construct(
        private CreateInvoiceHandler $createInvoiceHandler,
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

        $invoice = $this->createInvoiceHandler->handle(new CreateInvoiceCommand(
            customerName: $request->input('customer_name'),
            customerEmail: $request->input('customer_email'),
            productLines: $productLines,
        ));

        return new JsonResponse(
            data: $invoice->toArray(),
            status: Response::HTTP_CREATED,
            headers: ['Location' => "/api/invoices/{$invoice->id}"],
        );
    }
}
