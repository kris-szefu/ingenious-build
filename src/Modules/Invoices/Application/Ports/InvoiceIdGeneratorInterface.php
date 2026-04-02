<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Ports;

use Modules\Invoices\Domain\ValueObjects\InvoiceId;

interface InvoiceIdGeneratorInterface
{
    public function generate(): InvoiceId;
}
