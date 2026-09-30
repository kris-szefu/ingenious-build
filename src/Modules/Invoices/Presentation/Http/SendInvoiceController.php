<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Illuminate\Http\Response;
use Modules\Invoices\Application\UseCases\SendInvoice\SendInvoiceCommand;
use Modules\Invoices\Application\UseCases\SendInvoice\SendInvoiceHandler;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final readonly class SendInvoiceController
{
    public function __construct(
        private SendInvoiceHandler $sendInvoiceHandler,
    ) {}

    public function __invoke(string $id): Response
    {
        $this->sendInvoiceHandler->handle(
            new SendInvoiceCommand(InvoiceId::fromString($id)),
        );

        return new Response('', HttpResponse::HTTP_ACCEPTED);
    }
}
