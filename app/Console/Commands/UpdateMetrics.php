<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;
use App\Device;
use App\Metric;
use App\MetricType;
use App\MetricUnit;
use App\MetricHistory;
use App\MetricConfiguration;

class UpdateMetrics extends Command
{
    protected $signature = 'update:metrics';
    protected $description = 'Update metrics for given devices';

    public static $NORMAL = 1;
    public static $CRITICAL = 3;
    
    public static $RANGE = 1;
    public static $DELTA = 2;
    
    public static $NEGATIVE_VARIATION = 0;
    public static $POSITIVE_VARIATION = 1;
    
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $devices = Device::where('active', 1)->whereNotNull('less_id')->get();
        $client = new Client(['headers' => Config::get('less.headers')]);
        
        foreach ($devices as $device) {
            
            try {
                $response = $client->get(Config::get('less.api.metrics') . '?less_id=' . $device->less_id);
            } catch(RequestException $e) {
                $this->error("DEVICE :: " . $device->description . " not found");
                continue;
            }
            
            $metrics = json_decode($response->getBody());

            if (empty($metrics)) {
                $this->error("DEVICE :: " . $device->description . " without metrics");
                continue;
            }
            
            foreach ($metrics as $metricName => $metricValue) {
             
                if ($metricName == 'battery') {
                    
                    foreach ($metricValue as $battteryMetricName => $batteryMetricValue) {
                        
                        $this->processMetric($device, 'battery_' . $battteryMetricName, $batteryMetricValue);
                    }
                    
                } else {
                    
                    $this->processMetric($device, $metricName, $metricValue);
                }
            }
            
            $this->info("DEVICE :: " . $device->description . " updated");
        }
    }
    
    private function processMetric($device, $metricName, $metricValue) {
        
        if ((! isset($metricValue->derived) && ! isset($metricValue->value)) 
                || ! isset($metricValue->unit)) {return;}
        
        if (! isset($metricValue->derived)) {
            $metricValue->derived = $metricValue->value;
        }        
                
        $metricType = MetricType::firstOrCreate([
            'device_type' => 1, 
            'description' => $metricName
            ]);

        $metricUnit = MetricUnit::firstOrCreate([
            'description' => $metricValue->unit
        ]);

        $metricStatus = $this->getMetricCriticity($device->id, $metricType->id, $metricValue->derived, $metricUnit->id);
        
        $metric = Metric::updateOrCreate([
                'device' => $device->id, 
                'metric_type' => $metricType->id, 
                'metric_unit' => $metricUnit->id
            ], [
                'metric_status' => $metricStatus,
                'amount' => $metricValue->derived
            ]);
        
        MetricHistory::create([
                'device' => $device->id, 
                'metric_type' => $metricType->id, 
                'metric_status' => $metricStatus,
                'metric_unit' => $metricUnit->id,
                'amount' => $metricValue->derived
            ]);
        
        $this->info("METRIC :: $metricName ($metricValue->derived $metricValue->unit) updated for DEVICE $device->description");
        
        return $metric;
    }
    
    private function getMetricCriticity($device, $metricType, $metricValue, $metricUnit) {
        
        $metricStatus = $this->getDeltaMetricCriticity($device, $metricType, $metricValue, $metricUnit);
        
        if ($metricStatus === static::$CRITICAL) {
            return $metricStatus;
        }
        
        return $this->getRangeMetricCriticity($metricType, $metricValue);
    }
    
    private function getDeltaMetricCriticity($device, $metricType, $metricValue, $metricUnit){
        
        $metricStatus = static::$NORMAL;
        
        $previousValue = MetricHistory::select('amount')
                ->where('device', $device)
                ->where('metric_type', $metricType)
                ->where('metric_unit', $metricUnit)
                ->orderBy('created_at', 'desc')
                ->first();
        
        if (isset($previousValue->amount)) {
            
            $deltaConfigurations = MetricConfiguration::where('id_metric_type', $metricType)
                ->where('id_metric_configuration_type', static::$DELTA)
                ->get();
            
            foreach ($deltaConfigurations as $deltaConfiguration) {
                
                if (($deltaConfiguration->rangeA == static::$NEGATIVE_VARIATION && 
                        ($previousValue->amount - $metricValue) > $deltaConfiguration->rangeB) ||
                    ($deltaConfiguration->rangeA == static::$POSITIVE_VARIATION && 
                        ($metricValue - $previousValue->amount) > $deltaConfiguration->rangeB)) {
                    
                    $metricStatus = static::$CRITICAL;
                    break;
                }
            }
        }
        
        return $metricStatus;
    }
    
    private function getRangeMetricCriticity($metricType, $metricValue){
        
        $metricStatus = MetricConfiguration::select('id_metric_status')
                ->where('id_metric_type', $metricType)
                ->where('id_metric_configuration_type', static::$RANGE)
                ->where('rangeA', '<=', $metricValue)
                ->where('rangeB', '>=', $metricValue)
                ->first();
        
        if (isset($metricStatus->id_metric_status)) {return $metricStatus->id_metric_status;}
        
        return static::$NORMAL;
    }
}
