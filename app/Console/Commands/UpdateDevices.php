<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use App\Device;
use App\DeviceType;

class UpdateDevices extends Command
{
    protected $signature = 'update:devices';
    protected $description = 'Update devices';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = new Client(['headers' => Config::get('less.headers')]);
        $response = $client->get(Config::get('less.api.devices'));
        
        if ($response->getStatusCode() == 200) {
            $silobags = json_decode($response->getBody());
            
            foreach ($silobags as $silobag) {
                
                foreach ($silobag->device_list as $device) {
                    
                    $deviceType = DeviceType::firstOrCreate(['name' => $device->device_type]);
                    
                    Device::updateOrCreate(
                        ['less_id' => $device->less_id],
                        [
                            'description' => $device->name,
                            'type' => $deviceType->id,
                            'active' => 1,
                            'activated_at' => date("Y-m-d H:i:s", ($device->activated_at)/1000)
                        ]     
                    );
                    
                    $this->info("DEVICE :: " . $device->name . " updated");
                }
            }   
        }
    }
}
