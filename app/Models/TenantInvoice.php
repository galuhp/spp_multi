<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'period',
    'transaction_count',
    'fee_per_transaction',
    'total_amount',
    'status',
    'doku_invoice_number',
    'payment_url',
    'due_date',
    'paid_at',
    'notes',
])]
class TenantInvoice extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_count' => 'integer',
            'fee_per_transaction' => 'integer',
            'total_amount' => 'integer',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        if ($this->isPaid() || $this->total_amount <= 0) {
            return false;
        }

        if ($this->status === 'overdue') {
            return true;
        }

        return $this->due_date !== null && $this->due_date->lt(today());
    }
}
