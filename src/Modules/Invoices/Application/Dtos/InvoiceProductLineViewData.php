<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

use Modules\Invoices\Domain\Entities\InvoiceProductLine;

final readonly class InvoiceProductLineViewData
{
    public function __construct(
        public string $productName,
        public int $quantity,
        public int $unitPrice,
        public int $totalUnitPrice,
    ) {}

    public static function fromDomain(InvoiceProductLine $line): self
    {
        return new self(
            productName: $line->name(),
            quantity: $line->quantity(),
            unitPrice: $line->unitPrice(),
            totalUnitPrice: $line->totalUnitPrice(),
        );
    }
}
