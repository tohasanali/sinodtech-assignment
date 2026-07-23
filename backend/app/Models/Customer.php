<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'employee_id', 'last_contacted_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_contacted_at' => 'datetime',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * At least one purchase ever, and the most recent one older than the
     * configured threshold — a customer who never bought anything is new,
     * not lost.
     */
    public function scopeLost(Builder $query): Builder
    {
        $threshold = now()->subDays(config('crm.lost_customer_days'));

        return $query->whereHas('sales')
            ->whereDoesntHave('sales', fn (Builder $q) => $q->where('created_at', '>=', $threshold));
    }

    public function isLost(): bool
    {
        $lastPurchaseAt = $this->sales()->max('created_at');

        return $lastPurchaseAt !== null
            && Carbon::parse($lastPurchaseAt)->lt(now()->subDays(config('crm.lost_customer_days')));
    }

    public function wasRecentlyContacted(): bool
    {
        return $this->last_contacted_at !== null
            && $this->last_contacted_at->gt(now()->subDays(config('crm.recontact_cooldown_days')));
    }
}
