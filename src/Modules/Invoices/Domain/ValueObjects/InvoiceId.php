<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidInvoiceId;
use Ramsey\Uuid\Uuid;

final readonly class InvoiceId
{
    private function __construct(public string $value) {}

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        if (! Uuid::isValid($value)) {
            throw InvalidInvoiceId::fromString($value);
        }

        return new self($value);
    }
}
