<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateException;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateTransitionException;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSentException;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InvoiceTest extends TestCase
{
    public function test_initializes_in_draft_status(): void
    {
        $invoice = $this->newInvoice();

        $this->assertSame(StatusEnum::Draft, $invoice->status());
    }

    public function test_allows_empty_lines_on_creation(): void
    {
        $invoice = $this->newInvoice();

        $this->assertSame([], $invoice->productLines());
        $this->assertSame(0, $invoice->totalPrice());
    }

    public function test_cannot_be_sent_when_has_no_lines(): void
    {
        $invoice = $this->newInvoice();

        $this->assertFalse($invoice->canBeSent());
    }

    public function test_mark_as_sending_works_from_draft_with_valid_lines(): void
    {
        $invoice = $this->newInvoice([
            new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100),
        ]);

        $invoice->markAsSending();

        $this->assertSame(StatusEnum::Sending, $invoice->status());
    }

    public function test_mark_as_sending_fails_when_has_no_lines(): void
    {
        $invoice = $this->newInvoice();

        $this->expectException(InvoiceCannotBeSentException::class);
        $invoice->markAsSending();
    }

    public function test_rehydrate_allows_sending_with_lines(): void
    {
        $invoice = Invoice::rehydrate(
            id: new InvoiceId('invoice-sending'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100)],
            status: StatusEnum::Sending,
        );

        $this->assertSame(StatusEnum::Sending, $invoice->status());
    }

    public function test_rehydrate_allows_draft_with_no_lines(): void
    {
        $invoice = Invoice::rehydrate(
            id: new InvoiceId('invoice-draft'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [],
            status: StatusEnum::Draft,
        );

        $this->assertSame(StatusEnum::Draft, $invoice->status());
        $this->assertSame([], $invoice->productLines());
        $this->assertSame(0, $invoice->totalPrice());
        $this->assertFalse($invoice->canBeSent());
    }

    #[DataProvider('invalidRehydrateStateProvider')]
    public function test_rehydrate_rejects_non_draft_status_with_no_lines(StatusEnum $status): void
    {
        $this->expectException(InvalidInvoiceStateException::class);

        Invoice::rehydrate(
            id: new InvoiceId('invoice-invalid'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [],
            status: $status,
        );
    }

    #[DataProvider('invalidTransitionProvider')]
    public function test_invalid_transitions_throw_exceptions(string $transition): void
    {
        $this->expectException(InvalidInvoiceStateTransitionException::class);

        match ($transition) {
            'draft_to_sent_to_client' => $this->newInvoice()->markAsSentToClient(),
            'sending_to_sending' => $this->newSendingInvoice()->markAsSending(),
            'sent_to_client_to_sending' => $this->newSentToClientInvoice()->markAsSending(),
            'sent_to_client_to_sent_to_client' => $this->newSentToClientInvoice()->markAsSentToClient(),
        };
    }

    public function test_mark_as_sent_to_client_works_only_from_sending(): void
    {
        $invoice = $this->newInvoice([
            new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100),
        ]);
        $invoice->markAsSending();

        $invoice->markAsSentToClient();

        $this->assertSame(StatusEnum::SentToClient, $invoice->status());
    }

    public function test_total_price_sums_product_line_totals(): void
    {
        $invoice = $this->newInvoice([
            new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100),
            new InvoiceProductLine(name: 'Shoes', quantity: 1, unitPrice: 250),
        ]);

        $this->assertSame(450, $invoice->totalPrice());
    }

    public static function invalidTransitionProvider(): array
    {
        return [
            ['draft_to_sent_to_client'],
            ['sending_to_sending'],
            ['sent_to_client_to_sending'],
            ['sent_to_client_to_sent_to_client'],
        ];
    }

    public static function invalidRehydrateStateProvider(): array
    {
        return [
            [StatusEnum::Sending],
            [StatusEnum::SentToClient],
        ];
    }

    /**
     * @param  array<int, InvoiceProductLine>  $productLines
     */
    private function newInvoice(array $productLines = []): Invoice
    {
        return Invoice::createDraft(
            id: new InvoiceId('invoice-1'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: $productLines,
        );
    }

    private function newSendingInvoice(): Invoice
    {
        $invoice = $this->newInvoice([
            new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100),
        ]);
        $invoice->markAsSending();

        return $invoice;
    }

    private function newSentToClientInvoice(): Invoice
    {
        $invoice = $this->newSendingInvoice();
        $invoice->markAsSentToClient();

        return $invoice;
    }
}
