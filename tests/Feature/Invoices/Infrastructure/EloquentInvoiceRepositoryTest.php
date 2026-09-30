<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFound;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use Modules\Invoices\Infrastructure\Repositories\EloquentInvoiceRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Invoices\InvoiceIds;
use Tests\TestCase;

final class EloquentInvoiceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentInvoiceRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new EloquentInvoiceRepository;
    }

    #[Test]
    public function round_trip_save_and_load_reconstructs_domain_state(): void
    {
        $id = InvoiceIds::random();
        $invoice = Invoice::draft(
            $id,
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
            [
                new ProductLine(new ProductName('Widget'), new Quantity(2), new UnitPrice(300)),
                new ProductLine(new ProductName('Gadget'), new Quantity(5), new UnitPrice(100)),
            ],
        );

        $this->repo->save($invoice);
        $loaded = $this->repo->getById($id);

        self::assertTrue($loaded->id->equals($id));
        self::assertSame('Ada Lovelace', $loaded->customerName->value);
        self::assertSame('ada@example.com', $loaded->customerEmail->value);
        self::assertSame(StatusEnum::Draft, $loaded->status());
        self::assertCount(2, $loaded->productLines());
        self::assertSame(1100, $loaded->totalPrice());
    }

    #[Test]
    public function save_replaces_product_lines_on_subsequent_writes(): void
    {
        $id = InvoiceIds::random();
        $this->repo->save(Invoice::draft(
            $id,
            new CustomerName('Ada'),
            new CustomerEmail('ada@example.com'),
            [new ProductLine(new ProductName('Old'), new Quantity(1), new UnitPrice(50))],
        ));

        $this->repo->save(Invoice::draft(
            $id,
            new CustomerName('Ada'),
            new CustomerEmail('ada@example.com'),
            [new ProductLine(new ProductName('New'), new Quantity(3), new UnitPrice(200))],
        ));

        $loaded = $this->repo->getById($id);
        self::assertCount(1, $loaded->productLines());
        self::assertSame('New', $loaded->productLines()[0]->productName->value);
        self::assertSame(600, $loaded->totalPrice());
    }

    #[Test]
    public function persists_non_draft_status_across_reconstitution_round_trip(): void
    {
        $id = InvoiceIds::random();
        $invoice = Invoice::draft(
            $id,
            new CustomerName('Ada'),
            new CustomerEmail('ada@example.com'),
            [new ProductLine(new ProductName('W'), new Quantity(1), new UnitPrice(10))],
        );
        $invoice->send();

        $this->repo->save($invoice);
        $loaded = $this->repo->getById($id);

        self::assertSame(StatusEnum::Sending, $loaded->status());
    }

    #[Test]
    public function get_by_id_throws_when_invoice_missing(): void
    {
        $this->expectException(InvoiceNotFound::class);

        $this->repo->getById(InvoiceIds::random());
    }
}
