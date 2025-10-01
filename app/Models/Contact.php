<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

/**
 * Modello Contact che utilizza il database tenant.
 * 
 * Il trait UsesTenantConnection garantisce che questo modello
 * operi sempre nel database del tenant corrente.
 */
class Contact extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'supplier_id',
        'name',
        'role',
        'email',
        'phone',
        'notes',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
