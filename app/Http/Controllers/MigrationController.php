<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MigrationController extends Controller
{
    /**
     * マイグレーション管理画面を表示
     */
    public function index()
    {
        return view('migration.index');
    }

    /**
     * マイグレーション状態を取得
     */
    public function status()
    {
        Artisan::call('migrate:status');
        $output = Artisan::output();
        
        return response()->json([
            'success' => true,
            'output' => $output,
        ]);
    }

    /**
     * マイグレーションを実行
     */
    public function migrate(Request $request)
    {
        // セキュリティチェック: 管理者のみ実行可能
        $user = auth('sanctum')->user();
        if (!$user || !$user->is_admin) {
            $key = $request->query('key');
            $password = env('MIGRATE_KEY', 'changeme');
            if ($key !== $password) {
                return response()->json([
                    'success' => false,
                    'message' => '管理者権限が必要です',
                ], 403);
            }
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'マイグレーションが正常に実行されました',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'マイグレーション実行中にエラーが発生しました',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * マイグレーションをロールバック
     */
    public function rollback(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user || !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です',
            ], 403);
        }

        try {
            $step = $request->input('step', 1);
            Artisan::call('migrate:rollback', ['--step' => $step, '--force' => true]);
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'ロールバックが正常に実行されました',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ロールバック実行中にエラーが発生しました',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * キャッシュクリア
     */
    public function clearCache(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user || !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です',
            ], 403);
        }

        try {
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'キャッシュをクリアしました',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'キャッシュクリア中にエラーが発生しました',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * データベース構造を確認
     */
    public function checkTable(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user || !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です',
            ], 403);
        }

        try {
            $table = $request->input('table', 'products');
            $columns = DB::select("SHOW COLUMNS FROM {$table}");
            
            return response()->json([
                'success' => true,
                'table' => $table,
                'columns' => $columns,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'テーブル情報の取得中にエラーが発生しました',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 古い形式のマイグレーション（後方互換性のため保持）
     */
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
