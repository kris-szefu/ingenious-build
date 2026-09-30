<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\MarkInvoiceDelivered;

use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final readonly class MarkInvoiceDeliveredCommand
{
    public function __construct(
        public InvoiceId $id,
    ) {}
}

