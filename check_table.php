<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    if (\Schema::hasTable('password_reset_codes')) {
        $cols = \DB::select('DESCRIBE password_reset_codes');
        echo "TABLE_EXISTS\n";
        print_r($cols);
    } else {
        echo "TABLE_NOT_FOUND\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
