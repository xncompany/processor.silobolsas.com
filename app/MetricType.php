<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MetricType extends Model
{
    const UPDATED_AT = null;
    
    protected $table = 'metric_types';
    
    protected $fillable = ['device_type', 'description'];
}
