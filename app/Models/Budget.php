<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

/**
 * Modello Budget che utilizza il database tenant.
 * 
 * Il trait UsesTenantConnection garantisce che questo modello
 * operi sempre nel database del tenant corrente, fornendo
 * automaticamente l'isolamento dei dati tra tenant.
 */
class Budget extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'year',
        'category',
        'allocated',
    ];

    public function contractCategory()
    {
        return $this->belongsTo(ContractCategory::class, 'category');
    }
}
