<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceTest extends TestCase
{
    #[Test]
    public function creating_invoice_returns_correct_status(): void
    {
        $invoice = Invoice::create(
            'test',
            CustomerEmail::fromString('test@example.com'),
        );

        $this->assertSame(StatusEnum::Draft, $invoice->status);
    }

    #[Test]
    public function creating_invoices_assigns_different_ids(): void
    {
        $first = Invoice::create(
            'test',
            CustomerEmail::fromString('test@example.com'),
        );
        $second = Invoice::create(
            'test',
            CustomerEmail::fromString('test@example.com'),
        );

        $this->assertNotSame($first->id->value, $second->id->value);
    }

    #[Test]
    public function creating_with_no_lines(): void
    {
        $invoice = Invoice::create(
            'test',
            CustomerEmail::fromString('test@example.com'),
        );
        $this->assertSame(0, $invoice->totalPrice());
        $this->assertSame([], $invoice->productLines);
    }

    #[Test]
    public function total_price_sums_lines(): void
    {
        $invoice = Invoice::create(
            'test',
            CustomerEmail::fromString('test@example.com'),
            [
                new ProductLine('test', 1, 2),
                new ProductLine('test', 2, 2),

            ]
        );
        $this->assertSame(6, $invoice->totalPrice());
    }

    #[Test]
    public function status_cannot_be_modified_outside(): void
    {
        $invoice = Invoice::create(
            'test',
            CustomerEmail::fromString('test@example.com'),
        );
        $this->expectException(\Error::class);
        $invoice->status = StatusEnum::Sending;
    }

    #[Test]
    public function product_line_cannot_be_modified_outside(): void
    {
        $invoice = Invoice::create(
            'test',
            CustomerEmail::fromString('test@example.com'),
        );
        $this->expectException(\Error::class);
        $invoice->productLines = [];
    }

    #[Test]
    public function reconstituted_invoice_keeps_its_status(): void
    {
        $id = InvoiceId::generate();

        $invoice = Invoice::reconstitute(
            $id,
            'test',
            CustomerEmail::fromString('test@example.com'),
            StatusEnum::Sending,
            [new ProductLine('test', 2, 100)],
        );

        $this->assertSame($id->value, $invoice->id->value);
        $this->assertSame(StatusEnum::Sending, $invoice->status);
        $this->assertSame(200, $invoice->totalPrice());
    }
}
