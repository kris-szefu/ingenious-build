<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Ports;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFound;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Invoices\InMemoryInvoiceRepository;

final class InMemoryInvoiceRepositoryTest extends TestCase
{
    #[Test]
    public function it_saves_and_fetches_by_id(): void
    {
        $repo = new InMemoryInvoiceRepository();
        $invoice = Invoice::draft(
            InvoiceId::generate(),
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
        );

        $repo->save($invoice);

        self::assertSame($invoice, $repo->getById($invoice->id));
    }

    #[Test]
    public function it_overwrites_on_save_with_same_id(): void
    {
        $repo = new InMemoryInvoiceRepository();
        $id = InvoiceId::generate();

        $first = Invoice::draft($id, new CustomerName('Ada'), new CustomerEmail('ada@example.com'));
        $second = Invoice::draft($id, new CustomerName('Grace'), new CustomerEmail('grace@example.com'));

        $repo->save($first);
        $repo->save($second);

        self::assertSame($second, $repo->getById($id));
    }

    #[Test]
    public function it_throws_when_invoice_is_missing(): void
    {
        $repo = new InMemoryInvoiceRepository();

        $this->expectException(InvoiceNotFound::class);
        $repo->getById(InvoiceId::generate());
    }
}

