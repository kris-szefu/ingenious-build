<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateInvoiceEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function creates_invoice_with_lines(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'ada@example.com',
            'product_lines' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'unit_price' => 300],
                ['product_name' => 'Gadget', 'quantity' => 5, 'unit_price' => 100],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('customer_name', 'Ada Lovelace')
            ->assertJsonPath('customer_email', 'ada@example.com')
            ->assertJsonPath('total_price', 1100)
            ->assertJsonPath('product_lines.0.product_name', 'Widget')
            ->assertJsonPath('product_lines.1.product_name', 'Gadget');

        $this->assertDatabaseHas('invoices', [
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'ada@example.com',
            'status' => 'draft',
        ]);
    }

    #[Test]
    public function creates_invoice_without_lines(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer_name' => 'Grace Hopper',
            'customer_email' => 'grace@example.com',
            'product_lines' => [],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('total_price', 0)
            ->assertExactJson([
                'id' => $response->json('id'),
                'status' => 'draft',
                'customer_name' => 'Grace Hopper',
                'customer_email' => 'grace@example.com',
                'product_lines' => [],
                'total_price' => 0,
            ]);
    }

    #[Test]
    public function rejects_invalid_payload(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer_name' => '',
            'customer_email' => 'not-an-email',
            'product_lines' => [[
                'product_name' => 'Widget',
                'quantity' => 0,
                'unit_price' => 0,
            ]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'customer_name',
                'customer_email',
                'product_lines.0.quantity',
                'product_lines.0.unit_price',
            ]);
    }
}

