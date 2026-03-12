<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Migration Fix Script ===\n\n";

// 1. productsテーブルの現在の構造を確認
echo "1. Checking products table structure...\n";
$columns = DB::select('SHOW COLUMNS FROM products');
$hasLabel = false;
$hasSellerId = false;
$hasAllergens = false;

foreach($columns as $col) {
    echo "  - {$col->Field}\n";
    if($col->Field === 'label') $hasLabel = true;
    if($col->Field === 'seller_id') $hasSellerId = true;
    if($col->Field === 'allergens') $hasAllergens = true;
}

echo "\n";

// 2. seller_id関連のマイグレーションをマーク（既にカラムが存在する場合）
if($hasSellerId) {
    echo "2. seller_id column exists. Marking migrations as ran...\n";
    
    $exists1 = DB::table('migrations')->where('migration', '2026_02_10_000000_add_seller_to_products_table')->exists();
    if(!$exists1) {
        DB::table('migrations')->insert([
            'migration' => '2026_02_10_000000_add_seller_to_products_table',
            'batch' => 4
        ]);
        echo "  ✓ Marked: 2026_02_10_000000_add_seller_to_products_table\n";
    } else {
        echo "  - Already marked: 2026_02_10_000000_add_seller_to_products_table\n";
    }
    
    $exists2 = DB::table('migrations')->where('migration', '2026_02_10_000001_change_seller_to_seller_id_in_products_table')->exists();
    if(!$exists2) {
        DB::table('migrations')->insert([
            'migration' => '2026_02_10_000001_change_seller_to_seller_id_in_products_table',
            'batch' => 4
        ]);
        echo "  ✓ Marked: 2026_02_10_000001_change_seller_to_seller_id_in_products_table\n";
    } else {
        echo "  - Already marked: 2026_02_10_000001_change_seller_to_seller_id_in_products_table\n";
    }
}

echo "\n";

// 3. labelカラムの追加
if(!$hasLabel) {
    echo "3. label column does not exist. Adding it...\n";
    DB::statement('ALTER TABLE products ADD COLUMN label varchar(50) NULL AFTER category');
    echo "  ✓ label column added!\n";
} else {
    echo "3. label column already exists.\n";
}

echo "\n";

// 4. labelマイグレーションをマーク
echo "4. Marking label migrations as ran...\n";

$exists3 = DB::table('migrations')->where('migration', '2026_03_12_000000_add_label_to_products_table')->exists();
if(!$exists3) {
    DB::table('migrations')->insert([
        'migration' => '2026_03_12_000000_add_label_to_products_table',
        'batch' => 5
    ]);
    echo "  ✓ Marked: 2026_03_12_000000_add_label_to_products_table\n";
} else {
    echo "  - Already marked: 2026_03_12_000000_add_label_to_products_table\n";
}

// 重複マイグレーションを削除（存在する場合）
$exists4 = DB::table('migrations')->where('migration', '2026_03_12_091941_add_label_to_products_table')->exists();
if($exists4) {
    DB::table('migrations')->where('migration', '2026_03_12_091941_add_label_to_products_table')->delete();
    echo "  ✓ Removed duplicate: 2026_03_12_091941_add_label_to_products_table\n";
}

echo "\n";

// 5. allergensカラムの追加
if(!$hasAllergens) {
    echo "5. allergens column does not exist. Adding it...\n";
    DB::statement('ALTER TABLE products ADD COLUMN allergens text NULL AFTER description');
    echo "  ✓ allergens column added!\n";
} else {
    echo "5. allergens column already exists.\n";
}

echo "\n";

// 6. allergensマイグレーションをマーク
echo "6. Marking allergens migration as ran...\n";

$exists5 = DB::table('migrations')->where('migration', '2026_03_12_131302_add_allergens_to_products_table')->exists();
if(!$exists5) {
    DB::table('migrations')->insert([
        'migration' => '2026_03_12_131302_add_allergens_to_products_table',
        'batch' => 6
    ]);
    echo "  ✓ Marked: 2026_03_12_131302_add_allergens_to_products_table\n";
} else {
    echo "  - Already marked: 2026_03_12_131302_add_allergens_to_products_table\n";
}

echo "\n=== Done! ===\n";
echo "Run: php artisan migrate:status to verify\n";
