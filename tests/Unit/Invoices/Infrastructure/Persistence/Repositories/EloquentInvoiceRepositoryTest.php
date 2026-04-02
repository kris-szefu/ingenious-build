<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Infrastructure\Persistence\Repositories;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Infrastructure\Persistence\Repositories\EloquentInvoiceRepository;
use Tests\TestCase;

final class EloquentInvoiceRepositoryTest extends TestCase
{
    public function test_save_and_get_by_id_roundtrip(): void
    {
        $repository = new EloquentInvoiceRepository;
        $invoiceId = new InvoiceId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');

        $invoice = Invoice::createDraft(
            id: $invoiceId,
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [
                new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100),
                new InvoiceProductLine(name: 'Shoes', quantity: 1, unitPrice: 250),
            ],
        );
        $invoice->markAsSending();

        $repository->save($invoice);
        $loaded = $repository->getById($invoiceId);

        $this->assertNotNull($loaded);
        $this->assertSame(StatusEnum::Sending, $loaded->status());
        $this->assertSame('John Doe', $loaded->customerName());
        $this->assertCount(2, $loaded->productLines());
        $this->assertSame(450, $loaded->totalPrice());
    }

    public function test_get_by_id_rehydrates_valid_persisted_state(): void
    {
        $repository = new EloquentInvoiceRepository;
        $invoiceId = new InvoiceId('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb');

        $invoice = Invoice::rehydrate(
            id: $invoiceId,
            customerName: 'Jane Doe',
            customerEmail: 'jane@example.com',
            productLines: [new InvoiceProductLine(name: 'Bag', quantity: 1, unitPrice: 300)],
            status: StatusEnum::SentToClient,
        );
        $repository->save($invoice);

        $loaded = $repository->getById($invoiceId);

        $this->assertNotNull($loaded);
        $this->assertSame(StatusEnum::SentToClient, $loaded->status());
        $this->assertSame(300, $loaded->totalPrice());
    }

    public function test_get_by_id_returns_null_when_missing(): void
    {
        $repository = new EloquentInvoiceRepository;

        $this->assertNull($repository->getById(new InvoiceId('cccccccc-cccc-cccc-cccc-cccccccccccc')));
    }

    public function test_save_replaces_existing_product_lines(): void
    {
        $repository = new EloquentInvoiceRepository;
        $invoiceId = new InvoiceId('dddddddd-dddd-dddd-dddd-dddddddddddd');

        $invoice = Invoice::createDraft(
            id: $invoiceId,
            customerName: 'Alice',
            customerEmail: 'alice@example.com',
            productLines: [new InvoiceProductLine(name: 'Hat', quantity: 1, unitPrice: 100)],
        );
        $repository->save($invoice);

        $updatedInvoice = Invoice::createDraft(
            id: $invoiceId,
            customerName: 'Alice',
            customerEmail: 'alice@example.com',
            productLines: [
                new InvoiceProductLine(name: 'Shoes', quantity: 2, unitPrice: 150),
                new InvoiceProductLine(name: 'Scarf', quantity: 1, unitPrice: 50),
            ],
        );
        $repository->save($updatedInvoice);

        $loaded = $repository->getById($invoiceId);
        $this->assertNotNull($loaded);
        $this->assertCount(2, $loaded->productLines());
        $this->assertSame(350, $loaded->totalPrice());

        $this->assertDatabaseCount('invoice_product_lines', 2);
    }
}
