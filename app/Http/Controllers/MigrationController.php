<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MigrationController extends Controller
{
    public function migrate(Request $request)
    {
        $key = $request->query('key');
        // セキュリティのため、適当なパスワードを設定してください
        $password = env('MIGRATE_KEY', 'changeme');
        if ($key !== $password) {
            return response('Unauthorized', 401);
        }
        // 実行
        Artisan::call('migrate');
        return response('Migration executed: ' . Artisan::output());
    }

    public function fresh(Request $request)
    {
        $key = $request->query('key');
        $password = env('MIGRATE_KEY', 'changeme');
        if ($key !== $password) {
            return response('Unauthorized', 401);
        }
        Artisan::call('migrate:fresh --seed');
        return response('Migration fresh executed: ' . Artisan::output());
    }
}
