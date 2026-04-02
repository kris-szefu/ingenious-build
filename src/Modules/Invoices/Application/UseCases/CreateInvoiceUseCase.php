<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases;

use Modules\Invoices\Application\Commands\CreateInvoiceCommand;
use Modules\Invoices\Application\Dtos\InvoiceViewData;
use Modules\Invoices\Application\Ports\InvoiceIdGeneratorInterface;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;

final readonly class CreateInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceIdGeneratorInterface $invoiceIdGenerator,
    ) {}

    public function execute(CreateInvoiceCommand $command): InvoiceViewData
    {
        $invoice = Invoice::createDraft(
            id: $this->invoiceIdGenerator->generate(),
            customerName: $command->customerName,
            customerEmail: $command->customerEmail,
            productLines: $this->mapProductLines($command->productLines),
        );

        $this->invoiceRepository->save($invoice);

        return InvoiceViewData::fromDomain($invoice);
    }

    /**
     * @param  array<int, array{name: string, quantity: int, unitPrice: int}>  $productLines
     * @return array<int, InvoiceProductLine>
     */
    private function mapProductLines(array $productLines): array
    {
        return array_map(
            static fn (array $line): InvoiceProductLine => new InvoiceProductLine(
                name: $line['name'],
                quantity: $line['quantity'],
                unitPrice: $line['unitPrice'],
            ),
            $productLines,
        );
    }
}
