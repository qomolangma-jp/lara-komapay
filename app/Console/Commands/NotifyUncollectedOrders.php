<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\LineMessagingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class NotifyUncollectedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:uncollected-orders {--hours=24 : 何時間経過で未受取とみなすか}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '受け取りが完了していない（完了のまま期限超過）注文に対してLINEで通知を送る';

    private LineMessagingService $line;

    public function __construct(LineMessagingService $line)
    {
        parent::__construct();
        $this->line = $line;
    }

    public function handle(): int
    {
        $hours = (int) $this->option('hours') ?: (int) env('LINE_UNCOLLECTED_THRESHOLD_HOURS', 24);

        $cutoff = now()->subHours($hours);

        $orders = Order::where('status', Order::STATUS_PREPARED)
            ->whereNull('line_notified_uncollected_at')
            ->where('updated_at', '<=', $cutoff)
            ->with('user')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('未受取の期限超過注文はありませんでした。');
            return 0;
        }

        foreach ($orders as $order) {
            $user = $order->user;
            if (! $user || empty($user->line_user_id)) {
                Log::info('NotifyUncollectedOrders: LINE情報がないためスキップ', ['order_id' => $order->id]);
                continue;
            }

            $message = "【コマペイ】受け取りのご確認\n" .
                "{$user->display_name} 様\n" .
                "注文ID: {$order->id}\n" .
                "ご注文日時: {$order->created_at->format('Y-m-d H:i')}\n" .
                "受取予定日時を過ぎていますが、まだ受け取り処理が完了していません。お手数ですが店舗にご連絡ください。";

            try {
                $resp = $this->line->sendTextMessage($user->line_user_id, $message);
                if ($resp->successful()) {
                    $order->line_notified_uncollected_at = now();
                    $order->save();
                    Log::info('NotifyUncollectedOrders: LINE送信成功', ['order_id' => $order->id]);
                } else {
                    Log::warning('NotifyUncollectedOrders: LINE送信失敗', ['order_id' => $order->id, 'status' => $resp->status()]);
                }
            } catch (\Throwable $e) {
                Log::error('NotifyUncollectedOrders: 例外', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info('未受取注文の通知処理を完了しました。');
        return 0;
    }
}
