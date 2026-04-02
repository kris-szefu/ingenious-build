<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Support;

use Modules\Invoices\Application\Ports\InvoiceIdGeneratorInterface;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final readonly class FixedInvoiceIdGenerator implements InvoiceIdGeneratorInterface
{
    public function __construct(
        private string $invoiceId,
    ) {}

    public function generate(): InvoiceId
    {
        return new InvoiceId($this->invoiceId);
    }
}
