<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    protected $table = 'device_types';

    protected $fillable = ['name', 'description'];
    
    const CREATED_AT = null;
    const UPDATED_AT = null;
}
