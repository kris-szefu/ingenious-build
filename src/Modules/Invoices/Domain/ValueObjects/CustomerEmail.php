<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class CustomerEmail implements Stringable
{
    public function __construct(public string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid customer email: {$value}");
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
