<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceProductLineException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InvoiceProductLineTest extends TestCase
{
    #[DataProvider('invalidQuantityProvider')]
    public function test_rejects_non_positive_quantity(int $quantity): void
    {
        $this->expectException(InvalidInvoiceProductLineException::class);

        new InvoiceProductLine(
            name: 'T-Shirt',
            quantity: $quantity,
            unitPrice: 100,
        );
    }

    #[DataProvider('invalidUnitPriceProvider')]
    public function test_rejects_non_positive_unit_price(int $unitPrice): void
    {
        $this->expectException(InvalidInvoiceProductLineException::class);

        new InvoiceProductLine(
            name: 'T-Shirt',
            quantity: 1,
            unitPrice: $unitPrice,
        );
    }

    #[DataProvider('lineTotalProvider')]
    public function test_calculates_total_unit_price(int $quantity, int $unitPrice, int $expected): void
    {
        $line = new InvoiceProductLine(
            name: 'T-Shirt',
            quantity: $quantity,
            unitPrice: $unitPrice,
        );

        $this->assertSame($expected, $line->totalUnitPrice());
    }

    public static function invalidQuantityProvider(): array
    {
        return [
            [0],
            [-1],
            [-10],
        ];
    }

    public static function invalidUnitPriceProvider(): array
    {
        return [
            [0],
            [-1],
            [-10],
        ];
    }

    public static function lineTotalProvider(): array
    {
        return [
            [1, 1, 1],
            [3, 125, 375],
            [2, 100, 200],
        ];
    }
}
