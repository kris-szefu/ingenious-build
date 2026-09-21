<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

final class InvalidInvoiceId extends \DomainException
{
    public static function fromString(string $value): self
    {
        return new self(sprintf('"%s" is not a valid invoice id.', $value));
    }
}
