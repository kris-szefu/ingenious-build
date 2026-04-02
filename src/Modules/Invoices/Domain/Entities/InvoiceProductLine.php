<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Exceptions\InvalidInvoiceProductLineException;

final readonly class InvoiceProductLine
{
    public function __construct(
        private string $name,
        private int $quantity,
        private int $unitPrice,
    ) {
        if ($this->quantity <= 0) {
            throw InvalidInvoiceProductLineException::quantityMustBePositive($this->quantity);
        }

        if ($this->unitPrice <= 0) {
            throw InvalidInvoiceProductLineException::unitPriceMustBePositive($this->unitPrice);
        }
    }

    public function totalUnitPrice(): int
    {
        return $this->quantity * $this->unitPrice;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPrice(): int
    {
        return $this->unitPrice;
    }
}
