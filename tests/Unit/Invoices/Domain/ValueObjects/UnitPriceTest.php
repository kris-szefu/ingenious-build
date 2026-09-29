<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidProductLine;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UnitPriceTest extends TestCase
{
    #[Test]
    public function it_accepts_positive_integers(): void
    {
        self::assertSame(1500, (new UnitPrice(1500))->value);
    }

    #[Test]
    #[DataProvider('nonPositiveValues')]
    public function it_rejects_zero_and_negatives(int $value): void
    {
        $this->expectException(InvalidProductLine::class);
        new UnitPrice($value);
    }

    /** @return iterable<string, array<int, int>> */
    public static function nonPositiveValues(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }
}
