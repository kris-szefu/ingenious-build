<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\GetInvoice;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;

final readonly class InvoiceView
{
    /**
     * @param  list<ProductLineView>  $productLines
     */
    public function __construct(
        public string $id,
        public string $status,
        public string $customerName,
        public string $customerEmail,
        public array $productLines,
        public int $totalPrice,
    ) {}

    public static function fromDomain(Invoice $invoice): self
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

        return new self(
            id: $invoice->id->value,
            status: $invoice->status()->value,
            customerName: $invoice->customerName->value,
            customerEmail: $invoice->customerEmail->value,
            productLines: $productLines,
            totalPrice: $invoice->totalPrice(),
        );
    }

    /**
     * @return array{
     *   id: string,
     *   status: string,
     *   customer_name: string,
     *   customer_email: string,
     *   product_lines: list<array{product_name: string, quantity: int, unit_price: int, total_unit_price: int}>,
     *   total_price: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'product_lines' => array_map(
                static fn (ProductLineView $line): array => $line->toArray(),
                $this->productLines,
            ),
            'total_price' => $this->totalPrice,
        ];
    }
}
