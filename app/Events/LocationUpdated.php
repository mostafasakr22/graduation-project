<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // <--- أهم سطر
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    // بنستقبل الداتا اللي عايزين نبعتها
    public function __construct($data)
    {
        $this->data = $data;
    }

    // اسم القناة: trip.50 (حيث 50 هو رقم الرحلة)
    public function broadcastOn()
    {
        return new Channel('trip.' . $this->data['trip_id']);
    }

    // اسم الحدث اللي الفلاتر بيسمع عليه
    public function broadcastAs()
    {
        return 'location-updated';
    }
}