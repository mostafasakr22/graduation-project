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
        $title = '⚠️ Safety Alert!';
        $reason = 'Unknown Event';
        $icon = 'warning_icon';

        if ($this->crash->type === 'major_crash') {
            $title = '🚨 CRITICAL CRASH DETECTED!';
            $reason = 'Severe Collision or Rollover (SOS)';
            $icon = 'sos_icon';
        } elseif ($this->crash->type === 'hard_braking') {
            $title = '🛑 Hard Braking Detected';
            $reason = 'Sudden and harsh stop';
        } elseif ($this->crash->type === 'aggressive_turn') {
            $title = '🔄 Aggressive Turn Detected';
            $reason = 'Sharp turn at high speed';
        } elseif ($this->crash->type === 'road_bump') {
            $title = '⚠️ Severe Road Bump';
            $reason = 'Hit a bump or pothole violently';
        }

        
        $carDetails = $this->crash->vehicle->make . ' (' . $this->crash->vehicle->plate_number . ')';
        $driverName = $this->crash->vehicle->driver->name ?? 'Unknown Driver';

        $crashTime = $this->crash->crashed_at->format('h:i A'); 
        $crashDate = $this->crash->crashed_at->format('Y-m-d'); 

        
        return [
            'title'        => $title,
            'message'      => "Vehicle {$carDetails} driven by {$driverName} reported a {$reason}.",
            'icon'         => $icon,

            'details'      => [
                'driver_name'  => $driverName,
                'vehicle_info' => $carDetails,
                'reason'       => $reason,
                'time'         => $crashTime,
                'date'         => $crashDate,
            ],

            'telematics'   => [
                'crash_id'     => $this->crash->id,
                'latitude'     => $this->crash->latitude,
                'longitude'    => $this->crash->longitude,
                'speed_before' => $this->crash->speed_before . ' km/h',
                'severity'     => $this->crash->severity, // critical, medium, low
            ]
        ];
    }
}