<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\ValueObjects\ProductLine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProductLineTest extends TestCase
{
    #[Test]
    public function total_unit_price(): void
    {
        $productLine = new ProductLine('test', 2, 10);
        $this->assertSame(20, $productLine->totalUnitPrice());
    }

    #[Test]
    #[DataProvider('amounts')]
    public function it_knows_whether_both_amounts_are_positive(int $quantity, int $unitPrice, bool $expected): void
    {
        $productLine = new ProductLine('Desk', $quantity, $unitPrice);

        $this->assertSame($expected, $productLine->hasPositiveAmounts());
    }

    /** @return array<string, array{int, int, bool}> */
    public static function amounts(): array
    {
        return [
            'both positive' => [2, 100, true],
            'zero quantity' => [0, 100, false],
            'zero unit price' => [2, 0, false],
            'negative quantity' => [-1, 100, false],
            'negative unit price' => [2, -100, false],
        ];
    }
}
