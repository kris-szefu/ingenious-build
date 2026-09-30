<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\SendInvoice;

use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final readonly class SendInvoiceCommand
{
    public function __construct(
        public InvoiceId $id,
    ) {}
}
