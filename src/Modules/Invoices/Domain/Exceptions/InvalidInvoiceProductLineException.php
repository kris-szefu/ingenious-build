<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

final class InvalidInvoiceProductLineException extends DomainException
{
    public static function quantityMustBePositive(int $quantity): self
    {
        return new self(sprintf(
            'Invoice product line quantity must be greater than 0. Given: %d.',
            $quantity,
        ));
    }

    public static function unitPriceMustBePositive(int $unitPrice): self
    {
        return new self(sprintf(
            'Invoice product line unit price must be greater than 0. Given: %d.',
            $unitPrice,
        ));
    }
}
