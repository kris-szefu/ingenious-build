<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\SendInvoice;

use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Notifications\Api\Data\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Ramsey\Uuid\Uuid;

final readonly class SendInvoiceHandler
{
    public function __construct(
        private InvoiceRepositoryInterface $invoices,
        private NotificationFacadeInterface $notifications,
    ) {}

    public function handle(SendInvoiceCommand $command): void
    {
        $invoice = $this->invoices->getById($command->id);

        // Guard first: validate every send precondition without mutating state
        // so that a failure never triggers a customer notification.
        $invoice->ensureCanBeSent();

        $this->notifications->notify($this->notifyDataFor($invoice));

        // Only transition + persist after the notification side effect has
        // succeeded. If the facade throws, the invoice stays draft in storage.
        $invoice->send();
        $this->invoices->save($invoice);
    }

    private function notifyDataFor(Invoice $invoice): NotifyData
    {
        return new NotifyData(
            resourceId: Uuid::fromString($invoice->id->value),
            toEmail: $invoice->customerEmail->value,
            subject: "Invoice {$invoice->id->value}",
            message: "Dear {$invoice->customerName->value}, please find your invoice attached.",
        );
    }
}
