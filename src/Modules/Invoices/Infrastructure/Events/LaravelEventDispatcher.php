<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Invoices\Application\Ports\DomainEventDispatcherInterface;
use Modules\Invoices\Domain\Events\DomainEvent;
use Modules\Invoices\Domain\Events\InvoiceMarkedSending;

/**
 * Adapter over Laravel's event bus. Each recorded domain event is dispatched
 * under its own class name — subscribers subscribe to concrete event types
 * such as {@see InvoiceMarkedSending}.
 *
 * No listeners are wired today; the seam exists so future consumers (e.g.
 * an outbox writer, audit sink, or async fan-out) can subscribe without
 * touching Application code.
 */
final readonly class LaravelEventDispatcher implements DomainEventDispatcherInterface
{
    public function __construct(
        private Dispatcher $bus,
    ) {}

    /**
     * @param  iterable<DomainEvent>  $events
     */
    public function dispatchAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->bus->dispatch($event);
        }
    }
}
