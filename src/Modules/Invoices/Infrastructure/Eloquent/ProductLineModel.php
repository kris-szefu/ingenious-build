<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class ProductLineModel extends Model
{
    protected $table = 'invoice_product_lines';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'invoice_id',
        'name',
        'quantity',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'integer',
        ];
    }
}
