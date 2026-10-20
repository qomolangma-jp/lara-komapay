# ポイントチャージ API

すべてのポイントチャージ API は Sanctum 認証が必要です。現金・銀行振込の申請は `pending` で記録され、申請時点で残高は変わりません。現金または入金確認済みの振込申請をマスター管理者が承認するとポイントが付与されます。PayPay は PayPay サーバーで決済完了を検証してからポイントを付与します。

## 現金・銀行振込の申請

`POST /api/charge/apply`

```json
{
  "amount": 1000,
  "method": "bank_transfer"
}
```

- `amount`: 整数、100 以上 50,000 未満
- `method`: `cash` または `bank_transfer`。既存クライアント互換のため省略時は `cash`
- 銀行振込の成功レスポンスには `data.transfer_instructions` が含まれます。現金のレスポンスには含まれません。
- `POST /api/master/points/requests/{id}/approve` と `/reject` は既存の管理画面用 API です。承認は `master` のみ可能です。`teacher` は申請一覧を参照できますが、承認できません。

## PayPay チャージ

### 開始

`POST /api/charge/paypay`

```json
{
  "amount": 1000,
  "return_url": "https://frontend.example/charge/paypay/return"
}
```

`return_url` は設定済みのフロントエンドオリジンと `/charge/paypay/return` のみ許可されます。サーバーは手数料を `CHARGE_PAYPAY_FEE_RATE` に基づき円単位で四捨五入し、ポイント額と手数料の合計を PayPay の決済額にします。レスポンスの `data` に `charge_request_id` と PayPay の `payment_url` を返します。

### 確定

`POST /api/charge/paypay/confirm`

```json
{
  "merchantPaymentId": "charge_15"
}
```

`merchantPaymentId` のみ必須です。`transaction_id` と `status` は戻り URL にある場合だけ任意で送れますが、検証の根拠には使いません。サーバーは `merchantPaymentId` から認証ユーザー自身のチャージを特定し、PayPay サーバーへ照会して、決済状態（`COMPLETED`）・merchant payment ID・決済総額・通貨・取引 ID を検証します。すべて確認できた場合だけポイントを付与し、取引 ID は PayPay の照会結果から保存します。成功レスポンスの `data.amount` は付与ポイント数です。

| 結果 | HTTP |
| --- | --- |
| 該当チャージなし・他ユーザーのチャージ | 404 |
| 確定済み・処理済み | 409 |
| 未払い・キャンセル・金額不一致 | 422 |
| PayPay 照会失敗 | 502 |

## サーバー環境変数

| 環境変数 | 用途 |
| --- | --- |
| `CHARGE_BANK_NAME` | 振込先金融機関名 |
| `CHARGE_BANK_BRANCH_NAME` | 振込先支店名 |
| `CHARGE_BANK_ACCOUNT_TYPE` | 口座種別（既定値 `普通`） |
| `CHARGE_BANK_ACCOUNT_NUMBER` | 振込先口座番号 |
| `CHARGE_BANK_ACCOUNT_HOLDER` | 口座名義 |
| `CHARGE_BANK_TRANSFER_DEADLINE` | 表示する振込期限 |
| `PAYPAY_API_KEY` | PayPay API キー |
| `PAYPAY_API_SECRET` | PayPay API シークレット |
| `PAYPAY_MERCHANT_ID` | PayPay マーチャント ID |
| `PAYPAY_API_BASE_URL` | PayPay API URL |
| `PAYPAY_REDIRECT_URL` | 既存 PayPay 決済機能の戻り先 URL |
| `PAYPAY_SANDBOX` | PayPay SDK の Sandbox 設定 |
| `CHARGE_PAYPAY_FEE_RATE` | 手数料率。既定値 `0.05` |
| `FRONTEND_ALLOWED_ORIGINS` | 許可するフロントエンド origin。複数指定はカンマ区切り |

PayPay 認証情報と口座情報はサーバー環境変数で設定してください。秘密情報をソース、レスポンス、ログに含めないでください。
