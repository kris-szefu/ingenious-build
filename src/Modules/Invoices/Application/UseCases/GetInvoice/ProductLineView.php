<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\GetInvoice;

final readonly class ProductLineView
{
    public function __construct(
        public string $productName,
        public int $quantity,
        public int $unitPrice,
        public int $totalUnitPrice,
    ) {}

    /**
     * @return array{product_name: string, quantity: int, unit_price: int, total_unit_price: int}
     */
    public function toArray(): array
    {
        return [
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_unit_price' => $this->totalUnitPrice,
        ];
    }
}

