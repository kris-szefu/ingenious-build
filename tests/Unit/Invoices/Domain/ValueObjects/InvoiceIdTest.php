<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidInvoiceId;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class InvoiceIdTest extends TestCase
{
    /** @return iterable<string, array<int, string>> */
    public static function invalidValues(): iterable
    {
        yield 'empty' => [''];
        yield 'plain string' => ['not-a-uuid'];
        yield 'truncated uuid' => ['1234'];
    }

    #[Test]
    public function it_accepts_a_valid_uuid(): void
    {
        $value = Uuid::uuid4()->toString();

        self::assertSame($value, InvoiceId::fromString($value)->value);
    }

    #[Test]
    #[DataProvider('invalidValues')]
    public function it_rejects_invalid_values(string $value): void
    {
        $this->expectException(InvalidInvoiceId::class);

        InvoiceId::fromString($value);
    }
}
