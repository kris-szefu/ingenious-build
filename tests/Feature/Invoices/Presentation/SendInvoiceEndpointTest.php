<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notifications\Infrastructure\Drivers\DriverInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\Support\Notifications\FakeDriver;
use Tests\TestCase;

final class SendInvoiceEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeDriver $fakeDriver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDriver = new FakeDriver;
        $this->app->instance(DriverInterface::class, $this->fakeDriver);
    }

    #[Test]
    public function sends_draft_invoice_and_transitions_to_sending(): void
    {
        $id = $this->createInvoice();

        $this->postJson("/api/invoices/{$id}/send")
            ->assertStatus(202)
            ->assertNoContent(202);

        self::assertCount(1, $this->fakeDriver->sent);
        self::assertSame('ada@example.com', $this->fakeDriver->sent[0]['toEmail']);
        self::assertSame($id, $this->fakeDriver->sent[0]['reference']);

        $this->getJson("/api/invoices/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'sending');

        $this->assertDatabaseHas('invoices', ['id' => $id, 'status' => 'sending']);
    }

    #[Test]
    public function returns_422_when_invoice_is_not_in_draft(): void
    {
        $id = $this->createInvoice();

        $this->postJson("/api/invoices/{$id}/send")->assertStatus(202);

        $this->postJson("/api/invoices/{$id}/send")
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Invoice can only be sent from draft status, current: sending.',
            );

        self::assertCount(1, $this->fakeDriver->sent);
        $this->assertDatabaseHas('invoices', ['id' => $id, 'status' => 'sending']);
    }

    #[Test]
    public function returns_404_when_invoice_is_missing(): void
    {
        $unknownId = Uuid::uuid4()->toString();

        $this->postJson("/api/invoices/{$unknownId}/send")
            ->assertStatus(404)
            ->assertJsonPath('message', "Invoice with id {$unknownId} was not found.");

        self::assertSame([], $this->fakeDriver->sent);
    }

    #[Test]
    public function returns_404_when_id_is_not_a_uuid(): void
    {
        $this->postJson('/api/invoices/not-a-uuid/send')
            ->assertStatus(404);

        self::assertSame([], $this->fakeDriver->sent);
    }

    private function createInvoice(): string
    {
        return $this->postJson('/api/invoices', [
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'ada@example.com',
            'product_lines' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'unit_price' => 300],
            ],
        ])->assertStatus(201)->json('id');
    }
}
