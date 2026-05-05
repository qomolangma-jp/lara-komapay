<?php

namespace App\Services;

use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;

class LineMessagingService
{
    public function sendPasswordResetCode(string $lineUserId, string $code): HttpResponse
    {
        $channelAccessToken = (string) config('services.line_messaging.channel_access_token', '');
        if ($channelAccessToken === '') {
            throw new \RuntimeException('LINE Channel Access Token が設定されていません');
        }

        $message = "【コマペイ】本人確認\nパスワード再設定用の認証コードを発行しました。\n認証コード： {$code}\n有効期限：10分以内";

        return Http::withToken($channelAccessToken)
            ->acceptJson()
            ->timeout(10)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $lineUserId,
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => $message,
                    ],
                ],
            ]);
    }
}