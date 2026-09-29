<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\GetInvoice;

use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final readonly class GetInvoiceHandler
{
    public function __construct(private InvoiceRepositoryInterface $invoices) {}

    public function handle(InvoiceId $id): InvoiceView
    {
        $invoice = $this->invoices->getById($id);

        return $this->toView($invoice);
    }

    private function toView(Invoice $invoice): InvoiceView
    {
        $productLines = array_map(
            static fn (ProductLine $line): ProductLineView => new ProductLineView(
                productName: $line->productName->value,
                quantity: $line->quantity->value,
                unitPrice: $line->unitPrice->value,
                totalUnitPrice: $line->totalUnitPrice(),
            ),
            $invoice->productLines(),
        );

        return new InvoiceView(
            id: $invoice->id->value,
            status: $invoice->status()->value,
            customerName: $invoice->customerName->value,
            customerEmail: $invoice->customerEmail->value,
            productLines: $productLines,
            totalPrice: $invoice->totalPrice(),
        );
    }
}

