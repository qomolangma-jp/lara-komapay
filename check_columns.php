<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select('SHOW COLUMNS FROM products');

echo "Products table columns:\n";
foreach($columns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}
