<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeMarkedSent;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSent;
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
    private function __construct(
        public readonly InvoiceId $id,
        public readonly CustomerName $customerName,
        public readonly CustomerEmail $customerEmail,
        private StatusEnum $status,
        array $productLines = [],
    ) {
        $this->productLines = array_values($productLines);
    }

    /**
     * @param  list<ProductLine>  $productLines
     */
    public static function draft(
        InvoiceId $id,
        CustomerName $customerName,
        CustomerEmail $customerEmail,
        array $productLines = [],
    ): self {
        return new self($id, $customerName, $customerEmail, StatusEnum::Draft, $productLines);
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

    public function send(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw InvoiceCannotBeSent::notInDraft($this->status);
        }

        if ($this->productLines === []) {
            throw InvoiceCannotBeSent::hasNoProductLines();
        }

        foreach ($this->productLines as $line) {
            if ($line->quantity->value <= 0 || $line->unitPrice->value <= 0) {
                throw InvoiceCannotBeSent::hasInvalidProductLine();
            }
        }

        $this->status = StatusEnum::Sending;
    }

    public function markSentToClient(): void
    {
        if ($this->status !== StatusEnum::Sending) {
            throw InvoiceCannotBeMarkedSent::notInSending($this->status);
        }

        $this->status = StatusEnum::SentToClient;
    }
}
