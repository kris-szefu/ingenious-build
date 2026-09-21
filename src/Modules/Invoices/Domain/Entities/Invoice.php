<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidStatusTransition;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSent;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;

final class Invoice
{
    /** @param list<ProductLine> $productLines */
    private function __construct(
        public private(set) InvoiceId $id,
        public private(set) string $customerName,
        public private(set) CustomerEmail $customerEmail,
        public private(set) StatusEnum $status,
        public private(set) array $productLines,
    ) {}

    /** @param list<ProductLine> $productLines */
    public static function create(
        string $customerName,
        CustomerEmail $customerEmail,
        array $productLines = [],
    ): self {
        return new self(
            InvoiceId::generate(),
            $customerName,
            $customerEmail,
            StatusEnum::Draft,
            $productLines,
        );
    }

    /** @param list<ProductLine> $productLines */
    public static function reconstitute(
        InvoiceId $id,
        string $customerName,
        CustomerEmail $customerEmail,
        StatusEnum $status,
        array $productLines,
    ): self {
        return new self(
            $id,
            $customerName,
            $customerEmail,
            $status,
            $productLines,
        );
    }

    public function send(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw InvalidStatusTransition::from($this->status, StatusEnum::Sending);
        }

        if ($this->productLines === []) {
            throw InvoiceCannotBeSent::withoutProductLines();
        }

        if (! array_all($this->productLines, static fn (ProductLine $line): bool => $line->hasPositiveAmounts())) {
            throw InvoiceCannotBeSent::withNonPositiveAmounts();
        }

        $this->status = StatusEnum::Sending;
    }

    public function totalPrice(): int
    {
        return array_reduce(
            $this->productLines,
            fn (int $total, ProductLine $productLine): int => $total + $productLine->totalUnitPrice(),
            0,
        );
    }
}
