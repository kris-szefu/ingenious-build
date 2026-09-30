<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

/**
 * A product line on an invoice. Pure value object — no identity, immutable.
 *
 * Equality is structural (product name + quantity + unit price). Invariants
 * are delegated to the composing value objects:
 * {@see ProductName}, {@see Quantity}, {@see UnitPrice}.
 */
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
