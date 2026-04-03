<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Application\Commands\SendInvoiceCommand;
use Modules\Invoices\Application\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Application\Exceptions\InvoiceNotificationFailedException;
use Modules\Invoices\Application\UseCases\SendInvoiceUseCase;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateTransitionException;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSentException;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Invoices\Application\Support\FakeNotificationFacade;
use Tests\Unit\Invoices\Application\Support\InMemoryInvoiceRepository;
use Tests\Unit\Invoices\Application\Support\SaveSpyInvoiceRepository;

final class SendInvoiceUseCaseTest extends TestCase
{
    public function test_transitions_to_sending_when_draft_has_valid_lines(): void
    {
        $notificationFacade = new FakeNotificationFacade;

        $invoice = Invoice::createDraft(
            id: new InvoiceId('00000000-0000-0000-0000-000000000300'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100)],
        );
        $repository = new SaveSpyInvoiceRepository($invoice);

        $useCase = new SendInvoiceUseCase(
            invoiceRepository: $repository,
            invoiceNotifier: $notificationFacade,
        );
        $result = $useCase->execute(new SendInvoiceCommand(invoiceId: '00000000-0000-0000-0000-000000000300'));

        $this->assertSame(StatusEnum::Sending->value, $result->status);
        $this->assertSame(1, $repository->saveCalls);
        $this->assertSame('00000000-0000-0000-0000-000000000300', $repository->lastSavedInvoiceId);
        $this->assertSame(1, $notificationFacade->notifyCalls);
        $this->assertNotNull($notificationFacade->lastData);
        $this->assertSame('john@example.com', $notificationFacade->lastData['toEmail']);
    }

    public function test_throws_when_notification_fails_and_does_not_save(): void
    {
        $notificationFacade = new FakeNotificationFacade;
        $notificationFacade->shouldThrow = true;

        $invoice = Invoice::createDraft(
            id: new InvoiceId('00000000-0000-0000-0000-000000000310'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100)],
        );
        $repository = new SaveSpyInvoiceRepository($invoice);

        $useCase = new SendInvoiceUseCase(
            invoiceRepository: $repository,
            invoiceNotifier: $notificationFacade,
        );

        $this->expectException(InvoiceNotificationFailedException::class);

        try {
            $useCase->execute(new SendInvoiceCommand(invoiceId: '00000000-0000-0000-0000-000000000310'));
        } finally {
            $this->assertSame(0, $repository->saveCalls);
            $this->assertSame(1, $notificationFacade->notifyCalls);
        }
    }

    public function test_throws_when_draft_has_no_lines(): void
    {
        $repository = new InMemoryInvoiceRepository;
        $notificationFacade = new FakeNotificationFacade;
        $invoice = Invoice::createDraft(
            id: new InvoiceId('00000000-0000-0000-0000-000000000301'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [],
        );
        $repository->save($invoice);

        $useCase = new SendInvoiceUseCase(
            invoiceRepository: $repository,
            invoiceNotifier: $notificationFacade,
        );

        $this->expectException(InvoiceCannotBeSentException::class);
        $useCase->execute(new SendInvoiceCommand(invoiceId: '00000000-0000-0000-0000-000000000301'));
    }

    public function test_throws_when_state_transition_is_invalid(): void
    {
        $repository = new InMemoryInvoiceRepository;
        $notificationFacade = new FakeNotificationFacade;
        $invoice = Invoice::rehydrate(
            id: new InvoiceId('00000000-0000-0000-0000-000000000302'),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            productLines: [new InvoiceProductLine(name: 'Hat', quantity: 2, unitPrice: 100)],
            status: StatusEnum::Sending,
        );
        $repository->save($invoice);

        $useCase = new SendInvoiceUseCase(
            invoiceRepository: $repository,
            invoiceNotifier: $notificationFacade,
        );

        $this->expectException(InvalidInvoiceStateTransitionException::class);
        $useCase->execute(new SendInvoiceCommand(invoiceId: '00000000-0000-0000-0000-000000000302'));
    }

    public function test_throws_when_invoice_is_missing(): void
    {
        $useCase = new SendInvoiceUseCase(
            invoiceRepository: new InMemoryInvoiceRepository,
            invoiceNotifier: new FakeNotificationFacade,
        );

        $this->expectException(InvoiceNotFoundException::class);
        $useCase->execute(new SendInvoiceCommand(invoiceId: 'invoice-missing'));
    }
}
