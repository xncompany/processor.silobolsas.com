<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    const UPDATED_AT = null;
    
    protected $casts = [
        'active'   => 'boolean'
    ];
    
    protected $fillable = ['less_id', 'silobag', 'description', 'type', 'active', 'activated_at'];
}
