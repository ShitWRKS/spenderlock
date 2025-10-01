<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

/**
 * Modello Supplier che utilizza il database tenant.
 */
class Supplier extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'name',
        'vat_number',
        'fiscal_code',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
