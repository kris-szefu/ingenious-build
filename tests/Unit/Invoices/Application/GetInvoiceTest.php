<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Application\Exceptions\InvoiceNotFound;
use Modules\Invoices\Application\UseCases\GetInvoice;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Invoices\Application\Support\InMemoryInvoiceRepository;

final class GetInvoiceTest extends TestCase
{
    #[Test]
    public function it_returns_a_saved_invoice(): void
    {
        $repository = new InMemoryInvoiceRepository;
        $invoice = Invoice::create('Acme Corp', CustomerEmail::fromString('billing@acme.test'));
        $repository->save($invoice);

        $found = new GetInvoice($repository)->handle($invoice->id->value);

        self::assertSame($invoice->id->value, $found->id->value);
    }

    #[Test]
    public function it_throws_when_the_invoice_does_not_exist(): void
    {
        $id = InvoiceId::generate()->value;

        $this->expectException(InvoiceNotFound::class);
        $this->expectExceptionMessage($id);

        new GetInvoice(new InMemoryInvoiceRepository)->handle($id);
    }
}
