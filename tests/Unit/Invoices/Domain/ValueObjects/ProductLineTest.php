<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\ValueObjects\ProductLine;
use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProductLineTest extends TestCase
{
    #[Test]
    public function total_unit_price_multiplies_quantity_and_unit_price(): void
    {
        $line = new ProductLine(
            new ProductName('Widget'),
            new Quantity(4),
            new UnitPrice(250),
        );

        self::assertSame(1000, $line->totalUnitPrice());
    }
}
