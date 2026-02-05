<?php

namespace Zkriahac\Paynkolay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $table = 'paynkolay_transactions';
    
    protected $fillable = [
        'reference_code',
        'client_ref_code',
        'user_id',
        'amount',
        'currency',
        'status',
        'transaction_type',
        'installment',
        'use_3d',
        'card_holder_name',
        'card_number',
        'card_brand',
        'customer_key',
        'ip_address',
        'request_data',
        'response_data',
        'callback_data',
        'paid_at',
        'refunded_at',
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'use_3d' => 'boolean',
        'request_data' => 'array',
        'response_data' => 'array',
        'callback_data' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
    
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }
    
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}