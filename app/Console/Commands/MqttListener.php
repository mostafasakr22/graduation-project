<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trip;
use App\Models\Trip_location;
use App\Models\Crash;
use App\Models\Vehicle;
use App\Notifications\CrashAlert;
use Carbon\Carbon;

class MqttListener extends Command
{
    protected $keepRunning = true;
    protected $signature = 'mqtt:listen';
    protected $description = 'Comprehensive and Resilient MQTT Listener for Black-Box';
    public function handle()
    {

        $this->info('🚀 Black-Box MQTT Listener is starting...');

        // حلقة تكرار لا نهائية لضمان استمرار العمل حتى لو سقط البروكر
        while (true) {
            try {
                $this->info('📡 Attempting to connect to MQTT Broker...');

                // استخدام الفاساد الذي يعمل عندك
                $mqtt = \PhpMqtt\Client\Facades\MQTT::connection();

                // الاشتراك في التوبيك مع استخدام Wildcards (+) لسماع كل العربيات وكل الحالات
                $mqtt->subscribe('blackbox/v1/car/+/+', function (string $topic, string $message) {
                    $this->processIncomingMessage($topic, $message);
                }, 0);

                $this->info('✅ Connected! Waiting for data...');

                // تشغيل حلقة الاستماع
                $mqtt->loop(true);

            } catch (\Exception $e) {
                $this->error('⚠️ Connection Error: ' . $e->getMessage());
                $this->info('🔄 Retrying in 5 seconds...');

                // انتظار 5 ثواني قبل إعادة المحاولة لمنع الضغط على المعالج
                sleep(5);
            }
        }
    }

    /**
     * دالة معالجة الرسائل القادمة وتوزيعها
     */
    private function processIncomingMessage($topic, $message)
    {
        $this->info("📩 New Message on [$topic] -> $message");

        $parts = explode('/', $topic);
        if (count($parts) < 5)
            return;

        $vehicle_id = $parts[3];
        $action = $parts[4]; // telemetry, safety, or trip

        $data = json_decode($message, true);
        if (!$data) {
            $this->warn("❌ Invalid JSON received");
            return;
        }

        // توجيه الداتا بناءً على الـ Action
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
    }

    // 1. معالجة الحوادث والأمان
    private function handleSafety($vehicle_id, $data)
    {
        $this->info("⚠️ Safety event for car $vehicle_id");

        $activeTrip = Trip::where('vehicle_id', $vehicle_id)->where('status', 'ongoing')->latest()->first();

        $crash = Crash::create([
            'trip_id' => $activeTrip ? $activeTrip->id : null,
            'vehicle_id' => $vehicle_id,
            'crashed_at' => now(),
            'latitude' => $data['lat'] ?? '0.0',
            'longitude' => $data['long'] ?? '0.0',
            'location' => $data['location'] ?? null,
            'type' => $data['type'] ?? 'major_crash', // قيمة افتراضية لو نسي الهاردوير يبعتها
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

        //لما يحصل حادثه شديده يلغي الرحله
        if ($crash->type === 'major_crash' && $activeTrip) {
            $activeTrip->update([
                'end_time' => now(),
                'end_lat' => $crash->latitude,
                'end_lng' => $crash->longitude,
                'status' => 'completed'
            ]);
            $this->info("🚨 Trip $activeTrip->id has been AUTO-ENDED due to a major crash.");
        }

        // إرسال الإشعارات
        $alertTypes = ['major_crash', 'fuel_leak', 'early_warning'];
        if (in_array($crash->type, $alertTypes)) {
            $vehicle = Vehicle::with('user')->find($vehicle_id);
            if ($vehicle && $vehicle->user) {
                $vehicle->user->notify(new CrashAlert($crash));
                $this->info("🔔 Notification sent!");
            }
        }
    }

    // 2. معالجة التتبع اللحظي
    private function handleTelemetry($vehicle_id, $data)
    {
        $activeTrip = Trip::where('vehicle_id', $vehicle_id)->where('status', 'ongoing')->latest()->first();
        if ($activeTrip) {
            Trip_location::create([
                'trip_id' => $activeTrip->id,
                'latitude' => $data['lat'] ?? '0.0',
                'longitude' => $data['long'] ?? '0.0',
                'speed' => $data['speed'] ?? 0,
                'heading' => $data['heading'] ?? null,
                'rpm' => $data['rpm'] ?? null,
                'fuel_level' => $data['fuel'] ?? null,
                'coolant_temp' => $data['temp'] ?? null,
                'sats' => $data['sats'] ?? null,
            ]);
            // $this->info("📍 Location logged");
        }
    }

    // 3. معالجة بداية ونهاية الرحلة
    private function handleTripLifecycle($vehicle_id, $data)
    {
        $event = $data['event'] ?? '';

        if ($event == 'engine_on') {
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
        } elseif ($event == 'engine_off') {
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

    // دالة موحدة لحساب المسافة والسرعة وقفل الرحلة 
    public function finalizeTrip($trip, $endLat, $endLng)
    {
        $endTime = Carbon::now();
        $startTime = Carbon::parse($trip->start_time);

        // حساب الساعات للسرعة المتوسطة
        $hours = $startTime->diffInMinutes($endTime) / 60;

        // حساب المسافة (بتنادي الدالة اللي في الـ Model)
        $calculatedDistance = $trip->calculateDistance();

        // حساب السرعة المتوسطة
        $avgSpeed = ($hours > 0 && $calculatedDistance > 0) ? ($calculatedDistance / $hours) : 0;

        // حساب أقصى سرعة من جدول المواقع
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

        $this->info("🏁 Trip Finalized: {$calculatedDistance}km");
    }
}