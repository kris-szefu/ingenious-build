<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

final readonly class ProductLine
{
    public function __construct(
        public string $name,
        public int $quantity,
        public int $unitPrice,
    ) {}

    public function totalUnitPrice(): int
    {
        return $this->quantity * $this->unitPrice;
    }
}
