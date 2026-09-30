<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Notifications;

use Modules\Invoices\Application\Ports\CustomerNotifierInterface;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Notifications\Api\Data\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Ramsey\Uuid\Uuid;

/**
 * Anti-corruption adapter: translates Invoices' invoice-shaped notifier port
 * into the Notifications module's public {@see NotifyData} DTO.
 *
 * Wire-format concerns (subject / message body, correlation id shape, UUID
 * marshalling) live here so they never leak into the Application layer.
 */
final readonly class NotificationsCustomerNotifier implements CustomerNotifierInterface
{
    public function __construct(
        private NotificationFacadeInterface $notifications,
    ) {}

    public function notifyInvoiceReady(
        InvoiceId $invoiceId,
        CustomerName $customerName,
        CustomerEmail $customerEmail,
    ): void {
        $this->notifications->notify(new NotifyData(
            resourceId: Uuid::fromString($invoiceId->value),
            toEmail: $customerEmail->value,
            subject: "Invoice {$invoiceId->value}",
            message: "Dear {$customerName->value}, please find your invoice attached.",
        ));
    }
}
