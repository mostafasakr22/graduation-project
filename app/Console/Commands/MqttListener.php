<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trip;
use App\Models\Trip_location;
use App\Models\Crash;
use App\Models\Vehicle;
use App\Notifications\CrashAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MqttListener extends Command
{
    protected $keepRunning = true;
    protected $signature = 'mqtt:listen';
    protected $description = 'Comprehensive and Resilient MQTT Listener for Black-Box';

    public function handle()
    {
        $this->info('🚀 Black-Box MQTT Listener is starting...');

        while (true) {
            try {
                $this->info('📡 Attempting to connect to MQTT Broker...');
                $mqtt = \PhpMqtt\Client\Facades\MQTT::connection();

                $mqtt->subscribe('blackbox/v1/car/+/+', function (string $topic, string $message) {
                    $this->processIncomingMessage($topic, $message);
                }, 0);

                $this->info('✅ Connected! Waiting for data...');
                $mqtt->loop(true);

            } catch (\Exception $e) {
                $this->error('⚠️ Connection Error: ' . $e->getMessage());
                sleep(5);
            }
        }
    }

    private function processIncomingMessage($topic, $message)
    {
        $this->info("📩 New Message on [$topic]");

        $parts = explode('/', $topic);
        if (count($parts) < 5)
            return;

        $vehicle_id = $parts[3];
        $action = $parts[4];

        if (!is_numeric($vehicle_id)) {
            $this->error("❌ Invalid vehicle ID received: $vehicle_id");
            return;
        }

        $data = json_decode($message, true);
        if (!$data) {
            $this->warn("❌ Received message is not valid JSON");
            return;
        }

        // تنفيذ العمليات مع رصد أخطاء الداتابيز
        try {
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
        } catch (\Exception $e) {
            $this->error("❌ DATABASE FATAL ERROR: " . $e->getMessage());
        }
    }

    private function handleSafety($vehicle_id, $data)
    {
        $this->info("⚠️ Processing safety event for car $vehicle_id");

        $activeTrip = Trip::where('vehicle_id', $vehicle_id)->where('status', 'ongoing')->latest()->first();

        $crash = Crash::create([
            'trip_id' => $activeTrip ? $activeTrip->id : null,
            'vehicle_id' => $vehicle_id,
            'crashed_at' => now(),
            'latitude' => $data['lat'] ?? '0.0',
            'longitude' => $data['long'] ?? '0.0',
            'location' => $data['location'] ?? null,
            'type' => $data['type'] ?? 'major_crash',
            'severity' => $data['severity'] ?? 'low',
            'ax' => $data['ax'] ?? null,
            'ay' => $data['ay'] ?? null,
            'az' => $data['az'] ?? null,
            'yaw' => $data['yaw'] ?? null,
            'pitch' => $data['pitch'] ?? null,
            'roll' => $data['roll'] ?? null,
            'speed_before' => $data['speed'] ?? null,
            'rpm_before' => $data['rpm'] ?? null,
            'coolant_temp' => $data['temp'] ?? null,
            'fuel_level' => $data['fuel'] ?? null,
            'dtc_codes' => $data['dtc'] ?? null,
            'sats' => $data['sats'] ?? null,
        ]);

        $this->info("✅ Crash saved (ID: {$crash->id})");

        if ($crash->type === 'major_crash' && $activeTrip) {
            $this->finalizeTrip($activeTrip, $data['lat'] ?? null, $data['long'] ?? null);
            $this->info("🚨 Trip AUTO-ENDED due to crash.");
        }

        $alertTypes = ['major_crash', 'fuel_leak', 'early_warning'];
        if (in_array($crash->type, $alertTypes)) {
            $vehicle = Vehicle::with('user')->find($vehicle_id);
            if ($vehicle && $vehicle->user) {
                $vehicle->user->notify(new CrashAlert($crash));
                $this->info("🔔 Notification sent!");
            }
        }
    }

    private function handleTelemetry($vehicle_id, $data)
    {
        $activeTrip = Trip::where('vehicle_id', $vehicle_id)->where('status', 'ongoing')->latest()->first();
        if ($activeTrip) {
            Trip_location::create([
                'trip_id' => $activeTrip->id,
                'latitude' => $data['lat'] ?? '0.0',
                'longitude' => $data['long'] ?? '0.0',
                'speed' => $data['speed'] ?? 0,
                'fuel_level' => $data['fuel'] ?? null,
                'coolant_temp' => $data['temp'] ?? null,
                'rpm' => $data['rpm'] ?? null,
                'sats' => $data['sats'] ?? null,
            ]);
            $this->info("📍 Location logged for trip {$activeTrip->id}");
        } else {
            $this->warn("⚠️ Telemetry ignored: No active trip for car $vehicle_id");
        }
    }

    private function handleTripLifecycle($vehicle_id, $data)
    {
        $event = $data['event'] ?? '';

        if ($event == 'engine_on') {
            $vehicle = Vehicle::findOrFail($vehicle_id);
            Trip::create([
                'vehicle_id' => $vehicle_id,
                'driver_id' => $vehicle->driver_id,
                'start_time' => now(),
                'start_lat' => $data['lat'] ?? '0.0',
                'start_lng' => $data['long'] ?? '0.0',
                'status' => 'ongoing'
            ]);
            $this->info("🔑 Trip Started for car $vehicle_id");
        } elseif ($event == 'engine_off') {
            $trip = Trip::where('vehicle_id', $vehicle_id)->where('status', 'ongoing')->latest()->first();
            if ($trip) {
                $this->finalizeTrip($trip, $data['lat'] ?? null, $data['long'] ?? null);
                $this->info("🏁 Trip Ended for car $vehicle_id");
            }
        }
    }

    private function finalizeTrip($trip, $endLat, $endLng)
    {
        $endTime = Carbon::now();
        $startTime = Carbon::parse($trip->start_time);
        $hours = $startTime->diffInMinutes($endTime) / 60;

        $calculatedDistance = $trip->calculateDistance();
        $avgSpeed = ($hours > 0) ? ($calculatedDistance / $hours) : 0;
        $maxSpeed = Trip_location::where('trip_id', $trip->id)->max('speed') ?? 0;

        $trip->update([
            'end_time' => $endTime,
            'end_lat' => $endLat,
            'end_lng' => $endLng,
            'distance_km' => round($calculatedDistance, 2),
            'avg_speed' => round($avgSpeed, 2),
            'max_speed' => round($maxSpeed, 2),
            'status' => 'completed'
        ]);
    }
}