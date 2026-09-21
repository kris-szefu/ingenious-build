<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Application\UseCases\CreateInvoice;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidCustomerEmail;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Invoices\Application\Support\InMemoryInvoiceRepository;

final class CreateInvoiceTest extends TestCase
{
    private InMemoryInvoiceRepository $repository;

    private CreateInvoice $createInvoice;

    protected function setUp(): void
    {
        $this->repository = new InMemoryInvoiceRepository;
        $this->createInvoice = new CreateInvoice($this->repository);
    }

    #[Test]
    public function it_creates_a_draft_for_the_given_customer(): void
    {
        $invoice = $this->createInvoice->handle('Acme Corp', 'billing@acme.test');

        self::assertSame(StatusEnum::Draft, $invoice->status);
        self::assertSame('Acme Corp', $invoice->customerName);
        self::assertSame('billing@acme.test', $invoice->customerEmail->value);
    }

    #[Test]
    public function it_puts_the_given_product_lines_on_the_invoice(): void
    {
        $invoice = $this->createInvoice->handle('Acme Corp', 'billing@acme.test', [
            ['name' => 'Desk', 'quantity' => 2, 'unitPrice' => 15000],
            ['name' => 'Chair', 'quantity' => 4, 'unitPrice' => 7500],
        ]);

        self::assertEquals(
            [new ProductLine('Desk', 2, 15000), new ProductLine('Chair', 4, 7500)],
            $invoice->productLines,
        );
        self::assertSame(60000, $invoice->totalPrice());
    }

    #[Test]
    public function it_saves_the_invoice(): void
    {
        $invoice = $this->createInvoice->handle('Acme Corp', 'billing@acme.test');

        self::assertInstanceOf(Invoice::class, $this->repository->find($invoice->id));
    }

    #[Test]
    public function it_saves_nothing_when_the_email_is_invalid(): void
    {
        try {
            $this->createInvoice->handle('Acme Corp', 'not-an-email');
            self::fail('Expected InvalidCustomerEmail.');
        } catch (InvalidCustomerEmail) {
        }

        self::assertTrue($this->repository->isEmpty());
    }
}
