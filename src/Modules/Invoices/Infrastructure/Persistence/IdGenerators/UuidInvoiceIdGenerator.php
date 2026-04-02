<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence\IdGenerators;

use Modules\Invoices\Application\Ports\InvoiceIdGeneratorInterface;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Ramsey\Uuid\Uuid;

final class UuidInvoiceIdGenerator implements InvoiceIdGeneratorInterface
{
    public function generate(): InvoiceId
    {
        return new InvoiceId(Uuid::uuid4()->toString());
    }
}
