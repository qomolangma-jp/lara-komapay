<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// adminユーザーにパスワード設定
$admin = User::where('username', 'admin')->first();
if ($admin) {
    $admin->password = Hash::make('admin');
    $admin->save();
    echo "admin password set to 'admin'\n";
}

// studentユーザーにパスワード設定
$student = User::where('username', 'student')->first();
if ($student) {
    $student->password = Hash::make('1234');
    $student->save();
    echo "student password set to '1234'\n";
}

// うんこユーザーにパスワード設定
$seller = User::where('username', 'うんこ')->first();
if ($seller) {
    $seller->password = Hash::make('seller');
    $seller->save();
    echo "seller password set to 'seller'\n";
}

echo "All passwords updated successfully!\n";
