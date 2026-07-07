<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Enums\UserRole;

class ShowRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:show {--detailed : 詳細表示}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'ロール定義とユーザー統計を表示';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== ロール定義 ===');
        $this->newLine();

        $rows = [];
        foreach (UserRole::cases() as $role) {
            $rows[] = [
                $role->value,
                $role->getLabel(),
                $role->getDescription(),
            ];
        }

        $this->table(
            ['値', '日本語名', '説明'],
            $rows
        );

        $this->newLine();
        $this->info('=== ユーザー統計 ===');
        $this->newLine();

        $statsRows = [];
        $total = 0;
        foreach (UserRole::cases() as $role) {
            $count = User::where('role', $role->value)->count();
            $total += $count;
            $statsRows[] = [
                $role->getLabel(),
                $count,
                number_format($total > 0 ? ($count / $total) * 100 : 0, 1) . '%',
            ];
        }

        // ロール未設定ユーザー
        $nullCount = User::where(function ($query) {
            $query->whereNull('role')
                  ->orWhere('role', '');
        })->count();

        if ($nullCount > 0) {
            $statsRows[] = [
                '未設定',
                $nullCount,
                number_format(($nullCount / ($total + $nullCount)) * 100, 1) . '%',
            ];
        }

        $this->table(
            ['ロール', 'ユーザー数', 'パーセンテージ'],
            $statsRows
        );

        // 詳細表示オプション
        if ($this->option('detailed')) {
            $this->newLine();
            $this->info('=== ロール別ユーザー一覧 ===');
            $this->newLine();

            foreach (UserRole::cases() as $role) {
                $users = User::where('role', $role->value)
                    ->select('id', 'username', 'name_2nd', 'name_1st', 'shop_name')
                    ->get();

                if ($users->isEmpty()) {
                    continue;
                }

                $this->newLine();
                $this->line('<fg=cyan>' . $role->getLabel() . ' (' . $users->count() . '名)</></>');
                
                $userRows = [];
                foreach ($users as $user) {
                    $displayName = $user->shop_name ?: ($user->name_2nd . ' ' . $user->name_1st);
                    $userRows[] = [
                        $user->id,
                        $user->username,
                        $displayName,
                    ];
                }

                $this->table(['ID', 'ユーザー名', '表示名'], $userRows);
            }
        }

        $this->newLine();
        $this->info('✓ ロール情報を表示しました！');

        return Command::SUCCESS;
    }
}
