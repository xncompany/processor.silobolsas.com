<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;
use App\Device;
use App\Metric;
use App\MetricType;
use App\MetricUnit;
use App\MetricHistory;

class UpdateMetrics extends Command
{
    protected $signature = 'update:metrics';
    protected $description = 'Update metrics for given devices';

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

        $metric = Metric::updateOrCreate([
                'device' => $device->id, 
                'metric_type' => $metricType->id, 
                'metric_unit' => $metricUnit->id
            ], [
                'amount' => $metricValue->derived
            ]);

        MetricHistory::create([
                'device' => $device->id, 
                'metric_type' => $metricType->id, 
                'metric_unit' => $metricUnit->id,
                'amount' => $metricValue->derived
            ]);
        
        $this->info("METRIC :: $metricName ($metricValue->derived $metricValue->unit) updated for DEVICE $device->description");
        
        return $metric;
    }
}
