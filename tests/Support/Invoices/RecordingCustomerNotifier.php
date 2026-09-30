<?php

declare(strict_types=1);

namespace Tests\Support\Invoices;

use Modules\Invoices\Application\Ports\CustomerNotifierInterface;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Throwable;

/**
 * Test double for {@see CustomerNotifierInterface} that records every call
 * for later assertion. Optionally re-throws a queued exception to simulate
 * transport failures.
 */
final class RecordingCustomerNotifier implements CustomerNotifierInterface
{
    /** @var list<array{invoiceId: string, customerName: string, customerEmail: string}> */
    public array $calls = [];

    private ?Throwable $throwOnNext = null;

    public function throwOnNextCall(Throwable $exception): void
    {
        $this->throwOnNext = $exception;
    }

    public function notifyInvoiceReady(
        InvoiceId $invoiceId,
        CustomerName $customerName,
        CustomerEmail $customerEmail,
    ): void {
        $this->calls[] = [
            'invoiceId' => $invoiceId->value,
            'customerName' => $customerName->value,
            'customerEmail' => $customerEmail->value,
        ];

        if ($this->throwOnNext !== null) {
            $toThrow = $this->throwOnNext;
            $this->throwOnNext = null;
            throw $toThrow;
        }
    }
}
