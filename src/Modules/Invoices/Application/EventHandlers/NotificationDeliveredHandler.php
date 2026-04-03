<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\EventHandlers;

use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Notifications\Api\Events\WebhookDeliveredEvent;

final readonly class NotificationDeliveredHandler
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function handle(WebhookDeliveredEvent $event): void
    {
        $invoice = $this->invoiceRepository->getById(new InvoiceId($event->resourceId->toString()));

        if ($invoice === null || ! $invoice->status()->isSending()) {
            return;
        }

        $invoice->markAsSentToClient();
        $this->invoiceRepository->save($invoice);
    }
}
