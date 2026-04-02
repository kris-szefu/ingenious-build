<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateException;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateTransitionException;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSentException;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final class Invoice
{
    /**
     * @param  array<int, InvoiceProductLine>  $productLines
     */
    private function __construct(
        private InvoiceId $id,
        private string $customerName,
        private string $customerEmail,
        private array $productLines,
        private StatusEnum $status,
    ) {}

    /**
     * @param  array<int, InvoiceProductLine>  $productLines
     */
    public static function createDraft(
        InvoiceId $id,
        string $customerName,
        string $customerEmail,
        array $productLines = [],
    ): self {
        return new self(
            id: $id,
            customerName: $customerName,
            customerEmail: $customerEmail,
            productLines: $productLines,
            status: StatusEnum::Draft,
        );
    }

    /**
     * @param  array<int, InvoiceProductLine>  $productLines
     */
    public static function rehydrate(
        InvoiceId $id,
        string $customerName,
        string $customerEmail,
        array $productLines,
        StatusEnum $status,
    ): self {
        if (($status->isSending() || $status->isSentToClient()) && empty($productLines)) {
            throw InvalidInvoiceStateException::missingProductLinesForStatus($status);
        }

        return new self(
            id: $id,
            customerName: $customerName,
            customerEmail: $customerEmail,
            productLines: $productLines,
            status: $status,
        );
    }

    public function id(): InvoiceId
    {
        return $this->id;
    }

    public function customerName(): string
    {
        return $this->customerName;
    }

    public function customerEmail(): string
    {
        return $this->customerEmail;
    }

    public function status(): StatusEnum
    {
        return $this->status;
    }

    /**
     * @return array<int, InvoiceProductLine>
     */
    public function productLines(): array
    {
        return $this->productLines;
    }

    public function canBeSent(): bool
    {
        return $this->status->isDraft() && count($this->productLines) > 0;
    }

    public function markAsSending(): void
    {
        if (! $this->status->isDraft()) {
            throw InvalidInvoiceStateTransitionException::from($this->status, StatusEnum::Sending);
        }

        if (count($this->productLines) === 0) {
            throw InvoiceCannotBeSentException::missingProductLines();
        }

        $this->status = StatusEnum::Sending;
    }

    public function markAsSentToClient(): void
    {
        if (! $this->status->isSending()) {
            throw InvalidInvoiceStateTransitionException::from($this->status, StatusEnum::SentToClient);
        }

        $this->status = StatusEnum::SentToClient;
    }

    public function totalPrice(): int
    {
        return array_sum(array_map(
            static fn (InvoiceProductLine $line): int => $line->totalUnitPrice(),
            $this->productLines,
        ));
    }
}
