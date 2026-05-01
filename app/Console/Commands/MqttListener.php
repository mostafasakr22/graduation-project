<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Laravel\Facades\Mqtt;
use App\Models\Trip;
use App\Models\Trip_location;
use App\Models\Crash;
use App\Models\Vehicle;
use App\Notifications\CrashAlert;

class MqttListener extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Comprehensive MQTT Listener for Black-Box';

    public function handle()
    {
        $this->info('🚀 Black-Box Comprehensive Listener is active...');
        $mqtt = \PhpMqtt\Client\Facades\MQTT::connection();

        // السماع لكل العمليات: telemetry, safety, trip
        $mqtt->subscribe('blackbox/v1/car/+/+', function (string $topic, string $message) {

            $parts = explode('/', $topic);
            $vehicle_id = $parts[3];
            $action = $parts[4];

            $data = json_decode($message, true);
            if (!$data) {
                $this->warn("Invalid JSON received on $topic");
                return;
            }

            switch ($action) {
                case 'telemetry':
                    $this->handleTelemetry($vehicle_id, $data);
                    break;
                case 'safety':
                    $this->handleSafety($vehicle_id, $data);
                    break;
                case 'trip':
                    $this->handleTripLifecycle($vehicle_id, $data);
                    break;
            }
        }, 0);

        $mqtt->loop(true);
    }

    // 1. معالجة بيانات الحوادث والأمان (تطابق كامل مع جدول الـ Crash)
    private function handleSafety($vehicle_id, $data)
    {
        $this->info("⚠️ Safety event detected for car $vehicle_id");

        // البحث عن الرحلة الحالية لربطها بالحادثة
        $activeTrip = Trip::where('vehicle_id', $vehicle_id)->where('status', 'ongoing')->latest()->first();

        $crash = Crash::create([
            'trip_id' => $activeTrip ? $activeTrip->id : null,
            'vehicle_id' => $vehicle_id,
            'crashed_at' => now(),
            'latitude' => $data['lat'] ?? '0.0',
            'longitude' => $data['long'] ?? '0.0',
            'location' => $data['location'] ?? null,
            'type' => $data['type']  ,
            'severity' => $data['severity'] ?? 'low',

            // بيانات حساسات الحركة (IMU)
            'ax' => $data['ax'] ?? null,
            'ay' => $data['ay'] ?? null,
            'az' => $data['az'] ?? null,
            'yaw' => $data['yaw'] ?? null,
            'pitch' => $data['pitch'] ?? null,
            'roll' => $data['roll'] ?? null,

            // بيانات المحرك والسيارة (OBD-II & GPS)
            'speed_before' => $data['speed'] ?? null,
            'rpm_before' => $data['rpm'] ?? null,
            'coolant_temp' => $data['temp'] ?? null,
            'fuel_level' => $data['fuel'] ?? null,
            'dtc_codes' => $data['dtc'] ?? null,
            'sats' => $data['sats'] ?? null,
        ]);

        // إرسال الإشعار للمالك في الحالات الخطيرة
        $alertTypes = ['major_crash', 'fuel_leak', 'early_warning'];
        if (in_array($crash->type, $alertTypes)) {
            $vehicle = Vehicle::with('user')->find($vehicle_id);
            if ($vehicle && $vehicle->user) {
                $vehicle->user->notify(new CrashAlert($crash));
                $this->info("🔔 Notification sent to {$vehicle->user->name}");
            }
        }
    }

    // 2. معالجة التتبع اللحظي (Telemetry)
    private function handleTelemetry($vehicle_id, $data)
    {
        $activeTrip = Trip::where('vehicle_id', $vehicle_id)->where('status', 'ongoing')->latest()->first();
        if ($activeTrip) {
            Trip_location::create([
                'trip_id' => $activeTrip->id,
                'latitude' => $data['lat'],
                'longitude' => $data['long'],
                'speed' => $data['speed'] ?? 0,
                'heading' => $data['heading'] ?? null,
                'rpm' => $data['rpm'] ?? null,
                'fuel_level' => $data['fuel'] ?? null,
                'coolant_temp' => $data['temp'] ?? null,
                'sats' => $data['sats'] ?? null,
            ]);
        }
    }

    // 3. معالجة دورة حياة الرحلة (Trip)
    private function handleTripLifecycle($vehicle_id, $data)
    {
        if (($data['event'] ?? '') == 'engine_on') {
            $vehicle = Vehicle::find($vehicle_id);
            Trip::create([
                'vehicle_id' => $vehicle_id,
                'driver_id' => $vehicle->driver_id ?? null,
                'start_time' => now(),
                'start_lat' => $data['lat'] ?? '0.0',
                'start_lng' => $data['long'] ?? '0.0',
                'status' => 'ongoing'
            ]);
            $this->info("🔑 Trip Started for car $vehicle_id");
        } elseif (($data['event'] ?? '') == 'engine_off') {
            $trip = Trip::where('vehicle_id', $vehicle_id)->where('status', 'ongoing')->latest()->first();
            if ($trip) {
                $trip->update([
                    'end_time' => now(),
                    'end_lat' => $data['lat'] ?? null,
                    'end_lng' => $data['long'] ?? null,
                    'status' => 'completed'
                ]);
                $this->info("🏁 Trip Ended for car $vehicle_id");
            }
        }
    }
}