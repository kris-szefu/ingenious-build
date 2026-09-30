<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\UseCases\SendInvoice;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Invoices\Application\UseCases\SendInvoice\SendInvoiceCommand;
use Modules\Invoices\Application\UseCases\SendInvoice\SendInvoiceHandler;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSent;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use Modules\Notifications\Api\Data\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\Invoices\InMemoryInvoiceRepository;
use Tests\Support\Invoices\InvoiceIds;

final class SendInvoiceHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private InMemoryInvoiceRepository $repo;

    private InvoiceId $id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InMemoryInvoiceRepository;
        $this->id = InvoiceIds::random();
    }

    #[Test]
    public function it_notifies_customer_and_transitions_invoice_to_sending(): void
    {
        $this->repo->save($this->draftInvoiceWithLines());

        $facade = Mockery::mock(NotificationFacadeInterface::class);
        $facade->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function (NotifyData $data): bool {
                self::assertSame($this->id->value, $data->resourceId->toString());
                self::assertSame('ada@example.com', $data->toEmail);
                self::assertNotSame('', $data->subject);
                self::assertNotSame('', $data->message);

                return true;
            }));

        $this->handlerWith($facade)->handle(new SendInvoiceCommand($this->id));

        self::assertSame(StatusEnum::Sending, $this->repo->getById($this->id)->status());
    }

    #[Test]
    public function it_rejects_when_invoice_is_not_in_draft(): void
    {
        $invoice = $this->draftInvoiceWithLines();
        $invoice->send(); // draft -> sending
        $this->repo->save($invoice);

        $facade = Mockery::mock(NotificationFacadeInterface::class);
        $facade->shouldNotReceive('notify');

        $this->expectException(InvoiceCannotBeSent::class);

        try {
            $this->handlerWith($facade)->handle(new SendInvoiceCommand($this->id));
        } finally {
            self::assertSame(StatusEnum::Sending, $this->repo->getById($this->id)->status());
        }
    }

    #[Test]
    public function it_rejects_when_invoice_has_no_product_lines(): void
    {
        $this->repo->save(Invoice::draft(
            $this->id,
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
        ));

        $facade = Mockery::mock(NotificationFacadeInterface::class);
        $facade->shouldNotReceive('notify');

        $this->expectException(InvoiceCannotBeSent::class);

        try {
            $this->handlerWith($facade)->handle(new SendInvoiceCommand($this->id));
        } finally {
            self::assertSame(StatusEnum::Draft, $this->repo->getById($this->id)->status());
        }
    }

    #[Test]
    public function it_leaves_invoice_in_draft_when_facade_throws(): void
    {
        $this->repo->save($this->draftInvoiceWithLines());

        $facade = Mockery::mock(NotificationFacadeInterface::class);
        $facade->shouldReceive('notify')
            ->once()
            ->andThrow(new RuntimeException('smtp down'));

        try {
            $this->handlerWith($facade)->handle(new SendInvoiceCommand($this->id));
            self::fail('Expected RuntimeException to bubble.');
        } catch (RuntimeException) {
            // expected
        }

        self::assertSame(StatusEnum::Draft, $this->repo->getById($this->id)->status());
    }

    private function handlerWith(NotificationFacadeInterface $facade): SendInvoiceHandler
    {
        return new SendInvoiceHandler($this->repo, $facade);
    }

    private function draftInvoiceWithLines(): Invoice
    {
        return Invoice::draft(
            $this->id,
            new CustomerName('Ada Lovelace'),
            new CustomerEmail('ada@example.com'),
            [
                new ProductLine(
                    new ProductName('Widget'),
                    new Quantity(2),
                    new UnitPrice(300),
                ),
            ],
        );
    }
}
