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

    /**
     * Reconstitute an Invoice from persisted state. Bypasses transition guards
     * on purpose — the caller is responsible for feeding valid data read from
     * storage. Intended for use only by an `InvoiceRepositoryInterface`
     * adapter; other callers should go through `::draft()` and the transition
     * methods.
     *
     * @internal
     *
     * @param  list<ProductLine>  $productLines
     */
    public static function reconstitute(
        InvoiceId $id,
        CustomerName $customerName,
        CustomerEmail $customerEmail,
        StatusEnum $status,
        array $productLines = [],
    ): self {
        return new self($id, $customerName, $customerEmail, $status, $productLines);
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
        $this->ensureCanBeSent();

        $this->status = StatusEnum::Sending;
    }

    /**
     * Verify the invoice satisfies every guard required to transition to
     * `sending`, without mutating state. Callers that need to run side effects
     * (e.g. notify the customer) before the transition should invoke this
     * first, then call {@see self::send()} once the side effect has succeeded.
     *
     * Positive `quantity` and `unitPrice` are enforced by the {@see Quantity}
     * and {@see UnitPrice} value objects at construction time, so an invoice
     * built through the public API cannot carry an invalid line here.
     *
     * @throws InvoiceCannotBeSent
     */
    public function ensureCanBeSent(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw InvoiceCannotBeSent::notInDraft($this->status);
        }

        if ($this->productLines === []) {
            throw InvoiceCannotBeSent::hasNoProductLines();
        }
    }

    public function markSentToClient(): void
    {
        if ($this->status !== StatusEnum::Sending) {
            throw InvoiceCannotBeMarkedSent::notInSending($this->status);
        }

        $this->status = StatusEnum::SentToClient;
    }
}
