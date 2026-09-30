<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidProductLine;
use Stringable;

final readonly class ProductName implements Stringable
{
    public function __construct(public string $value)
    {
        if (trim($value) === '') {
            throw InvalidProductLine::emptyProductName();
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
