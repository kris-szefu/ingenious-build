<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\ValueObjects\ProductLine;
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
}
