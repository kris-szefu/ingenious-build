<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidCustomer;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class CustomerNameTest extends TestCase
{
    #[Test]
    public function it_stores_the_trimmed_value(): void
    {
        $name = new CustomerName('  Ada Lovelace  ');

        self::assertSame('Ada Lovelace', $name->value);
        self::assertSame('Ada Lovelace', (string) $name);
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith(['   '])]
    #[TestWith(["\t\n"])]
    public function it_rejects_empty_or_whitespace_only_values(string $value): void
    {
        $this->expectException(InvalidCustomer::class);
        $this->expectExceptionMessage('Customer name must not be empty.');

        new CustomerName($value);
    }
}
