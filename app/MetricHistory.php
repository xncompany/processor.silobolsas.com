<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MetricHistory extends Model
{
    const UPDATED_AT = null;
    
    protected $table = 'metrics_history';
    protected $fillable = ['device', 'metric_type', 'metric_status', 'metric_unit', 'amount', 'alert_sent'];
    
    public function metric_type()
    {
        return $this->hasOne('App\MetricType', 'id', 'metric_type');
    }
    
    public function metric_status()
    {
        return $this->hasOne('App\MetricStatus', 'id', 'metric_status');
    }
    
    public function metric_unit()
    {
        return $this->hasOne('App\MetricUnit', 'id', 'metric_unit');
    }
}
