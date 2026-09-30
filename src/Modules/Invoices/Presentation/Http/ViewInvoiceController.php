<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Modules\Invoices\Application\UseCases\GetInvoice\GetInvoiceHandler;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Symfony\Component\HttpFoundation\Response;

final readonly class ViewInvoiceController
{
    public function __construct(
        private GetInvoiceHandler $getInvoiceHandler,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $invoice = $this->getInvoiceHandler->handle(InvoiceId::fromString($id));

        return new JsonResponse($invoice->toArray(), Response::HTTP_OK);
    }
}
