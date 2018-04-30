<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
        $metricsNotAlerted = MetricHistory::where('metric_status', static::$CRITICAL)
                ->where('alert_sent', static::$NOTSENT)
                ->get();
        
        foreach ($metricsNotAlerted as $metricToAlert) {
            
            //$user = Obtener la data de usuario; 
            
            Mail::send('emails.alert', 
                        ['metric_status' => static::$CRITICAL,],
                        function ($m) use ($user) {

                $m->from('noreply@smartiumtech.com', 'Smartium');
                $m->to($user->email, $user->name)->subject('[Smartium] Alerta detectada en una de sus lanzas');
            });
             
            MetricHistory::update(['id' => $metricToAlert->id], ['alert_sent' => static::$SENT]);
        }        
    }
}
