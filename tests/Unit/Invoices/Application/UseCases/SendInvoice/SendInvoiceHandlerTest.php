<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\UseCases\SendInvoice;

use Modules\Invoices\Application\UseCases\SendInvoice\SendInvoiceCommand;
use Modules\Invoices\Application\UseCases\SendInvoice\SendInvoiceHandler;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Events\InvoiceMarkedSending;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSent;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\Invoices\InMemoryInvoiceRepository;
use Tests\Support\Invoices\InvoiceIds;
use Tests\Support\Invoices\RecordingCustomerNotifier;
use Tests\Support\Invoices\RecordingEventDispatcher;

final class SendInvoiceHandlerTest extends TestCase
{
    private InMemoryInvoiceRepository $repo;

    private InvoiceId $id;

    private RecordingCustomerNotifier $notifier;

    private RecordingEventDispatcher $events;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InMemoryInvoiceRepository;
        $this->id = InvoiceIds::random();
        $this->notifier = new RecordingCustomerNotifier;
        $this->events = new RecordingEventDispatcher;
    }

    #[Test]
    public function it_notifies_customer_and_transitions_invoice_to_sending(): void
    {
        $this->repo->save($this->draftInvoiceWithLines());

        $this->handler()->handle(new SendInvoiceCommand($this->id));

        self::assertCount(1, $this->notifier->calls);
        self::assertSame($this->id->value, $this->notifier->calls[0]['invoiceId']);
        self::assertSame('ada@example.com', $this->notifier->calls[0]['customerEmail']);
        self::assertSame('Ada Lovelace', $this->notifier->calls[0]['customerName']);
        self::assertSame(StatusEnum::Sending, $this->repo->getById($this->id)->status());
    }

    #[Test]
    public function it_dispatches_invoice_marked_sending_after_commit(): void
    {
        $this->repo->save($this->draftInvoiceWithLines());

        $this->handler()->handle(new SendInvoiceCommand($this->id));

        self::assertCount(1, $this->events->dispatched);
        $event = $this->events->dispatched[0];
        self::assertInstanceOf(InvoiceMarkedSending::class, $event);
        self::assertSame($this->id->value, $event->invoiceId->value);
    }

    #[Test]
    public function it_rejects_when_invoice_is_not_in_draft(): void
    {
        $invoice = $this->draftInvoiceWithLines();
        $invoice->send(); // draft -> sending
        $this->repo->save($invoice);

        $this->expectException(InvoiceCannotBeSent::class);

        try {
            $this->handler()->handle(new SendInvoiceCommand($this->id));
        } finally {
            self::assertSame([], $this->notifier->calls, 'Notifier must not fire when guard fails.');
            self::assertSame([], $this->events->dispatched, 'No domain event on guard failure.');
            self::assertSame(StatusEnum::Sending, $this->repo->getById($this->id)->status());
        }
    }

    #[Test]
    public function it_rejects_when_invoice_has_no_product_lines(): void
    {
        $this->repo->save(Invoice::draft(
            $this->id,
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
        ));

        $this->expectException(InvoiceCannotBeSent::class);

        try {
            $this->handler()->handle(new SendInvoiceCommand($this->id));
        } finally {
            self::assertSame([], $this->notifier->calls);
            self::assertSame([], $this->events->dispatched);
            self::assertSame(StatusEnum::Draft, $this->repo->getById($this->id)->status());
        }
    }

    #[Test]
    public function it_leaves_invoice_in_draft_when_notifier_throws(): void
    {
        $this->repo->save($this->draftInvoiceWithLines());
        $this->notifier->throwOnNextCall(new RuntimeException('smtp down'));

        try {
            $this->handler()->handle(new SendInvoiceCommand($this->id));
            self::fail('Expected RuntimeException to bubble.');
        } catch (RuntimeException) {
            // expected
        }

        self::assertCount(1, $this->notifier->calls);
        self::assertSame([], $this->events->dispatched, 'No event dispatched when notifier throws.');
        self::assertSame(StatusEnum::Draft, $this->repo->getById($this->id)->status());
    }

    private function handler(): SendInvoiceHandler
    {
        return new SendInvoiceHandler($this->repo, $this->notifier, $this->events);
    }

    private function draftInvoiceWithLines(): Invoice
    {
        return Invoice::draft(
            $this->id,
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
            [
                new ProductLine(
                    new ProductName('Widget'),
                    new Quantity(2),
                    new UnitPrice(300),
                ),
            ],
        );
    }
}
