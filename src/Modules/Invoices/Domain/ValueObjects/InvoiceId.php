<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Stringable;

final readonly class InvoiceId implements Stringable
{
    private function __construct(public string $value)
    {
        if (Uuid::isValid($value) === false) {
            throw new InvalidArgumentException("Invalid InvoiceId UUID: {$value}");
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
