<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

final class InvoiceNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self("Invoice with id {$id} was not found.");
    }
}
