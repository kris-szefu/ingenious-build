<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Events;

use DateTimeImmutable;

/**
 * Marker for a domain event recorded by an aggregate.
 *
 * Kept intentionally minimal: pure PHP, no framework contracts, no
 * references to Application or Infrastructure. The Application layer pulls
 * recorded events from the aggregate after a successful commit and hands
 * them to its own outbound dispatcher port for delivery.
 */
interface DomainEvent
{
    public function occurredAt(): DateTimeImmutable;
}
