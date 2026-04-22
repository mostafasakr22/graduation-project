<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttListener extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Test MQTT';

    public function handle()
    {
        $this->info('Waiting for data...');

        $mqtt = MQTT::connection();
        
        $topic = 'graduation/test/data';

        $mqtt->subscribe($topic, function (string $topic, string $message) {
            $this->info("Received: " . $message);
        }, 0);

        $mqtt->loop(true);
    }
}