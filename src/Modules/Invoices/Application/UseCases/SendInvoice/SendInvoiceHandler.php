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
        // The whole flow runs inside `updateLocked` so that concurrent send
        // requests for the same invoice are serialised on a pessimistic row
        // lock. A losing request blocks until the winner commits, then sees
        // `status = sending` and its `ensureCanBeSent()` guard throws — no
        // second customer notification is dispatched.
        $this->invoices->updateLocked($command->id, function (Invoice $invoice): void {
            // Guard first: validate every send precondition without mutating
            // state so that a failure never triggers a customer notification.
            $invoice->ensureCanBeSent();

            $this->notifications->notify($this->notifyDataFor($invoice));

            // Only transition after the notification side effect has succeeded.
            // If the facade throws, `updateLocked` rolls the transaction back
            // and the invoice stays draft in storage.
            $invoice->send();
        });
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
