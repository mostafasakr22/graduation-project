<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MqttListener extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Test MQTT Listening';

    public function handle()
    {
        $this->info('Waiting for data from MQTT X...');

    $mqtt = \PhpMqtt\Laravel\Facades\Mqtt::connection();


        // توبيك تجريبي
        $topic = 'graduation/test/data';

        $mqtt->subscribe($topic, function (string $topic, string $message) {
            $this->info("------------------------------------");
            $this->info("Message Received: " . $message);
            $this->info("------------------------------------");
        }, 0);

        $mqtt->loop(true);
    }
}