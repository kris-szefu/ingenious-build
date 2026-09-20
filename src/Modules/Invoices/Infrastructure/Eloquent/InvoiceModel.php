<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InvoiceModel extends Model
{
    protected $table = 'invoices';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_name',
        'customer_email',
        'status',
    ];

    /** @return HasMany<ProductLineModel, $this> */
    public function productLines(): HasMany
    {
        return $this->hasMany(ProductLineModel::class, 'invoice_id');
    }
}
