<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Presentation;

use Modules\Invoices\Application\UseCases\MarkInvoiceAsSentToClient;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use Modules\Invoices\Presentation\Listeners\MarkInvoiceAsSentToClientListener;
use Modules\Notifications\Api\Events\WebhookDeliveredEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Tests\Unit\Invoices\Application\Support\InMemoryInvoiceRepository;

final class MarkInvoiceAsSentToClientListenerTest extends TestCase
{
    private InMemoryInvoiceRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryInvoiceRepository;
    }

    #[Test]
    public function it_marks_a_sending_invoice_as_sent_to_client_without_logging(): void
    {
        $invoice = $this->stored(StatusEnum::Sending);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('debug');
        $logger->expects($this->never())->method('warning');

        $this->listener($logger)->handle($this->eventFor($invoice->id->value));

        $this->assertSame(StatusEnum::SentToClient, $this->repository->find($invoice->id)?->status);
    }

    #[Test]
    public function it_logs_a_resource_that_is_not_an_invoice_at_debug_level(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('debug');
        $logger->expects($this->never())->method('warning');

        $this->listener($logger)->handle($this->eventFor(InvoiceId::generate()->value));
    }

    #[Test]
    public function it_logs_a_webhook_for_an_invoice_that_is_not_sending_as_a_warning(): void
    {
        $invoice = $this->stored(StatusEnum::Draft);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $this->listener($logger)->handle($this->eventFor($invoice->id->value));

        $this->assertSame(StatusEnum::Draft, $this->repository->find($invoice->id)?->status);
    }

    private function listener(LoggerInterface $logger): MarkInvoiceAsSentToClientListener
    {
        return new MarkInvoiceAsSentToClientListener(new MarkInvoiceAsSentToClient($this->repository), $logger);
    }

    private function eventFor(string $resourceId): WebhookDeliveredEvent
    {
        return new WebhookDeliveredEvent(Uuid::fromString($resourceId));
    }

    private function stored(StatusEnum $status): Invoice
    {
        $invoice = Invoice::reconstitute(
            InvoiceId::generate(),
            'Acme Corp',
            CustomerEmail::fromString('billing@acme.test'),
            $status,
            [new ProductLine('Desk', 2, 15000)],
        );
        $this->repository->save($invoice);

        return $invoice;
    }
}
