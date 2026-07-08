<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>メールアドレス確認</title>
</head>
<body>
    <p>{{ $user->name_2nd ?? $user->username }} 様,</p>

    <p>このメールはメールアドレス確認のために送信されています。以下のリンクをクリックしてメールアドレスを確認してください。</p>

    <p><a href="{{ $verifyUrl }}">メールアドレスを確認する</a></p>

    <p>もしこのリクエストに心当たりがない場合は、このメールは破棄してください。</p>

    <p>よろしくお願いします。</p>
</body>
</html>
