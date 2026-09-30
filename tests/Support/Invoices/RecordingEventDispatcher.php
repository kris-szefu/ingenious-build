<?php

declare(strict_types=1);

namespace Tests\Support\Invoices;

use Modules\Invoices\Application\Ports\DomainEventDispatcherInterface;
use Modules\Invoices\Domain\Events\DomainEvent;

/**
 * Test double for {@see DomainEventDispatcherInterface} that records every
 * dispatched event for later assertion.
 */
final class RecordingEventDispatcher implements DomainEventDispatcherInterface
{
    /** @var list<DomainEvent> */
    public array $dispatched = [];

    public function dispatchAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->dispatched[] = $event;
        }
    }
}
