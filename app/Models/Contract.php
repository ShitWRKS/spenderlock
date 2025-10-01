<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ContractCategory;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

/**
 * Modello Contract che utilizza il database tenant.
 */
class Contract extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'supplier_id',
        'title',
        'contract_category_id',
        'start_date',
        'end_date',
        'renewal_mode',
        'amount_total',
        'amount_recurring',
        'frequency_months',
        'payment_type',
        'notes',
        'attachments_contract',
        'attachments_orders',
        'attachments_documents',
    ];

    protected $casts = [
        'attachments_contract' => 'array',
        'attachments_orders' => 'array',
        'attachments_documents' => 'array',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContractCategory::class, 'contract_category_id');
    }
}
