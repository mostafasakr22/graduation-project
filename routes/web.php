use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

Route::get('/debug-mqtt', function () {
    return [
        'PHP_Version' => PHP_VERSION,
        'Mqtt_Library_Installed' => is_dir(base_path('vendor/php-mqtt')),
        'Facade_Class_Exists' => class_exists(\PhpMqtt\Laravel\Facades\Mqtt::class),
        'Config_Loaded' => config('mqtt.connections.default.host'),
        'App_URL' => config('app.url'),
        'Environment' => app()->environment(),
    ];
});