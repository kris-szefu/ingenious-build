<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidCustomer;
use Stringable;

final readonly class CustomerEmail implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidCustomer::invalidEmail($value);
        }

        $this->value = $trimmed;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
