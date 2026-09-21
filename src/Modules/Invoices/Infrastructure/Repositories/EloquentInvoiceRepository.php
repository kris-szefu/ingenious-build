<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceModel;
use Modules\Invoices\Infrastructure\Eloquent\ProductLineModel;
use Ramsey\Uuid\Uuid;

final readonly class EloquentInvoiceRepository implements InvoiceRepository
{
    public function find(InvoiceId $id): ?Invoice
    {
        $model = InvoiceModel::query()
            ->with('productLines')
            ->find($id->value);

        if (! $model instanceof InvoiceModel) {
            return null;
        }

        return Invoice::reconstitute(
            InvoiceId::fromString($model->id),
            $model->customer_name,
            CustomerEmail::fromString($model->customer_email),
            StatusEnum::from($model->status),
            array_values(
                $model->productLines
                    ->map(static fn (ProductLineModel $line): ProductLine => new ProductLine(
                        name: $line->name,
                        quantity: $line->quantity,
                        unitPrice: $line->price,
                    ))
                    ->all(),
            ),
        );
    }

    public function save(Invoice $invoice): void
    {
        DB::transaction(static function () use ($invoice): void {
            InvoiceModel::query()->updateOrCreate(
                ['id' => $invoice->id->value],
                [
                    'customer_name' => $invoice->customerName,
                    'customer_email' => $invoice->customerEmail->value,
                    'status' => $invoice->status->value,
                ],
            );

            ProductLineModel::query()
                ->where('invoice_id', $invoice->id->value)
                ->delete();

            foreach ($invoice->productLines as $line) {
                ProductLineModel::query()->create([
                    'id' => Uuid::uuid4()->toString(),
                    'invoice_id' => $invoice->id->value,
                    'name' => $line->name,
                    'quantity' => $line->quantity,
                    'price' => $line->unitPrice,
                ]);
            }
        });
    }
}
