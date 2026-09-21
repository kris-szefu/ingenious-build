<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Infrastructure;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use Modules\Invoices\Infrastructure\Notifications\NotificationFacadeInvoiceNotifier;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NotificationFacadeInvoiceNotifierTest extends TestCase
{
    #[Test]
    public function it_sends_the_invoice_to_the_customer_referencing_the_invoice_id(): void
    {
        $invoice = Invoice::create(
            'Acme Corp',
            CustomerEmail::fromString('billing@acme.test'),
            [new ProductLine('Desk', 2, 15000)],
        );

        $facade = $this->createMock(NotificationFacadeInterface::class);
        $facade->expects($this->once())
            ->method('notify')
            ->with($this->callback(
                static fn (NotifyData $data): bool => $data->resourceId->toString() === $invoice->id->value
                    && $data->toEmail === 'billing@acme.test'
                    && str_contains($data->subject, $invoice->id->value)
                    && str_contains($data->message, 'Acme Corp')
                    && str_contains($data->message, '30000'),
            ));

        new NotificationFacadeInvoiceNotifier($facade)->notifyCustomer($invoice);
    }
}
