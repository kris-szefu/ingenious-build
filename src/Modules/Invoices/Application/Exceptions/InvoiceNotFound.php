<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Exceptions;

use RuntimeException;

final class InvoiceNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Invoice "%s" was not found.', $id));
    }
}
