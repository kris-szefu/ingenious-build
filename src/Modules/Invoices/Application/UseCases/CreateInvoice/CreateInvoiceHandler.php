<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\CreateInvoice;

use Modules\Invoices\Application\Ports\IdGeneratorInterface;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;

final readonly class CreateInvoiceHandler
{
    public function __construct(
        private InvoiceRepositoryInterface $invoices,
        private IdGeneratorInterface $ids,
    ) {}

    public function handle(CreateInvoiceCommand $command): InvoiceId
    {
        $id = $this->ids->next();

        $productLines = array_map(
            static fn (ProductLineInput $line): ProductLine => new ProductLine(
                new ProductName($line->productName),
                new Quantity($line->quantity),
                new UnitPrice($line->unitPrice),
            ),
            array_values($command->productLines),
        );

        $invoice = Invoice::draft(
            $id,
            new CustomerName($command->customerName),
            new CustomerEmail($command->customerEmail),
            $productLines,
        );

        $this->invoices->save($invoice);

        return $id;
    }
}

