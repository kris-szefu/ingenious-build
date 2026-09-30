<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

final class InvalidInvoiceId extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self("Invalid InvoiceId: {$value}");
    }
}
