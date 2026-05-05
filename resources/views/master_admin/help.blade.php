@extends('layouts.master_layout')

@section('title', '使い方')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">使い方</h1>
        <p class="text-muted mb-0">管理画面の基本操作を、用途ごとにまとめています。</p>
    </div>
    <a href="/master" class="btn btn-outline-primary">
        <i class="fas fa-tachometer-alt me-1"></i>ダッシュボードへ
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                        <i class="fas fa-user-cog fs-5"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">管理者向け</h5>
                        <small class="text-muted">ユーザー・商品・注文の確認</small>
                    </div>
                </div>
                <ul class="mb-0 ps-3">
                    <li>ダッシュボードで売上や件数を確認</li>
                    <li>ユーザー管理で登録情報を修正</li>
                    <li>商品管理で在庫や公開状態を調整</li>
                    <li>注文管理でステータスを更新</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                        <i class="fas fa-calendar-check fs-5"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">営業設定</h5>
                        <small class="text-muted">注文可能時間の管理</small>
                    </div>
                </div>
                <ul class="mb-0 ps-3">
                    <li>注文可能時間設定で営業日・休止日を登録</li>
                    <li>対象日を選んで時間帯を一括保存</li>
                    <li>カレンダーで設定状況を月ごとに確認</li>
                    <li>ログ管理で変更履歴を追跡</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                        <i class="fas fa-shopping-cart fs-5"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">カート・ログ</h5>
                        <small class="text-muted">現在の状態を素早く確認</small>
                    </div>
                </div>
                <ul class="mb-0 ps-3">
                    <li>カート管理で現在のカート内容を確認</li>
                    <li>ニュース管理でお知らせを配信</li>
                    <li>ログ管理で操作履歴を閲覧</li>
                    <li>必要に応じて検索や並び替えを利用</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h5 class="mb-0">画面の使い方</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="fw-semibold mb-2">1. 左メニューを選ぶ</div>
                    <div class="text-muted small">目的の画面をクリックして移動します。</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="fw-semibold mb-2">2. 検索・絞り込みを使う</div>
                    <div class="text-muted small">一覧が多い場合は検索欄や条件指定を使います。</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="fw-semibold mb-2">3. 確認して保存する</div>
                    <div class="text-muted small">更新前に内容を確認し、保存ボタンで反映します。</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="fw-semibold mb-2">4. ログで追跡する</div>
                    <div class="text-muted small">変更後はログ管理で結果を確認できます。</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm">
    <strong>補足:</strong> 画面ごとに操作方法が少し違うため、各一覧の上部にある案内やボタン表示もあわせて確認してください。
</div>
@endsection