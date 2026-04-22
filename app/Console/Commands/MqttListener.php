<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;
// تأكد أن هذا الـ Use هو اللي شغال عندك (بناءً على الكود اللي بعته)

class MqttListener extends Command
{
    // الاسم اللي بنشغل بيه الأمر
    protected $signature = 'mqtt:listen';
    protected $description = 'Listen to MQTT Test Data and save to Database';

    public function handle()
    {
        $this->info('--- MQTT Test Mode Started ---');
        $this->info('Connecting to: broker.emqx.io');

        try {
            // الاتصال بالبروكر
            $mqtt = MQTT::connection();

            // التوبيك اللي بنجرب عليه في MQTT X
            $topic = 'graduation/test/data';

            $mqtt->subscribe($topic, function ($topic, $message) {
                $this->info("Received: " . $message);
                $data = json_decode($message, true);

                if ($data) {
                    try {
                        \App\Models\Crash::create([
                            'vehicle_id' => $data['vehicle_id'] ?? 1, // تأكد إن الـ ID ده موجود في جدول vehicles
                            'crashed_at' => now(),
                            'latitude' => $data['lat'] ?? '30.0444',
                            'longitude' => $data['long'] ?? '31.2357',
                            'location' => 'Cairo, Egypt',

                            // ده العمود اللي كان هيعملك المشكلة الجاية
                            // لازم تختار قيمة من: major_crash, hard_braking, road_bump, الخ
                            'type' => 'major_crash',

                            'severity' => isset($data['temp']) && $data['temp'] > 50 ? 'critical' : 'medium',

                            // قيم اختيارية (Nullable)
                            'coolant_temp' => $data['temp'] ?? null,
                            'speed_before' => $data['speed'] ?? null,
                        ]);

                        $this->info("✅ Success: Crash recorded in database!");
                    } catch (\Exception $e) {
                        // عشان لو حصل Error بسبب Foreign Key أو غيره يظهرلك هنا بوضوح
                        $this->error("❌ Database Error: " . $e->getMessage());
                    }
                }
            }, 0);
            // يخلي الكود شغال ميفصلش
            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}