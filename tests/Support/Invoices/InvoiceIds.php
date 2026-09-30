<?php

declare(strict_types=1);

namespace Tests\Support\Invoices;

use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Ramsey\Uuid\Uuid;

/**
 * Test-only ergonomics: mint a valid random InvoiceId without depending on
 * production id-generation policy (which lives in the infrastructure adapter).
 */
final class InvoiceIds
{
    public static function random(): InvoiceId
    {
        return InvoiceId::fromString(Uuid::uuid4()->toString());
    }
}
