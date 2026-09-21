<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidStatusTransition;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSent;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[Test]
    public function sending_a_draft_moves_it_to_sending(): void
    {
        $invoice = self::invoice(StatusEnum::Draft, [
            new ProductLine('Desk', 2, 15000),
            new ProductLine('Chair', 4, 7500),
        ]);

        $invoice->send();

        $this->assertSame(StatusEnum::Sending, $invoice->status);
    }

    #[Test]
    #[DataProvider('statusesOtherThanDraft')]
    public function only_a_draft_can_be_sent(StatusEnum $status): void
    {
        $invoice = self::invoice($status, [new ProductLine('Desk', 2, 15000)]);

        try {
            $invoice->send();
            $this->fail('Expected InvalidStatusTransition.');
        } catch (InvalidStatusTransition) {
        }

        $this->assertSame($status, $invoice->status);
    }

    #[Test]
    public function an_invoice_without_product_lines_cannot_be_sent(): void
    {
        $invoice = self::invoice(StatusEnum::Draft, []);

        try {
            $invoice->send();
            $this->fail('Expected InvoiceCannotBeSent.');
        } catch (InvoiceCannotBeSent) {
        }

        $this->assertSame(StatusEnum::Draft, $invoice->status);
    }

    #[Test]
    #[DataProvider('linesWithNonPositiveAmounts')]
    public function an_invoice_with_a_non_positive_amount_cannot_be_sent(ProductLine ...$productLines): void
    {
        $invoice = self::invoice(StatusEnum::Draft, array_values($productLines));

        try {
            $invoice->send();
            $this->fail('Expected InvoiceCannotBeSent.');
        } catch (InvoiceCannotBeSent) {
        }

        $this->assertSame(StatusEnum::Draft, $invoice->status);
    }

    /** @return array<string, list<ProductLine>> */
    public static function linesWithNonPositiveAmounts(): array
    {
        return [
            'zero quantity' => [new ProductLine('Desk', 0, 15000)],
            'zero unit price' => [new ProductLine('Desk', 2, 0)],
            'negative quantity' => [new ProductLine('Desk', -1, 15000)],
            'valid line followed by an invalid one' => [
                new ProductLine('Desk', 2, 15000),
                new ProductLine('Chair', 0, 7500),
            ],
        ];
    }

    #[Test]
    public function the_status_rule_is_checked_before_the_product_lines(): void
    {
        $invoice = self::invoice(StatusEnum::Sending, []);

        $this->expectException(InvalidStatusTransition::class);

        $invoice->send();
    }

    #[Test]
    public function a_sending_invoice_can_be_marked_as_sent_to_client(): void
    {
        $invoice = self::invoice(StatusEnum::Sending, [new ProductLine('Desk', 2, 15000)]);

        $invoice->markAsSentToClient();

        $this->assertSame(StatusEnum::SentToClient, $invoice->status);
    }

    #[Test]
    #[DataProvider('statusesOtherThanSending')]
    public function only_a_sending_invoice_can_be_marked_as_sent_to_client(StatusEnum $status): void
    {
        $invoice = self::invoice($status, [new ProductLine('Desk', 2, 15000)]);

        try {
            $invoice->markAsSentToClient();
            $this->fail('Expected InvalidStatusTransition.');
        } catch (InvalidStatusTransition) {
        }

        $this->assertSame($status, $invoice->status);
    }

    /** @return array<string, array{StatusEnum}> */
    public static function statusesOtherThanSending(): array
    {
        return [
            'draft' => [StatusEnum::Draft],
            'sent to client' => [StatusEnum::SentToClient],
        ];
    }

    /** @return array<string, array{StatusEnum}> */
    public static function statusesOtherThanDraft(): array
    {
        return [
            'sending' => [StatusEnum::Sending],
            'sent to client' => [StatusEnum::SentToClient],
        ];
    }

    /** @param list<ProductLine> $productLines */
    private static function invoice(StatusEnum $status, array $productLines): Invoice
    {
        return Invoice::reconstitute(
            InvoiceId::generate(),
            'Acme Corp',
            CustomerEmail::fromString('billing@acme.test'),
            $status,
            $productLines,
        );
    }
}
