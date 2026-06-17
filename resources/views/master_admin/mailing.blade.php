@extends('layouts.master_layout')

@section('content')
<div class="container mt-4">
    <h2>メルマガ送信</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="/master/mailing/send" class="mb-4">
        @csrf
        <div class="mb-3">
            <label class="form-label">送信先（個別）</label>
            <input type="email" name="to" class="form-control" placeholder="example@domain.com">
            <div class="form-text">空欄にすると全員送信またはチェックで全員送信できます</div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="send_to_all" name="send_to_all">
            <label class="form-check-label" for="send_to_all">全員に送信（メールのあるユーザー全員）</label>
        </div>

        <div class="mb-3">
            <label class="form-label">件名</label>
            <input type="text" name="subject" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">本文</label>
            <textarea name="body" class="form-control" rows="8" required></textarea>
        </div>

        <button class="btn btn-primary" type="submit">送信</button>
    </form>

    <div class="card">
        <div class="card-body">
            <h5>使い方</h5>
            <ul>
                <li>個別送信: 送信先にメールアドレスを入力して送信</li>
                <li>全員送信: チェックを入れて送信（メールアドレスがあるユーザーのみ、最大2000件）</li>
                <li>ローカル開発時は `.env` の `MAIL_` 設定を `log` ドライバにしてログを確認してください</li>
            </ul>
        </div>
    </div>
</div>
@endsection
