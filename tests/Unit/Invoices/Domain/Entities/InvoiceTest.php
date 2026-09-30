<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Events\InvoiceMarkedSending;
use Modules\Invoices\Domain\Events\InvoiceSentToClient;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
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

    #[Test]
    public function send_records_invoice_marked_sending_event(): void
    {
        $id = InvoiceIds::random();
        $invoice = Invoice::draft(
            $id,
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
            [new ProductLine(new ProductName('Widget'), new Quantity(2), new UnitPrice(300))],
        );

        $invoice->send();

        $events = $invoice->pullRecordedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(InvoiceMarkedSending::class, $events[0]);
        self::assertSame($id->value, $events[0]->invoiceId->value);
    }

    #[Test]
    public function mark_sent_to_client_records_invoice_sent_to_client_event(): void
    {
        $id = InvoiceIds::random();
        $invoice = Invoice::draft(
            $id,
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
            [new ProductLine(new ProductName('Widget'), new Quantity(2), new UnitPrice(300))],
        );
        $invoice->send();
        $invoice->pullRecordedEvents(); // discard the sending event

        $invoice->markSentToClient();

        $events = $invoice->pullRecordedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(InvoiceSentToClient::class, $events[0]);
        self::assertSame($id->value, $events[0]->invoiceId->value);
    }

    #[Test]
    public function pull_recorded_events_clears_the_buffer(): void
    {
        $invoice = Invoice::draft(
            InvoiceIds::random(),
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
            [new ProductLine(new ProductName('Widget'), new Quantity(2), new UnitPrice(300))],
        );
        $invoice->send();

        self::assertCount(1, $invoice->pullRecordedEvents());
        self::assertSame([], $invoice->pullRecordedEvents());
    }
}
