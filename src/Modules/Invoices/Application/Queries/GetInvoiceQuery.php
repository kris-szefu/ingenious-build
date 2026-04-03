<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Queries;

final readonly class GetInvoiceQuery
{
    public function __construct(
        public string $invoiceId,
    ) {}
}
