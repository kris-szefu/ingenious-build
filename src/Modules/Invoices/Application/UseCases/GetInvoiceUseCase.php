<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases;

use Modules\Invoices\Application\Dtos\InvoiceViewData;
use Modules\Invoices\Application\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Application\Queries\GetInvoiceQuery;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final readonly class GetInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(GetInvoiceQuery $query): InvoiceViewData
    {
        $invoice = $this->invoiceRepository->getById(new InvoiceId($query->invoiceId));

        if ($invoice === null) {
            throw InvoiceNotFoundException::withId($query->invoiceId);
        }

        return InvoiceViewData::fromDomain($invoice);
    }
}
