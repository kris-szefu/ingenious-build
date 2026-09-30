<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\UseCases\MarkInvoiceDelivered;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Minimal PSR-3 logger that records `warning()` calls for assertions.
 */
final class SpyLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    public array $warnings = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        if ((string) $level !== 'warning') {
            return;
        }

        $this->warnings[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
