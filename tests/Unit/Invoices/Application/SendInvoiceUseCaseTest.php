<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Application\Commands\SendInvoiceCommand;
use Modules\Invoices\Application\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Application\UseCases\SendInvoiceUseCase;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateTransitionException;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSentException;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Invoices\Application\Support\InMemoryInvoiceRepository;
use Tests\Unit\Invoices\Application\Support\SaveSpyInvoiceRepository;

final class SendInvoiceUseCaseTest extends TestCase
{
    public function test_transitions_to_sending_when_draft_has_valid_lines(): void
    {
        $invoice = Invoice::createDraft(
            id: new InvoiceId('invoice-300'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100)],
        );
        $repository = new SaveSpyInvoiceRepository($invoice);

        $useCase = new SendInvoiceUseCase(invoiceRepository: $repository);
        $result = $useCase->execute(new SendInvoiceCommand(invoiceId: 'invoice-300'));

        $this->assertSame(StatusEnum::Sending->value, $result->status);
        $this->assertSame(1, $repository->saveCalls);
        $this->assertSame('invoice-300', $repository->lastSavedInvoiceId);
    }

    public function test_throws_when_draft_has_no_lines(): void
    {
        $repository = new InMemoryInvoiceRepository;
        $invoice = Invoice::createDraft(
            id: new InvoiceId('invoice-301'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [],
        );
        $repository->save($invoice);

        $useCase = new SendInvoiceUseCase(invoiceRepository: $repository);

        $this->expectException(InvoiceCannotBeSentException::class);
        $useCase->execute(new SendInvoiceCommand(invoiceId: 'invoice-301'));
    }

    public function test_throws_when_state_transition_is_invalid(): void
    {
        $repository = new InMemoryInvoiceRepository;
        $invoice = Invoice::rehydrate(
            id: new InvoiceId('invoice-302'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100)],
            status: StatusEnum::Sending,
        );
        $repository->save($invoice);

        $useCase = new SendInvoiceUseCase(invoiceRepository: $repository);

        $this->expectException(InvalidInvoiceStateTransitionException::class);
        $useCase->execute(new SendInvoiceCommand(invoiceId: 'invoice-302'));
    }

    public function test_throws_when_invoice_is_missing(): void
    {
        $useCase = new SendInvoiceUseCase(invoiceRepository: new InMemoryInvoiceRepository);

        $this->expectException(InvoiceNotFoundException::class);
        $useCase->execute(new SendInvoiceCommand(invoiceId: 'invoice-missing'));
    }
}
