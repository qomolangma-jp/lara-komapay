<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Enums\UserRole;

class MigrateUserRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:migrate {--force : Force execution without confirmation}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = '既存ユーザーのロールを is_admin と status から自動生成';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            $this->newLine();
            $this->line('このコマンドは既存ユーザーのロールを自動生成します。');
            $this->line('');
            $this->warn('注意: すでにロールが設定されているユーザーは上書きされます。');
            $this->newLine();

            if (!$this->confirm('続行しますか？')) {
                $this->info('キャンセルされました。');
                return Command::FAILURE;
            }
        }

        $this->info('ロール移行開始...');
        $this->newLine();

        // is_admin = true のユーザーをマスター管理者に
        $masterAdminCount = User::where('is_admin', true)
            ->update(['role' => UserRole::MASTER_ADMIN->value]);
        if ($masterAdminCount > 0) {
            $this->info("✓ is_admin = true のユーザーを「マスター管理者」に設定: {$masterAdminCount} 件");
        }

        // status = 'admin' のユーザーを一般管理者に
        $adminCount = User::where('status', 'admin')
            ->update(['role' => UserRole::ADMIN->value]);
        if ($adminCount > 0) {
            $this->info("✓ status = 'admin' のユーザーを「一般管理者」に設定: {$adminCount} 件");
        }

        // status = 'seller' のユーザーを販売者に
        $sellerCount = User::where('status', 'seller')
            ->update(['role' => UserRole::SELLER->value]);
        if ($sellerCount > 0) {
            $this->info("✓ status = 'seller' のユーザーを「販売者」に設定: {$sellerCount} 件");
        }

        // ロールが未設定のユーザーを通常ユーザーに（その他）
        $userCount = User::where(function ($query) {
            $query->whereNull('role')
                  ->orWhere('role', '');
        })
        ->update(['role' => UserRole::USER->value]);
        if ($userCount > 0) {
            $this->info("✓ ロールが未設定のユーザーを「通常ユーザー」に設定: {$userCount} 件");
        }

        // 結果表示
        $this->newLine();
        $this->table(
            ['ロール', '件数', '説明'],
            [
                [UserRole::MASTER_ADMIN->getLabel(), User::where('role', UserRole::MASTER_ADMIN->value)->count(), UserRole::MASTER_ADMIN->getDescription()],
                [UserRole::ADMIN->getLabel(), User::where('role', UserRole::ADMIN->value)->count(), UserRole::ADMIN->getDescription()],
                [UserRole::SELLER->getLabel(), User::where('role', UserRole::SELLER->value)->count(), UserRole::SELLER->getDescription()],
                [UserRole::USER->getLabel(), User::where('role', UserRole::USER->value)->count(), UserRole::USER->getDescription()],
            ]
        );

        $this->newLine();
        $this->info('✓ ロール移行が完了しました！');

        return Command::SUCCESS;
    }
}
