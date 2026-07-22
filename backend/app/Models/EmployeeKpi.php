<?php

namespace App\Models;

use Database\Factories\EmployeeKpiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeKpi extends Model
{
    /** @use HasFactory<EmployeeKpiFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'customer_id', 'sale_id', 'points'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
