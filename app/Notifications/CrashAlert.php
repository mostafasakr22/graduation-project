<?php

namespace App\Notifications;

use App\Models\Crash;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CrashAlert extends Notification
{
    use Queueable;

    public $crash;
    public $crashData; // بيانات الحادثة

    public function __construct($crashData)
    {
        $this->crashData = $crashData;
    }

    // بنقوله خزن الإشعار في الداتابيز
    public function via($notifiable)
    {
        return ['database']; 
    }

    // شكل البيانات اللي هتتخزن في الداتابيز
    public function toDatabase($notifiable)
    {
        
    $vehicleInfo = $this->crash->vehicle->plate_number ?? 'ID #' . $this->crash->vehicle_id;
        return [
            'title'        => '🚨 Crash Detected!',
            'body'         => "Vehicle ({$vehicleInfo}) reported a crash.",
            'crash_id'     => $this->crash->id,
            'severity'     => $this->crash->severity,
            'time'         => now(),
            'icon'         => 'crash_alert'
        ];
    }
}