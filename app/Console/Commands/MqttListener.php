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
                $this->info("Received: " . $message);

                $data = json_decode($message, true);

                if ($data) {
                    try {
                        // بنحاول نسجل في الداتابيز
                        \App\Models\Crash::create([
                            'vehicle_id' => $data['vehicle_id'] ?? 1,
                            'severity' => isset($data['temp']) && $data['temp'] > 50 ? 'High' : 'Normal',
                            'details' => $data['status'] ?? 'MQTT Test',
                            'location' => '30.0444, 31.2357',
                        ]);

                        $this->info("✅ Success: Data saved to database.");

                    } catch (\Exception $e) {
                        // السطر ده هيطبع لك في الـ SSH لو الداتابيز رفضت وليه رفضت
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