<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EloquentInvoiceRepositoryTest extends TestCase
{
    private InvoiceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(InvoiceRepository::class);
    }

    #[Test]
    public function saved_invoice_is_returned_unchanged(): void
    {
        $invoice = Invoice::create(
            'Acme Corp',
            CustomerEmail::fromString('billing@acme.test'),
            [
                new ProductLine('Desk', 2, 15000),
                new ProductLine('Chair', 4, 7500),
            ],
        );

        $this->repository->save($invoice);

        $found = $this->repository->find($invoice->id);

        self::assertInstanceOf(Invoice::class, $found);
        self::assertSame($invoice->id->value, $found->id->value);
        self::assertSame('Acme Corp', $found->customerName);
        self::assertSame('billing@acme.test', $found->customerEmail->value);
        self::assertSame(StatusEnum::Draft, $found->status);
        self::assertEquals($invoice->productLines, $found->productLines);
        self::assertSame(60000, $found->totalPrice());
    }

    #[Test]
    public function saved_status_survives_the_round_trip(): void
    {
        $invoice = Invoice::reconstitute(
            InvoiceId::generate(),
            'Acme Corp',
            CustomerEmail::fromString('billing@acme.test'),
            StatusEnum::Sending,
            [new ProductLine('Desk', 1, 100)],
        );

        $this->repository->save($invoice);

        $found = $this->repository->find($invoice->id);

        self::assertInstanceOf(Invoice::class, $found);
        self::assertSame(StatusEnum::Sending, $found->status);
    }

    #[Test]
    public function saving_twice_does_not_duplicate_product_lines(): void
    {
        $invoice = Invoice::create(
            'Acme Corp',
            CustomerEmail::fromString('billing@acme.test'),
            [new ProductLine('Desk', 2, 15000)],
        );

        $this->repository->save($invoice);
        $this->repository->save($invoice);

        $found = $this->repository->find($invoice->id);

        self::assertInstanceOf(Invoice::class, $found);
        self::assertCount(1, $found->productLines);
        $this->assertDatabaseCount('invoice_product_lines', 1);
    }

    #[Test]
    public function unknown_id_returns_null(): void
    {
        self::assertNull($this->repository->find(InvoiceId::generate()));
    }
}
