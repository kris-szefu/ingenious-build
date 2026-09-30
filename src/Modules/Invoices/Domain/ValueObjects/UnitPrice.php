<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidProductLine;

final readonly class UnitPrice
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw InvalidProductLine::nonPositiveUnitPrice($value);
        }
    }
}
