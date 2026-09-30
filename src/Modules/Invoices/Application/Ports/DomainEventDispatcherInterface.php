<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Ports;

use Modules\Invoices\Domain\Events\DomainEvent;

/**
 * Outbound port for dispatching recorded domain events after the enclosing
 * unit of work commits. The Application layer pulls events from an
 * aggregate via `pullRecordedEvents()` and hands them to this port; the
 * Infrastructure adapter forwards them onto the framework event bus (or
 * an outbox, when one is added).
 */
interface DomainEventDispatcherInterface
{
    /**
     * @param  iterable<DomainEvent>  $events
     */
    public function dispatchAll(iterable $events): void;
}
