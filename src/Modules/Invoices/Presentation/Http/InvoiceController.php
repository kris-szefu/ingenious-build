<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Modules\Invoices\Application\Commands\CreateInvoiceCommand;
use Modules\Invoices\Application\Commands\SendInvoiceCommand;
use Modules\Invoices\Application\Dtos\InvoiceProductLineViewData;
use Modules\Invoices\Application\Dtos\InvoiceViewData;
use Modules\Invoices\Application\Queries\GetInvoiceQuery;
use Modules\Invoices\Application\UseCases\CreateInvoiceUseCase;
use Modules\Invoices\Application\UseCases\GetInvoiceUseCase;
use Modules\Invoices\Application\UseCases\SendInvoiceUseCase;
use Modules\Invoices\Presentation\Http\Requests\CreateInvoiceRequest;
use Modules\Invoices\Presentation\Http\Requests\SendInvoiceRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class InvoiceController
{
    public function __construct(
        private CreateInvoiceUseCase $createInvoiceUseCase,
        private GetInvoiceUseCase $getInvoiceUseCase,
        private SendInvoiceUseCase $sendInvoiceUseCase,
    ) {}

    public function create(CreateInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $invoice = $this->createInvoiceUseCase->execute(new CreateInvoiceCommand(
            customerName: $data['customerName'],
            customerEmail: $data['customerEmail'],
            productLines: $data['productLines'] ?? [],
        ));

        return new JsonResponse($this->toPayload($invoice), Response::HTTP_CREATED);
    }

    public function view(string $invoiceId): JsonResponse
    {
        $invoice = $this->getInvoiceUseCase->execute(new GetInvoiceQuery(
            invoiceId: $invoiceId,
        ));

        return new JsonResponse($this->toPayload($invoice), Response::HTTP_OK);
    }

    public function send(string $invoiceId, SendInvoiceRequest $request): JsonResponse
    {
        $request->validated();

        $invoice = $this->sendInvoiceUseCase->execute(new SendInvoiceCommand(
            invoiceId: $invoiceId,
        ));

        return new JsonResponse($this->toPayload($invoice), Response::HTTP_OK);
    }

    /**
     * @return array{
     *     invoiceId: string,
     *     status: string,
     *     customerName: string,
     *     customerEmail: string,
     *     productLines: array<int, array{
     *         productName: string,
     *         quantity: int,
     *         unitPrice: int,
     *         totalUnitPrice: int
     *     }>,
     *     totalPrice: int
     * }
     */
    private function toPayload(InvoiceViewData $invoice): array
    {
        return [
            'invoiceId' => $invoice->invoiceId,
            'status' => $invoice->status,
            'customerName' => $invoice->customerName,
            'customerEmail' => $invoice->customerEmail,
            'productLines' => array_map(
                static fn (InvoiceProductLineViewData $line): array => [
                    'productName' => $line->productName,
                    'quantity' => $line->quantity,
                    'unitPrice' => $line->unitPrice,
                    'totalUnitPrice' => $line->totalUnitPrice,
                ],
                $invoice->productLines,
            ),
            'totalPrice' => $invoice->totalPrice,
        ];
    }
}
