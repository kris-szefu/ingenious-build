<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

final class InvoiceCannotBeSentException extends DomainException
{
    public static function missingProductLines(): self
    {
        return new self('Invoice cannot be sent without at least one product line.');
    }
}
