<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function index()
    {
        return view('master_admin.index');
    }

    public function users()
    {
        $users = \App\Models\User::all();
        return view('master_admin.users', compact('users'));
    }

    public function products()
    {
        return view('master_admin.products');
    }

    public function orders()
    {
        return view('master_admin.orders');
    }

    public function news()
    {
        return view('master_admin.news');
    }

    public function stats()
    {
        return view('master_admin.stats');
    }

    public function cart()
    {
        return view('master_admin.cart');
    }

    public function logs(Request $request)
    {
        $logType = $request->get('type', 'laravel');
        $logPath = storage_path('logs/');
        $logs = [];
        $logContent = '';
        
        // ログファイルの一覧を取得
        $logFiles = [];
        if (is_dir($logPath)) {
            $files = scandir($logPath);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $logFiles[] = $file;
                }
            }
        }
        
        // サブディレクトリのログも取得
        $logDirs = ['api', 'web', 'database', 'errors', 'debug'];
        foreach ($logDirs as $dir) {
            $dirPath = $logPath . $dir;
            if (is_dir($dirPath)) {
                $files = scandir($dirPath);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                        $logFiles[] = $dir . '/' . $file;
                    }
                }
            }
        }
        
        // 選択されたログファイルの内容を読み込み
        $selectedLog = $request->get('file', 'laravel.log');
        $selectedLogPath = $logPath . $selectedLog;
        
        if (file_exists($selectedLogPath)) {
            $content = file_get_contents($selectedLogPath);
            // 最新の100行を表示
            $lines = explode("\n", $content);
            $lines = array_slice($lines, -100);
            $logContent = implode("\n", $lines);
        }
        
        return view('master_admin.logs', compact('logFiles', 'logContent', 'selectedLog'));
    }
}
