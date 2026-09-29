<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeMarkedSent;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSent;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceStateMachineTest extends TestCase
{
    private function draftWithLines(ProductLine ...$lines): Invoice
    {
        return Invoice::draft(
            InvoiceId::generate(),
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
            array_values($lines),
        );
    }

    private function line(int $qty = 2, int $price = 300): ProductLine
    {
        return new ProductLine(new ProductName('Widget'), new Quantity($qty), new UnitPrice($price));
    }

    #[Test]
    public function draft_factory_forces_draft_status(): void
    {
        $invoice = $this->draftWithLines($this->line());

        self::assertSame(StatusEnum::Draft, $invoice->status());
    }

    #[Test]
    public function it_transitions_draft_to_sending(): void
    {
        $invoice = $this->draftWithLines($this->line());

        $invoice->send();

        self::assertSame(StatusEnum::Sending, $invoice->status());
    }

    #[Test]
    public function it_rejects_send_when_not_draft(): void
    {
        $invoice = $this->draftWithLines($this->line());
        $invoice->send();

        $this->expectException(InvoiceCannotBeSent::class);
        $invoice->send();
    }

    #[Test]
    public function it_rejects_send_when_no_product_lines(): void
    {
        $invoice = $this->draftWithLines();

        $this->expectException(InvoiceCannotBeSent::class);
        $invoice->send();
    }

    #[Test]
    public function it_rejects_send_when_a_line_is_invalid(): void
    {
        // VOs already guard against non-positive values; simulate a bypassed line
        // to exercise the domain's redundant defensive check.
        $reflection = new \ReflectionClass(Quantity::class);
        $quantity = $reflection->newInstanceWithoutConstructor();
        $quantityProp = $reflection->getProperty('value');
        $quantityProp->setValue($quantity, 0);

        $line = new ProductLine(new ProductName('Widget'), $quantity, new UnitPrice(100));
        $invoice = $this->draftWithLines($line);

        $this->expectException(InvoiceCannotBeSent::class);
        $invoice->send();
    }

    #[Test]
    public function it_marks_sent_to_client_only_from_sending(): void
    {
        $invoice = $this->draftWithLines($this->line());
        $invoice->send();

        $invoice->markSentToClient();

        self::assertSame(StatusEnum::SentToClient, $invoice->status());
    }

    #[Test]
    public function it_rejects_mark_sent_to_client_from_draft(): void
    {
        $invoice = $this->draftWithLines($this->line());

        $this->expectException(InvoiceCannotBeMarkedSent::class);
        $invoice->markSentToClient();
    }

    #[Test]
    public function it_rejects_mark_sent_to_client_from_sent(): void
    {
        $invoice = $this->draftWithLines($this->line());
        $invoice->send();
        $invoice->markSentToClient();

        $this->expectException(InvoiceCannotBeMarkedSent::class);
        $invoice->markSentToClient();
    }
}

