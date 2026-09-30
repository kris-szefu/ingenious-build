<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\UseCases\CreateInvoice;

use Modules\Invoices\Application\UseCases\CreateInvoice\CreateInvoiceCommand;
use Modules\Invoices\Application\UseCases\CreateInvoice\CreateInvoiceHandler;
use Modules\Invoices\Application\UseCases\CreateInvoice\ProductLineInput;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidProductLine;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Invoices\FixedIdGenerator;
use Tests\Support\Invoices\InMemoryInvoiceRepository;
use Tests\Support\Invoices\InvoiceIds;

final class CreateInvoiceHandlerTest extends TestCase
{
    private InMemoryInvoiceRepository $repo;

    private InvoiceId $id;

    private CreateInvoiceHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InMemoryInvoiceRepository;
        $this->id = InvoiceIds::random();
        $this->handler = new CreateInvoiceHandler($this->repo, new FixedIdGenerator($this->id));
    }

    #[Test]
    public function creates_empty_draft_invoice(): void
    {
        $view = $this->handler->handle(new CreateInvoiceCommand(
            customerName: 'Ada Lovelace',
            customerEmail: 'ada@example.com',
        ));

        self::assertSame($this->id->value, $view->id);
        self::assertSame('draft', $view->status);
        self::assertSame([], $view->productLines);
        self::assertSame(0, $view->totalPrice);

        $invoice = $this->repo->getById($this->id);
        self::assertSame(StatusEnum::Draft, $invoice->status());
        self::assertSame([], $invoice->productLines());
        self::assertSame(0, $invoice->totalPrice());
    }

    #[Test]
    public function creates_draft_invoice_with_product_lines_and_totals(): void
    {
        $view = $this->handler->handle(new CreateInvoiceCommand(
            customerName: 'Ada Lovelace',
            customerEmail: 'ada@example.com',
            productLines: [
                new ProductLineInput('Widget', 2, 300),
                new ProductLineInput('Gadget', 5, 100),
            ],
        ));

        self::assertSame($this->id->value, $view->id);
        self::assertCount(2, $view->productLines);
        self::assertSame(1100, $view->totalPrice);

        $invoice = $this->repo->getById($this->id);
        self::assertSame(StatusEnum::Draft, $invoice->status());
        self::assertCount(2, $invoice->productLines());
        self::assertSame(1100, $invoice->totalPrice());
    }

    #[Test]
    public function rejects_non_positive_quantity(): void
    {
        $this->expectException(InvalidProductLine::class);

        $this->handler->handle(new CreateInvoiceCommand(
            customerName: 'Ada Lovelace',
            customerEmail: 'ada@example.com',
            productLines: [new ProductLineInput('Widget', 0, 300)],
        ));
    }

    #[Test]
    public function rejects_non_positive_unit_price(): void
    {
        $this->expectException(InvalidProductLine::class);

        $this->handler->handle(new CreateInvoiceCommand(
            customerName: 'Ada Lovelace',
            customerEmail: 'ada@example.com',
            productLines: [new ProductLineInput('Widget', 1, 0)],
        ));
    }
}
