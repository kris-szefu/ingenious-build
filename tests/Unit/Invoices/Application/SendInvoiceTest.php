<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Application\Exceptions\InvoiceNotFound;
use Modules\Invoices\Application\Ports\InvoiceNotifier;
use Modules\Invoices\Application\UseCases\SendInvoice;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSent;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Unit\Invoices\Application\Support\InMemoryInvoiceRepository;

final class SendInvoiceTest extends TestCase
{
    private InMemoryInvoiceRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryInvoiceRepository;
    }

    #[Test]
    public function it_notifies_the_customer_and_saves_the_invoice_as_sending(): void
    {
        $invoice = $this->storedDraft([new ProductLine('Desk', 2, 15000)]);

        $notifier = $this->createMock(InvoiceNotifier::class);
        $notifier->expects($this->once())
            ->method('notifyCustomer')
            ->with($this->callback(
                static fn (Invoice $notified): bool => $notified->id->value === $invoice->id->value
                    && $notified->status === StatusEnum::Sending,
            ));

        new SendInvoice($this->repository, $notifier)->handle($invoice->id->value);

        $this->assertSame(StatusEnum::Sending, $this->repository->find($invoice->id)?->status);
    }

    #[Test]
    public function it_throws_for_an_unknown_invoice_without_notifying_anyone(): void
    {
        $notifier = $this->createMock(InvoiceNotifier::class);
        $notifier->expects($this->never())->method('notifyCustomer');

        $this->expectException(InvoiceNotFound::class);

        new SendInvoice($this->repository, $notifier)->handle(InvoiceId::generate()->value);
    }

    #[Test]
    public function it_neither_notifies_nor_saves_when_a_domain_rule_fails(): void
    {
        $invoice = $this->storedDraft([]);

        $notifier = $this->createMock(InvoiceNotifier::class);
        $notifier->expects($this->never())->method('notifyCustomer');

        try {
            new SendInvoice($this->repository, $notifier)->handle($invoice->id->value);
            $this->fail('Expected InvoiceCannotBeSent.');
        } catch (InvoiceCannotBeSent) {
        }

        $this->assertSame(StatusEnum::Draft, $this->repository->find($invoice->id)?->status);
    }

    #[Test]
    public function it_saves_nothing_when_the_notification_fails(): void
    {
        $invoice = $this->storedDraft([new ProductLine('Desk', 2, 15000)]);

        $notifier = $this->createStub(InvoiceNotifier::class);
        $notifier->method('notifyCustomer')->willThrowException(new RuntimeException('Mail server down'));

        try {
            new SendInvoice($this->repository, $notifier)->handle($invoice->id->value);
            $this->fail('Expected RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertSame('Mail server down', $e->getMessage());
        }

        $this->assertSame(StatusEnum::Draft, $this->repository->find($invoice->id)?->status);
    }

    /** @param list<ProductLine> $productLines */
    private function storedDraft(array $productLines): Invoice
    {
        $invoice = Invoice::create('Acme Corp', CustomerEmail::fromString('billing@acme.test'), $productLines);
        $this->repository->save($invoice);

        return $invoice;
    }
}
