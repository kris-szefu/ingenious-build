<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Events\DomainEvent;
use Modules\Invoices\Domain\Events\InvoiceMarkedSending;
use Modules\Invoices\Domain\Events\InvoiceSentToClient;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeMarkedSent;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSent;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;

final class Invoice
{
    /** @var list<ProductLine> */
    private array $productLines;

    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

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

    /**
     * Transition `draft → sending`. Records {@see InvoiceMarkedSending}.
     *
     * Guards are re-checked here so callers cannot bypass them, and so the
     * aggregate always owns the last word before mutation. Callers that
     * need to run a side effect *before* the transition (e.g. notify the
     * customer) should call {@see self::assertCanBeSent()} first to fail
     * fast without triggering the side effect.
     *
     * @throws InvoiceCannotBeSent
     */
    public function send(): void
    {
        $this->assertCanBeSent();

        $this->status = StatusEnum::Sending;
        $this->recordedEvents[] = new InvoiceMarkedSending($this->id);
    }

    /**
     * Transition `sending → sent-to-client`. Records
     * {@see InvoiceSentToClient}.
     *
     * @throws InvoiceCannotBeMarkedSent
     */
    public function markSentToClient(): void
    {
        if ($this->status !== StatusEnum::Sending) {
            throw InvoiceCannotBeMarkedSent::notInSending($this->status);
        }

        $this->status = StatusEnum::SentToClient;
        $this->recordedEvents[] = new InvoiceSentToClient($this->id);
    }

    /**
     * Return and clear the events recorded since the last pull. Intended for
     * post-commit dispatch by the Application layer; the Domain never
     * dispatches its own events.
     *
     * @return list<DomainEvent>
     */
    public function pullRecordedEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    /**
     * Assert (throw on failure) that every send precondition holds, without
     * mutating state. Callers that need to run side effects (e.g. notify the
     * customer) before the transition should invoke this first, then call
     * {@see self::send()} once the side effect has succeeded.
     *
     * Positive `quantity` and `unitPrice` are enforced by the
     * {@see Quantity} and
     * {@see UnitPrice} value objects at
     * construction time, so an invoice built through the public API cannot
     * carry an invalid line here.
     *
     * @throws InvoiceCannotBeSent
     */
    public function assertCanBeSent(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw InvoiceCannotBeSent::notInDraft($this->status);
        }

        if ($this->productLines === []) {
            throw InvoiceCannotBeSent::hasNoProductLines();
        }
    }
}
