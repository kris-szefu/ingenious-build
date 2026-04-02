<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases;

use Modules\Invoices\Application\Commands\SendInvoiceCommand;
use Modules\Invoices\Application\Dtos\InvoiceViewData;
use Modules\Invoices\Application\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final readonly class SendInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(SendInvoiceCommand $command): InvoiceViewData
    {
        $invoice = $this->invoiceRepository->getById(new InvoiceId($command->invoiceId));

        if ($invoice === null) {
            throw InvoiceNotFoundException::withId($command->invoiceId);
        }

        $invoice->markAsSending();
        $this->invoiceRepository->save($invoice);

        return InvoiceViewData::fromDomain($invoice);
    }
}
