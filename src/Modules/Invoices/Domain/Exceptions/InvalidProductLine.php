<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

final class InvalidProductLine extends DomainException
{
    public static function nonPositiveQuantity(int $value): self
    {
        return new self("Quantity must be a positive integer, got {$value}.");
    }

    public static function nonPositiveUnitPrice(int $value): self
    {
        return new self("Unit price must be a positive integer, got {$value}.");
    }

    public static function emptyProductName(): self
    {
        return new self('Product name must not be empty.');
    }
}
