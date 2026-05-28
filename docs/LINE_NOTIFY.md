# LINE通知（未受取注文）設定と運用

この機能は、受け取りが完了していない（`status` が `受渡済` になっていない）注文で、完了状態のまま所定時間を経過したものに対してLINEで通知を送信します。

## 環境変数
`.env` に以下を追加してください：

- `LINE_CHANNEL_ACCESS_TOKEN` - LINE Messaging API の Channel Access Token
- `LINE_CHANNEL_SECRET` - LINE Channel Secret（既に存在する場合は不要）
- `LINE_UNCOLLECTED_THRESHOLD_HOURS` - 何時間経過で未受取とみなすか（省略時は `24`）

例：

```
LINE_CHANNEL_ACCESS_TOKEN=YOUR_TOKEN_HERE
LINE_CHANNEL_SECRET=YOUR_SECRET_HERE
LINE_UNCOLLECTED_THRESHOLD_HOURS=24
```

## マイグレーション
新しいカラム `line_notified_uncollected_at` を `orders` テーブルに追加するマイグレーションを作成済みです。
デプロイ環境で実行してください：

```bash
php artisan migrate --force
```

## 手動実行（テスト）
しきい値を任意に指定してコマンドを実行できます：

```bash
# 24時間を超えた未受取注文に通知
php artisan notify:uncollected-orders

# 48時間を超えたものに限定して実行
php artisan notify:uncollected-orders --hours=48
```

コマンドは `line_user_id` がユーザーに設定されている場合にのみ送信します。

## スケジュール
`app/Console/Kernel.php` にて毎日 `01:00` に実行するよう登録済みです。サーバーでLaravel Schedulerを動かすには、cronで以下を設定してください：

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## ログ確認
送信成功・失敗はLaravelログに記録されます（`storage/logs/laravel.log`）。

## 注意点
- ユーザーに `line_user_id`（LINEのユーザーID）が登録されている必要があります。
- 一度通知が送信されると `orders.line_notified_uncollected_at` が埋まり、再送はされません。

