<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MqttListener extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Test MQTT Listening';

   public function handle()
{
    $this->info('Connecting directly to MQTT Broker...');

    // بننادي المكتبة الأم مباشرة (بدون استخدام Facades)
    // المسار ده موجود في أي نسخة من المكتبة
    $host = 'broker.emqx.io'; 
    $port = 1883;
    $clientId = 'azure_client_' . rand(1, 999);

    try {
        // استخدام الكلاس الخام مباشرة
        $mqtt = new \PhpMqtt\Client\MqttClient($host, $port, $clientId);
        $mqtt->connect();
        
        $this->info("Connected successfully to $host");

        $mqtt->subscribe('v1/devices/me/telemetry', function ($topic, $message) {
            echo "Data Received: $message \n";
        }, 0);

        $mqtt->loop(true);

    } catch (\Exception $e) {
        $this->error("Connection Failed: " . $e->getMessage());
    }
}
}