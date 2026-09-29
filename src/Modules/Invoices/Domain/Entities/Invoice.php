<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final class Invoice
{
    /** @var list<ProductLine> */
    private array $productLines;

    /**
     * @param  list<ProductLine>  $productLines
     */
    public function __construct(
        public readonly InvoiceId $id,
        public readonly CustomerName $customerName,
        public readonly CustomerEmail $customerEmail,
        private StatusEnum $status,
        array $productLines = [],
    ) {
        $this->productLines = array_values($productLines);
    }

    public function status(): StatusEnum
    {
        return $this->status;
    }

    /** @return list<ProductLine> */
    public function productLines(): array
    {
        return $this->productLines;
    }

    public function totalPrice(): int
    {
        return array_sum(array_map(
            static fn (ProductLine $line): int => $line->totalUnitPrice(),
            $this->productLines,
        ));
    }
}
