<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Exceptions;

use RuntimeException;

final class InvoiceNotFoundException extends RuntimeException
{
    public static function withId(string $invoiceId): self
    {
        return new self(sprintf('Invoice with id "%s" was not found.', $invoiceId));
    }
}
