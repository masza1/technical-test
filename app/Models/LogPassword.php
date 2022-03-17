<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogPassword extends Model
{
    protected $fillable = [
        'user_id',
        'password'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
