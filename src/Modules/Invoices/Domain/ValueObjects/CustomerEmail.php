<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidCustomerEmail;

final readonly class CustomerEmail
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw InvalidCustomerEmail::fromString($value);
        }

        return new self($value);
    }
}
