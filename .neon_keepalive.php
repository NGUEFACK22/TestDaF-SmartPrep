<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$interval = 20;
echo 'keepalive started (ping every ' . $interval . 's)' . PHP_EOL;
while (true) {
    try {
        Illuminate\Support\Facades\DB::selectOne('select 1');
        error_log(date('H:i:s') . ' neon ping OK');
    } catch (Throwable $e) {
        error_log(date('H:i:s') . ' neon ping FAIL: ' . $e->getMessage());
    }
    sleep($interval);
}
