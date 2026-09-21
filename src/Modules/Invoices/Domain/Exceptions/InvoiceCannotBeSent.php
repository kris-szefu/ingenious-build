<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

final class InvoiceCannotBeSent extends \DomainException
{
    public static function withoutProductLines(): self
    {
        return new self('An invoice without product lines cannot be sent.');
    }

    public static function withNonPositiveAmounts(): self
    {
        return new self('Every product line needs a positive quantity and unit price.');
    }
}
