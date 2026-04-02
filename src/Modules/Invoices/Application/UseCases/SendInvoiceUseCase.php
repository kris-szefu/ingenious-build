<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases;

use Modules\Invoices\Application\Commands\SendInvoiceCommand;
use Modules\Invoices\Application\Dtos\InvoiceViewData;
use Modules\Invoices\Application\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Application\Exceptions\InvoiceNotificationFailedException;
use Modules\Invoices\Application\Ports\InvoiceNotifierInterface;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Throwable;

final readonly class SendInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceNotifierInterface $invoiceNotifier,
    ) {}

    public function execute(SendInvoiceCommand $command): InvoiceViewData
    {
        $invoice = $this->invoiceRepository->getById(new InvoiceId($command->invoiceId));

        if ($invoice === null) {
            throw InvoiceNotFoundException::withId($command->invoiceId);
        }

        $invoice->assertCanBeMarkedAsSending();

        try {
            $this->invoiceNotifier->notify(
                invoiceId: $invoice->id()->value(),
                toEmail: $invoice->customerEmail(),
                subject: 'Invoice delivery in progress',
                message: 'Your invoice is being sent.',
            );
        } catch (Throwable $exception) {
            throw InvoiceNotificationFailedException::forInvoice($command->invoiceId, $exception);
        }

        $invoice->markAsSending();
        $this->invoiceRepository->save($invoice);

        return InvoiceViewData::fromDomain($invoice);
    }
}
