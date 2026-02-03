<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrashAlert extends Notification
{
    use Queueable;

    public $crash; // بيانات الحادثة

    public function __construct($crash)
    {
        $this->crash = $crash;
    }

    public function via($notifiable)
    {
        return ['database']; // التخزين في الداتابيز فقط
    }

    // شكل البيانات اللي هتتخزن
    public function toDatabase($notifiable)
    {
        return [
            'title'        => '🚨 Accident Alert!',
            'body'         => 'A crash has been detected for vehicle: ' . ($this->crash->vehicle->plate_number ?? 'Unknown'),
            'crash_id'     => $this->crash->id,
            'severity'     => $this->crash->severity,
            'time'         => now(),
            'icon'         => 'crash_alert'
        ];
    }
}