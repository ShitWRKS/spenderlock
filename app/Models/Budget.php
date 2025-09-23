<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
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
