<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\MetricHistory;
use Mail;

class SendAlert extends Command
{
    protected $signature = 'send:alert';
    protected $description = 'Send emails when a critical metric is detected';
    
    public static $CRITICAL = 3;    
    public static $NOTSENT = 0;
    public static $SENT = 1;
    
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {   
        $metricsNotAlerted = DB::table('metrics_history')
                ->where('metric_status', '=', static::$CRITICAL)
                ->where('alert_sent', '=', static::$NOTSENT)
                ->join('metric_types', 'metrics_history.metric_type', '=', 'metric_types.id')
                ->join('metric_units', 'metrics_history.metric_unit', '=', 'metric_units.id')
                ->join('metric_status', 'metrics_history.metric_status', '=', 'metric_status.id')
                ->select('metrics_history.*', 
                        'metric_types.description as metric_type_description',
                        'metric_units.description as metric_unit_description',
                        'metric_status.description as metric_status_description')
                ->get();
        
        foreach ($metricsNotAlerted as $metricToAlert) {
            
            $user = DB::table('devices')
                    ->where('devices.id', '=', $metricToAlert->device)
                    ->join('silobags', 'devices.silobag', '=', 'silobags.id')
                    ->join('lands', 'silobags.land', '=', 'lands.id')
                    ->join('users', 'lands.user', '=', 'users.id')
                    ->select('users.email', 
                            'lands.description as land', 
                            'silobags.description as silobag', 
                            'devices.description as device')
                    ->first();
            
            Mail::send('emails.alert', 
                        [
                            'metric_status' => $metricToAlert->metric_status_description,
                            'metric_type' => $metricToAlert->metric_type_description,
                            'metric_unit' => $metricToAlert->metric_unit_description,
                            'metric_amount' => $metricToAlert->amount,
                            'device' => $user->device,
                            'silobag' => $user->silobag,
                            'land' => $user->land,
                        ],
                        function ($m) use ($user) {

                //$m->from('noreply@smartiumtech.com', 'Smartium');
                //$m->to($user->email, $user->name);
                //$m->subject('[Smartium] Alerta detectada en una de sus lanzas');
                
                $m->from('german.scoglio@gmail.com', 'Smartium');
                $m->to('german.scoglio@gmail.com');
                $m->subject('[Smartium] Alerta detectada en una de sus lanzas');
            });
            
            $this->info("MAIL SENT :: to $user->email for metric $metricToAlert->id");
            
            MetricHistory::where('id', $metricToAlert->id)->update(['alert_sent' => static::$SENT]);
        }        
    }
}
