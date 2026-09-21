<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Application\Exceptions\InvoiceNotFound;
use Modules\Invoices\Application\UseCases\MarkInvoiceAsSentToClient;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidStatusTransition;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Invoices\Application\Support\InMemoryInvoiceRepository;

final class MarkInvoiceAsSentToClientTest extends TestCase
{
    private InMemoryInvoiceRepository $repository;

    private MarkInvoiceAsSentToClient $markAsSentToClient;

    protected function setUp(): void
    {
        $this->repository = new InMemoryInvoiceRepository;
        $this->markAsSentToClient = new MarkInvoiceAsSentToClient($this->repository);
    }

    #[Test]
    public function it_saves_a_sending_invoice_as_sent_to_client(): void
    {
        $invoice = $this->stored(StatusEnum::Sending);

        $this->markAsSentToClient->handle($invoice->id->value);

        $this->assertSame(StatusEnum::SentToClient, $this->repository->find($invoice->id)?->status);
    }

    #[Test]
    public function it_throws_for_an_unknown_invoice(): void
    {
        $this->expectException(InvoiceNotFound::class);

        $this->markAsSentToClient->handle(InvoiceId::generate()->value);
    }

    #[Test]
    public function it_saves_nothing_when_the_invoice_is_not_sending(): void
    {
        $invoice = $this->stored(StatusEnum::Draft);

        try {
            $this->markAsSentToClient->handle($invoice->id->value);
            $this->fail('Expected InvalidStatusTransition.');
        } catch (InvalidStatusTransition) {
        }

        $this->assertSame(StatusEnum::Draft, $this->repository->find($invoice->id)?->status);
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
