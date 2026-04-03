<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Enums;

enum StatusEnum: string
{
    case Draft = 'draft';
    case Sending = 'sending';
    case SentToClient = 'sent-to-client';

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isSending(): bool
    {
        return $this === self::Sending;
    }

    public function isSentToClient(): bool
    {
        return $this === self::SentToClient;
    }
}
