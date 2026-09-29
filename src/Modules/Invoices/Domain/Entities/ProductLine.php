<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;

final readonly class ProductLine
{
    public function __construct(
        public ProductName $productName,
        public Quantity $quantity,
        public UnitPrice $unitPrice,
    ) {}

    public function totalUnitPrice(): int
    {
        return $this->quantity->value * $this->unitPrice->value;
    }
}
