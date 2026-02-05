<?php

namespace Zkriahac\Paynkolay\Models;

use Illuminate\Database\Eloquent\Model;

class SavedCard extends Model
{
    protected $table = 'paynkolay_saved_cards';
    
    protected $fillable = [
        'user_id',
        'customer_key',
        'tran_id',
        'token',
        'card_alias',
        'card_holder_name',
        'card_number_masked',
        'card_brand',
        'expiry_month',
        'expiry_year',
        'is_default',
        'is_active',
    ];
    
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}