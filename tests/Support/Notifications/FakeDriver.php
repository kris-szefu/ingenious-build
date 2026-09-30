<?php

declare(strict_types=1);

namespace Tests\Support\Notifications;

use Modules\Notifications\Infrastructure\Drivers\DriverInterface;

final class FakeDriver implements DriverInterface
{
    /** @var list<array{toEmail: string, subject: string, message: string, reference: string}> */
    public array $sent = [];

    public function send(
        string $toEmail,
        string $subject,
        string $message,
        string $reference,
    ): void {
        $this->sent[] = [
            'toEmail' => $toEmail,
            'subject' => $subject,
            'message' => $message,
            'reference' => $reference,
        ];
    }
}

