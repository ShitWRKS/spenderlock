<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

/**
 * Modello ContractCategory che utilizza il database tenant.
 */
class ContractCategory extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'name',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
