<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InvoiceModel extends Model
{
    protected $table = 'invoices';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'customer_name',
        'customer_email',
        'status',
    ];

    public function productLines(): HasMany
    {
        return $this->hasMany(InvoiceProductLineModel::class, 'invoice_id', 'id');
    }
}
