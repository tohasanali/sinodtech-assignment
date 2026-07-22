<?php

namespace App\Models;

use Database\Factories\BranchStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class BranchStock extends Model
{
    /** @use HasFactory<BranchStockFactory> */
    use AsPivot, HasFactory;

    protected $table = 'branch_stocks';

    protected $fillable = ['branch_id', 'product_id', 'quantity'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
