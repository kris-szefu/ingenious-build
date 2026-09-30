<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\CreateInvoice;

final readonly class ProductLineInput
{
    public function __construct(
        public string $productName,
        public int $quantity,
        public int $unitPrice,
    ) {}
}
