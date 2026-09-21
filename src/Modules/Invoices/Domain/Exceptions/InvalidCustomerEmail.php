<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

final class InvalidCustomerEmail extends \DomainException
{
    public static function fromString(string $value): self
    {
        return new self(sprintf('"%s" is not a valid email address.', $value));
    }
}
