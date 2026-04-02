<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Application\Commands\CreateInvoiceCommand;
use Modules\Invoices\Application\UseCases\CreateInvoiceUseCase;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Invoices\Application\Support\FixedInvoiceIdGenerator;
use Tests\Unit\Invoices\Application\Support\InMemoryInvoiceRepository;

final class CreateInvoiceUseCaseTest extends TestCase
{
    public function test_creates_draft_invoice_with_empty_lines(): void
    {
        $repository = new InMemoryInvoiceRepository;
        $useCase = new CreateInvoiceUseCase(
            invoiceRepository: $repository,
            invoiceIdGenerator: new FixedInvoiceIdGenerator('invoice-100'),
        );

        $result = $useCase->execute(new CreateInvoiceCommand(
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [],
        ));

        $this->assertSame('invoice-100', $result->invoiceId);
        $this->assertSame(StatusEnum::Draft->value, $result->status);
        $this->assertSame([], $result->productLines);
        $this->assertSame(0, $result->totalPrice);
    }

    public function test_persists_invoice_and_returns_calculated_totals(): void
    {
        $repository = new InMemoryInvoiceRepository;
        $useCase = new CreateInvoiceUseCase(
            invoiceRepository: $repository,
            invoiceIdGenerator: new FixedInvoiceIdGenerator('invoice-101'),
        );

        $result = $useCase->execute(new CreateInvoiceCommand(
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [
                ['name' => 'Hat', 'quantity' => 2, 'unitPrice' => 100],
                ['name' => 'Shoes', 'quantity' => 1, 'unitPrice' => 250],
            ],
        ));

        $this->assertCount(2, $result->productLines);
        $this->assertSame(450, $result->totalPrice);
        $this->assertSame(200, $result->productLines[0]->totalUnitPrice);
        $this->assertSame(250, $result->productLines[1]->totalUnitPrice);

        $persisted = $repository->getById(new InvoiceId($result->invoiceId));
        $this->assertNotNull($persisted);
    }
}
