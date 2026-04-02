<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Support;

use Modules\Invoices\Application\Ports\InvoiceNotifierInterface;
use RuntimeException;

final class FakeNotificationFacade implements InvoiceNotifierInterface
{
    public int $notifyCalls = 0;

    /**
     * @var array{
     *     invoiceId: string,
     *     toEmail: string,
     *     subject: string,
     *     message: string
     * }|null
     */
    public ?array $lastData = null;

    public bool $shouldThrow = false;

    public function notify(string $invoiceId, string $toEmail, string $subject, string $message): void
    {
        $this->notifyCalls++;
        $this->lastData = [
            'invoiceId' => $invoiceId,
            'toEmail' => $toEmail,
            'subject' => $subject,
            'message' => $message,
        ];

        if ($this->shouldThrow) {
            throw new RuntimeException('Notification provider failure.');
        }
    }
}
