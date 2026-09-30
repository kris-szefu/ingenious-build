<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Invoices\InvoiceIds;

final class InvoiceTest extends TestCase
{
    #[Test]
    public function total_price_sums_product_line_totals(): void
    {
        $invoice = Invoice::draft(
            InvoiceIds::random(),
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
            [
                new ProductLine(new ProductName('A'), new Quantity(2), new UnitPrice(300)),
                new ProductLine(new ProductName('B'), new Quantity(5), new UnitPrice(100)),
            ],
        );

        self::assertSame(1100, $invoice->totalPrice());
    }

    #[Test]
    public function total_price_is_zero_when_there_are_no_product_lines(): void
    {
        $invoice = Invoice::draft(
            InvoiceIds::random(),
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
        );

        self::assertSame(0, $invoice->totalPrice());
        self::assertSame(StatusEnum::Draft, $invoice->status());
    }
}
