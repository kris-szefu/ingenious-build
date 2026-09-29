<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class CustomerName implements Stringable
{
    public function __construct(public string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Customer name must not be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
