<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InvoiceProductLineModel extends Model
{
    protected $table = 'invoice_product_lines';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'invoice_id',
        'name',
        'price',
        'quantity',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id', 'id');
    }
}
