<?php

declare(strict_types=1);

/* Run from cPanel/SSH terminal after entering only the MySQL details in .env:
 * php hosting-deploy.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    fwrite(STDERR, "PHP 8.1 or newer is required. Current version: ".PHP_VERSION.PHP_EOL);
    exit(1);
}

if (! is_file(__DIR__.'/.env')) {
    fwrite(STDERR, "Missing .env. Copy .env.example to .env and enter the MySQL connection details first.".PHP_EOL);
    exit(1);
}

// A previous config cache can contain old DB credentials. Remove it before
// Laravel boots so this runner always reads the current .env connection.
$configCache = __DIR__.'/bootstrap/cache/config.php';
if (is_file($configCache) && ! @unlink($configCache)) {
    fwrite(STDERR, "Cannot remove bootstrap/cache/config.php. Set write permission and run again.".PHP_EOL);
    exit(1);
}

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Services\ImageWebpOptimizer;

try {
    DB::connection()->getPdo();
} catch (Throwable $exception) {
    fwrite(STDERR, "Database connection failed: ".$exception->getMessage().PHP_EOL);
    exit(1);
}

if (! app(ImageWebpOptimizer::class)->supported()) {
    fwrite(STDERR, "PHP GD with WebP support is not enabled. Enable the GD extension from cPanel PHP Selector, then run again.".PHP_EOL);
    exit(1);
}

$commands = [
    ['migrate', ['--force' => true]],
    ['storage:link', []],
    ['optimize:clear', []],
    ['config:cache', []],
    ['route:cache', []],
    ['view:cache', []],
];

if (blank(config('app.key'))) {
    array_unshift($commands, ['key:generate', ['--force' => true]]);
}

foreach ($commands as [$command, $parameters]) {
    $status = Artisan::call($command, $parameters);
    echo Artisan::output();
    if ($status !== 0) {
        fwrite(STDERR, "Deployment stopped while running: {$command}".PHP_EOL);
        exit($status);
    }
}

echo PHP_EOL."Live Bajar deployment completed successfully.".PHP_EOL;
