<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CrashAlert extends Notification
{
    use Queueable;

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
        return [
            'title'   => '🚨 Crash Detected!',
            'body'    => 'Vehicle ' . $this->crashData['plate_number'] . ' reported a crash.',
            'type'    => 'crash',
            'id'      => $this->crashData['id'], 
            'time'    => now()
        ];
    }
}