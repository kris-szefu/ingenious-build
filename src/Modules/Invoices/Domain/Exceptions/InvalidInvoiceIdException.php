<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

final class InvalidInvoiceIdException extends DomainException
{
    public static function empty(): self
    {
        return new self('Invoice ID must not be empty.');
    }
}
