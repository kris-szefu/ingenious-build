<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Events;

use DateTimeImmutable;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

/**
 * Recorded by {@see Invoice::send()} when
 * an invoice transitions `draft → sending`.
 */
final readonly class InvoiceMarkedSending implements DomainEvent
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
