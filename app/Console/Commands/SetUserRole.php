<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Enums\UserRole;

class SetUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-role 
                            {user_id : ユーザーID} 
                            {role : ロール (master_admin|admin|seller|user)}
                            {--force : 確認なしで実行}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'ユーザーのロールを設定する';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $roleString = $this->argument('role');

        // ユーザーを検索
        $user = User::find($userId);
        if (!$user) {
            $this->error("ユーザーID「{$userId}」が見つかりません。");
            return Command::FAILURE;
        }

        // ロールを検証
        try {
            $newRole = UserRole::from($roleString);
        } catch (\ValueError $e) {
            $this->error("ロール「{$roleString}」は存在しません。");
            $this->line('利用可能なロール: ' . implode(', ', UserRole::getAllValues()));
            return Command::FAILURE;
        }

        $currentRole = $user->role ?? UserRole::USER;
        
        if ($currentRole === $newRole) {
            $this->info("ユーザー「{$user->username}」は既に「{$newRole->getLabel()}」です。");
            return Command::SUCCESS;
        }

        // 確認
        if (!$this->option('force')) {
            $this->newLine();
            $this->line("ユーザー: {$user->username}");
            $this->line("現在のロール: {$currentRole->getLabel()}");
            $this->line("新しいロール: {$newRole->getLabel()}");
            $this->newLine();

            if (!$this->confirm('ロールを変更しますか？')) {
                $this->info('キャンセルされました。');
                return Command::FAILURE;
            }
        }

        // ロールを更新
        $user->update(['role' => $newRole->value]);

        $this->newLine();
        $this->info('✓ ロールを更新しました！');
        $this->line("ユーザー: {$user->username}");
        $this->line("ロール: {$currentRole->getLabel()} → {$newRole->getLabel()}");

        return Command::SUCCESS;
    }
}
