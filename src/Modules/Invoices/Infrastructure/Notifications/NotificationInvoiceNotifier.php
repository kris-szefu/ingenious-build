<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Notifications;

use Modules\Invoices\Application\Ports\InvoiceNotifierInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Ramsey\Uuid\Uuid;

final readonly class NotificationInvoiceNotifier implements InvoiceNotifierInterface
{
    public function __construct(
        private NotificationFacadeInterface $notificationFacade,
    ) {}

    public function notify(
        string $invoiceId,
        string $toEmail,
        string $subject,
        string $message,
    ): void {
        $this->notificationFacade->notify(new NotifyData(
            resourceId: Uuid::fromString($invoiceId),
            toEmail: $toEmail,
            subject: $subject,
            message: $message,
        ));
    }
}
