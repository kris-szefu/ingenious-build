<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Notifications;

use Modules\Invoices\Application\Ports\InvoiceNotifier;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Ramsey\Uuid\Uuid;

final readonly class NotificationFacadeInvoiceNotifier implements InvoiceNotifier
{
    public function __construct(
        private NotificationFacadeInterface $notifications,
    ) {}

    public function notifyCustomer(Invoice $invoice): void
    {
        $this->notifications->notify(new NotifyData(
            resourceId: Uuid::fromString($invoice->id->value),
            toEmail: $invoice->customerEmail->value,
            subject: sprintf('Invoice %s', $invoice->id->value),
            message: sprintf(
                'Dear %s, your invoice %s totalling %d is ready.',
                $invoice->customerName,
                $invoice->id->value,
                $invoice->totalPrice(),
            ),
        ));
    }
}
