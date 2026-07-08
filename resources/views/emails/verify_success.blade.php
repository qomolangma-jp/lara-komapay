<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>メール認証完了</title>
</head>
<body>
    <h1>メール認証が完了しました</h1>
    <p>{{ $user->name_2nd ?? $user->username }} さん、メールの確認ありがとうございます。</p>
    <p>ログインページへは <a href="/login">こちら</a> から移動してください。</p>
</body>
</html>
