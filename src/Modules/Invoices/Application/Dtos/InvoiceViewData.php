<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;

final readonly class InvoiceViewData
{
    /**
     * @param  array<int, InvoiceProductLineViewData>  $productLines
     */
    public function __construct(
        public string $invoiceId,
        public string $status,
        public string $customerName,
        public string $customerEmail,
        public array $productLines,
        public int $totalPrice,
    ) {}

    public static function fromDomain(Invoice $invoice): self
    {
        return new self(
            invoiceId: $invoice->id()->value(),
            status: $invoice->status()->value,
            customerName: $invoice->customerName(),
            customerEmail: $invoice->customerEmail(),
            productLines: array_map(
                static fn (InvoiceProductLine $line): InvoiceProductLineViewData => InvoiceProductLineViewData::fromDomain($line),
                $invoice->productLines(),
            ),
            totalPrice: $invoice->totalPrice(),
        );
    }
}
