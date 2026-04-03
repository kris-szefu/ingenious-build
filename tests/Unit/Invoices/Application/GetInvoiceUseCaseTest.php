<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Application\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Application\Queries\GetInvoiceQuery;
use Modules\Invoices\Application\UseCases\GetInvoiceUseCase;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Invoices\Application\Support\InMemoryInvoiceRepository;

final class GetInvoiceUseCaseTest extends TestCase
{
    public function test_returns_invoice_view_data_when_found(): void
    {
        $repository = new InMemoryInvoiceRepository;
        $invoice = Invoice::createDraft(
            id: new InvoiceId('invoice-200'),
            customerName: 'Jane Doe',
            customerEmail: 'jane@example.com',
            productLines: [new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100)],
        );
        $repository->save($invoice);

        $useCase = new GetInvoiceUseCase(invoiceRepository: $repository);
        $result = $useCase->execute(new GetInvoiceQuery(invoiceId: 'invoice-200'));

        $this->assertSame('invoice-200', $result->invoiceId);
        $this->assertSame(StatusEnum::Draft->value, $result->status);
        $this->assertSame('Jane Doe', $result->customerName);
        $this->assertSame(200, $result->totalPrice);
    }

    public function test_throws_when_invoice_is_missing(): void
    {
        $useCase = new GetInvoiceUseCase(invoiceRepository: new InMemoryInvoiceRepository);

        $this->expectException(InvoiceNotFoundException::class);
        $useCase->execute(new GetInvoiceQuery(invoiceId: 'invoice-missing'));
    }
}
