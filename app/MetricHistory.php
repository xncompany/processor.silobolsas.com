<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MetricHistory extends Model
{
    const UPDATED_AT = null;
    protected $table = 'metrics_history';
    protected $fillable = ['device', 'metric_type', 'metric_status', 'metric_unit', 'amount'];
}
