<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidInvoiceIdException;

final readonly class InvoiceId
{
    public function __construct(private string $value)
    {
        if (trim($this->value) === '') {
            throw InvalidInvoiceIdException::empty();
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
