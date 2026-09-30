<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\CreateInvoice;

final readonly class CreateInvoiceCommand
{
    /**
     * @param  list<ProductLineInput>  $productLines
     */
    public function __construct(
        public string $customerName,
        public string $customerEmail,
        public array $productLines = [],
    ) {}
}
