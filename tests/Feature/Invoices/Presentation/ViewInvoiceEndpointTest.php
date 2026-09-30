<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class ViewInvoiceEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function returns_invoice_by_id(): void
    {
        $created = $this->postJson('/api/invoices', [
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'ada@example.com',
            'product_lines' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'unit_price' => 300],
                ['product_name' => 'Gadget', 'quantity' => 5, 'unit_price' => 100],
            ],
        ])->assertStatus(201);

        $id = $created->json('id');

        $response = $this->getJson("/api/invoices/{$id}");

        $response->assertStatus(200)
            ->assertExactJson([
                'id' => $id,
                'status' => 'draft',
                'customer_name' => 'Ada Lovelace',
                'customer_email' => 'ada@example.com',
                'product_lines' => [
                    [
                        'product_name' => 'Widget',
                        'quantity' => 2,
                        'unit_price' => 300,
                        'total_unit_price' => 600,
                    ],
                    [
                        'product_name' => 'Gadget',
                        'quantity' => 5,
                        'unit_price' => 100,
                        'total_unit_price' => 500,
                    ],
                ],
                'total_price' => 1100,
            ]);
    }

    #[Test]
    public function returns_404_when_invoice_is_missing(): void
    {
        $unknownId = Uuid::uuid4()->toString();

        $this->getJson("/api/invoices/{$unknownId}")
            ->assertStatus(404)
            ->assertJsonPath('message', "Invoice with id {$unknownId} was not found.");
    }

    #[Test]
    public function returns_404_when_id_is_not_a_uuid(): void
    {
        $this->getJson('/api/invoices/not-a-uuid')
            ->assertStatus(404);
    }
}
