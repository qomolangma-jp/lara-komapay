<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class SendTestEmail extends Command
{
    protected $signature = 'mail:send-test {--to= : Email address to send to}';
    protected $description = 'Send a test verification email to the specified address (default: hoda1480ka@icloud.com)';

    public function handle()
    {
        $to = $this->option('to') ?: 'hoda1480ka@icloud.com';

        $user = (object) [
            'username' => $to,
            'name_2nd' => 'テスト',
        ];

        $token = bin2hex(random_bytes(20));
        $appUrl = config('app.url') ?: env('APP_URL', 'http://localhost');
        $verifyUrl = rtrim($appUrl, '/') . '/auth/verify-email?token=' . $token;

        $body = "メール確認リンク: " . $verifyUrl . "\n\nこのメールはテスト送信です。";

        try {
            Mail::raw($body, function ($m) use ($to) {
                $m->to($to)->subject('【Komapay】テストメール（確認リンク）');
            });

            $this->info("Sent test email to {$to}");
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
            $this->line('Ensure MAIL_ settings are configured in .env (MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS).');
            return 1;
        }
    }
}
