<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Commands;

final readonly class SendInvoiceCommand
{
    public function __construct(
        public string $invoiceId,
    ) {}
}
