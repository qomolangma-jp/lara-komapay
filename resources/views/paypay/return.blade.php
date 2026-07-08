<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPay決済確認</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
            font-family: sans-serif;
            color: #1f2937;
        }
        .panel {
            width: min(92vw, 420px);
            padding: 28px 24px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.12);
            text-align: center;
        }
        .spinner {
            width: 44px;
            height: 44px;
            margin: 0 auto 16px;
            border: 4px solid #dbe3ea;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .message {
            font-size: 0.95rem;
            line-height: 1.7;
            color: #4b5563;
        }
        .error {
            color: #b91c1c;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="panel">
        <div class="spinner" id="spinner"></div>
        <div class="title" id="title">PayPay決済を確認しています</div>
        <div class="message" id="message">しばらくお待ちください。</div>
    </div>

    <script>
        const merchantPaymentId = @json($merchantPaymentId);
        const title = document.getElementById('title');
        const message = document.getElementById('message');
        const spinner = document.getElementById('spinner');
        const isDeposit = String(merchantPaymentId || '').startsWith('deposit_');

        if (isDeposit) {
            title.textContent = '残高チャージを確認しています';
            message.textContent = '入金が完了しているか確認中です。';
        }

        async function checkPayment() {
            if (!merchantPaymentId) {
                title.textContent = '決済情報が見つかりません';
                message.innerHTML = '注文情報を確認できませんでした。<br>学生画面からもう一度お試しください。';
                spinner.style.display = 'none';
                return;
            }

            try {
                const response = await fetch('/api/payments/paypay/confirm', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ merchantPaymentId })
                });

                const result = await response.json().catch(() => ({}));
                spinner.style.display = 'none';

                if (response.ok && result.success) {
                    title.textContent = isDeposit ? '残高チャージが完了しました' : '決済確認が完了しました';
                    message.textContent = result.message || (isDeposit ? '残高へ反映しました。' : '注文を処理しました。');
                    if (isDeposit) {
                        const link = document.createElement('a');
                        link.href = '/student';
                        link.textContent = '学生画面へ戻る';
                        link.className = 'btn btn-primary mt-3';
                        message.appendChild(document.createElement('br'));
                        message.appendChild(link);
                    }
                } else if (result.data && result.data.deleted) {
                    title.textContent = '注文を削除しました';
                    message.textContent = result.message || '決済が完了していなかったため、注文を削除しました。';
                } else {
                    title.textContent = '決済の確認に失敗しました';
                    message.textContent = result.message || 'もう一度お試しください。';
                    message.classList.add('error');
                }
            } catch (error) {
                spinner.style.display = 'none';
                title.textContent = '通信エラー';
                message.textContent = '決済確認に失敗しました。時間をおいて再度お試しください。';
                message.classList.add('error');
            }
        }

        checkPayment();
    </script>
</body>
</html>
