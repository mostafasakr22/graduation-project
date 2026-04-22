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

            $mqtt->subscribe($topic, function (string $topic, string $message) {
                // 1. عرض الرسالة في الـ SSH عشان تتابعها
                $this->info("------------------------------------");
                $this->info("Received at " . now()->toTimeString() . ": " . $message);

                // 2. تحويل الرسالة (JSON) لـ Array
                $data = json_decode($message, true);

                if ($data) {
                    // 3. تخزين الداتا في الداتابيز
                    // تأكد إن عندك موديل اسمه Crash والأعمدة دي موجودة
                    \App\Models\Crash::create([
                        'vehicle_id' => $data['vehicle_id'] ?? 1,
                        'severity'   => isset($data['temp']) && $data['temp'] > 50 ? 'High' : 'Normal',
                        'details'    => $data['status'] ?? 'MQTT Test Message',
                        'location'   => '30.0444, 31.2357', // موقع افتراضي للتجربة
                    ]);

                    $this->info("✅ Success: Data saved to database.");
                } else {
                    $this->warn("⚠️ Warning: Received data is not valid JSON.");
                }
                $this->info("------------------------------------");
            }, 0);

            // يخلي الكود شغال ميفصلش
            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}