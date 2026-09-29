<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\UseCases\GetInvoice;

use Modules\Invoices\Application\UseCases\GetInvoice\GetInvoiceHandler;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFound;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Invoices\InMemoryInvoiceRepository;

final class GetInvoiceHandlerTest extends TestCase
{
    private InMemoryInvoiceRepository $repo;
    private GetInvoiceHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InMemoryInvoiceRepository();
        $this->handler = new GetInvoiceHandler($this->repo);
    }

    #[Test]
    public function returns_view_dto_with_expected_shape_and_totals(): void
    {
        $id = InvoiceId::generate();
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

        $view = $this->handler->handle($id);

        self::assertSame([
            'id' => $id->value,
            'status' => 'draft',
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'ada@example.com',
            'product_lines' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'unit_price' => 300, 'total_unit_price' => 600],
                ['product_name' => 'Gadget', 'quantity' => 5, 'unit_price' => 100, 'total_unit_price' => 500],
            ],
            'total_price' => 1100,
        ], $view->toArray());
    }

    #[Test]
    public function returns_view_with_empty_product_lines(): void
    {
        $id = InvoiceId::generate();
        $this->repo->save(Invoice::draft(
            $id,
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
        ));

        $view = $this->handler->handle($id);

        self::assertSame([], $view->productLines);
        self::assertSame(0, $view->totalPrice);
    }

    #[Test]
    public function throws_invoice_not_found_when_id_is_unknown(): void
    {
        $this->expectException(InvoiceNotFound::class);

        $this->handler->handle(InvoiceId::generate());
    }
}

