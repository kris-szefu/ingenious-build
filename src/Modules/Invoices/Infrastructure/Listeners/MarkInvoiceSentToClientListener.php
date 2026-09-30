<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Listeners;

use Modules\Invoices\Application\UseCases\MarkInvoiceDelivered\MarkInvoiceDeliveredCommand;
use Modules\Invoices\Application\UseCases\MarkInvoiceDelivered\MarkInvoiceDeliveredHandler;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Notifications\Api\Events\WebhookDeliveredEvent;

final readonly class MarkInvoiceSentToClientListener
{
    public function __construct(
        private MarkInvoiceDeliveredHandler $handler,
    ) {}

    public function handle(WebhookDeliveredEvent $event): void
    {
        $this->handler->handle(
            new MarkInvoiceDeliveredCommand(
                InvoiceId::fromString($event->resourceId->toString()),
            ),
        );
    }
}

