<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Events;

use DateTimeImmutable;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

/**
 * Recorded by
 * {@see Invoice::markSentToClient()} when
 * an invoice transitions `sending → sent-to-client`.
 */
final readonly class InvoiceSentToClient implements DomainEvent
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public InvoiceId $invoiceId,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
