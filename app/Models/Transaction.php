<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'description',
        'old_saldo',
        'amount',
    ];

    public function customer(){
        return $this->belongsTo(Customer::class);
    }
}
