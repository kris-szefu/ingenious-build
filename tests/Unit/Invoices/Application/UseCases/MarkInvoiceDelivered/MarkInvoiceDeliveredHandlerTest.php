<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\UseCases\MarkInvoiceDelivered;

use Modules\Invoices\Application\UseCases\MarkInvoiceDelivered\MarkInvoiceDeliveredCommand;
use Modules\Invoices\Application\UseCases\MarkInvoiceDelivered\MarkInvoiceDeliveredHandler;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Events\InvoiceSentToClient;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tests\Support\Invoices\InMemoryInvoiceRepository;
use Tests\Support\Invoices\InvoiceIds;
use Tests\Support\Invoices\RecordingEventDispatcher;

final class MarkInvoiceDeliveredHandlerTest extends TestCase
{
    private InMemoryInvoiceRepository $repo;

    private InvoiceId $id;

    private RecordingEventDispatcher $events;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InMemoryInvoiceRepository;
        $this->id = InvoiceIds::random();
        $this->events = new RecordingEventDispatcher;
    }

    #[Test]
    public function it_transitions_a_sending_invoice_to_sent_to_client(): void
    {
        $this->repo->save($this->sendingInvoice());

        $this->handler()->handle(new MarkInvoiceDeliveredCommand($this->id));

        self::assertSame(
            StatusEnum::SentToClient,
            $this->repo->getById($this->id)->status(),
        );
    }

    #[Test]
    public function it_dispatches_invoice_sent_to_client_after_persist(): void
    {
        $this->repo->save($this->sendingInvoice());

        $this->handler()->handle(new MarkInvoiceDeliveredCommand($this->id));

        self::assertCount(1, $this->events->dispatched);
        self::assertInstanceOf(InvoiceSentToClient::class, $this->events->dispatched[0]);
        self::assertSame($this->id->value, $this->events->dispatched[0]->invoiceId->value);
    }

    #[Test]
    public function it_ignores_events_for_unknown_invoices(): void
    {
        $logger = new SpyLogger;

        (new MarkInvoiceDeliveredHandler($this->repo, $logger, $this->events))
            ->handle(new MarkInvoiceDeliveredCommand($this->id));

        self::assertCount(1, $logger->warnings);
        self::assertStringContainsString('not found', $logger->warnings[0]['message']);
        self::assertSame($this->id->value, $logger->warnings[0]['context']['invoice_id']);
        self::assertSame([], $this->events->dispatched, 'No event dispatched when invoice is unknown.');
    }

    #[Test]
    public function it_ignores_events_for_invoices_not_in_sending_state(): void
    {
        // Draft invoice — has never been sent.
        $this->repo->save($this->draftInvoiceWithLines());

        $logger = new SpyLogger;

        (new MarkInvoiceDeliveredHandler($this->repo, $logger, $this->events))
            ->handle(new MarkInvoiceDeliveredCommand($this->id));

        self::assertSame(
            StatusEnum::Draft,
            $this->repo->getById($this->id)->status(),
            'Status must remain draft when a delivery event arrives out of order.',
        );
        self::assertCount(1, $logger->warnings);
        self::assertStringContainsString('not in sending', $logger->warnings[0]['message']);
        self::assertSame([], $this->events->dispatched);
    }

    #[Test]
    public function it_ignores_duplicate_delivery_events(): void
    {
        $invoice = $this->sendingInvoice();
        $invoice->markSentToClient();
        $invoice->pullRecordedEvents(); // simulate post-commit drain
        $this->repo->save($invoice);

        $logger = new SpyLogger;

        (new MarkInvoiceDeliveredHandler($this->repo, $logger, $this->events))
            ->handle(new MarkInvoiceDeliveredCommand($this->id));

        self::assertSame(
            StatusEnum::SentToClient,
            $this->repo->getById($this->id)->status(),
        );
        self::assertCount(1, $logger->warnings);
        self::assertSame([], $this->events->dispatched);
    }

    private function handler(): MarkInvoiceDeliveredHandler
    {
        return new MarkInvoiceDeliveredHandler($this->repo, new NullLogger, $this->events);
    }

    /**
     * Sending fixture with the `InvoiceMarkedSending` event already drained,
     * as would have happened after the prior send-invoice transaction
     * committed in production.
     */
    private function sendingInvoice(): Invoice
    {
        $invoice = $this->draftInvoiceWithLines();
        $invoice->send();
        $invoice->pullRecordedEvents();

        return $invoice;
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
