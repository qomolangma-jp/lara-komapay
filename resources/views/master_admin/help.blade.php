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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h5 class="mb-0">各画面の説明</h5>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-tachometer-alt text-primary me-2"></i>
                        <h6 class="mb-0">ダッシュボード</h6>
                    </div>
                    <p class="text-muted mb-2">売上、注文数、利用者数などの全体状況を確認する画面です。期間を指定して、日別の売上推移や注文傾向を見られます。</p>
                    <ul class="mb-0 ps-3">
                        <li>期間を変更して分析対象を切り替える</li>
                        <li>売上・注文数・平均注文額を確認する</li>
                        <li>上位商品や最新注文を把握する</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-users text-primary me-2"></i>
                        <h6 class="mb-0">ユーザー管理</h6>
                    </div>
                    <p class="text-muted mb-2">学生・販売者・管理者などのユーザー情報を登録、編集、削除する画面です。LINE連携や学生番号の確認にも使います。</p>
                    <ul class="mb-0 ps-3">
                        <li>新規ユーザーを追加する</li>
                        <li>名前、学生ID、LINE ID、権限を更新する</li>
                        <li>登録済みユーザーを検索・一覧確認する</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-shopping-bag text-primary me-2"></i>
                        <h6 class="mb-0">商品管理</h6>
                    </div>
                    <p class="text-muted mb-2">商品の追加、編集、削除、在庫調整を行う画面です。注文に表示される商品情報や販売価格もここで管理します。</p>
                    <ul class="mb-0 ps-3">
                        <li>商品の基本情報を登録する</li>
                        <li>価格、在庫、説明文、画像を更新する</li>
                        <li>販売停止や公開状態の管理を行う</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-receipt text-primary me-2"></i>
                        <h6 class="mb-0">注文管理</h6>
                    </div>
                    <p class="text-muted mb-2">注文の受付状況や処理状況を確認する画面です。調理中、完了、受渡済、キャンセルなどのステータスを変更できます。</p>
                    <ul class="mb-0 ps-3">
                        <li>注文一覧を確認して対応状況を把握する</li>
                        <li>注文ステータスを更新する</li>
                        <li>日付や状態で絞り込みを行う</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-calendar-alt text-success me-2"></i>
                        <h6 class="mb-0">注文可能時間設定</h6>
                    </div>
                    <p class="text-muted mb-2">日ごとに注文を受け付けるか、休止にするかを設定する画面です。営業日ごとの開始時刻・終了時刻もまとめて管理できます。</p>
                    <ul class="mb-0 ps-3">
                        <li>カレンダーから対象日を選ぶ</li>
                        <li>営業日または休止日を一括設定する</li>
                        <li>月ごとの設定状況を一覧で確認する</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-shopping-cart text-warning me-2"></i>
                        <h6 class="mb-0">カート管理</h6>
                    </div>
                    <p class="text-muted mb-2">現在のカート内容を確認する画面です。ユーザーごとのカート状態をチェックし、不要なアイテムを削除できます。</p>
                    <ul class="mb-0 ps-3">
                        <li>ログイン中ユーザーの現在カートを確認する</li>
                        <li>商品名で検索して表示を絞る</li>
                        <li>不要なカートアイテムを削除する</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-newspaper text-danger me-2"></i>
                        <h6 class="mb-0">ニュース管理</h6>
                    </div>
                    <p class="text-muted mb-2">お知らせや案内文を登録・更新する画面です。学生向けの周知や営業情報の告知に使います。</p>
                    <ul class="mb-0 ps-3">
                        <li>新しいお知らせを投稿する</li>
                        <li>掲載内容を修正する</li>
                        <li>不要なお知らせを削除する</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-file-alt text-secondary me-2"></i>
                        <h6 class="mb-0">ログ管理</h6>
                    </div>
                    <p class="text-muted mb-2">システムログや監査ログを確認する画面です。操作履歴や不具合調査に使います。</p>
                    <ul class="mb-0 ps-3">
                        <li>操作履歴やエラーを確認する</li>
                        <li>画面切り替えでファイルログと監査ログを見分ける</li>
                        <li>変更内容の追跡に活用する</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-database text-dark me-2"></i>
                        <h6 class="mb-0">マイグレーション</h6>
                    </div>
                    <p class="text-muted mb-2">データベースの更新や初期化を行う画面です。テーブル作成や構造変更が必要なときに使います。</p>
                    <ul class="mb-0 ps-3">
                        <li>テーブルの作成状況を確認する</li>
                        <li>マイグレーションの実行・ロールバックを行う</li>
                        <li>キャッシュ削除や状態確認を行う</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h5 class="mb-0">操作のコツ</h5>
    </div>
    <div class="card-body">
        <ul class="mb-0 ps-3">
            <li>一覧画面は、まず検索と並び替えを使うと目的のデータを見つけやすくなります。</li>
            <li>更新系の画面は、保存前に対象データと内容をもう一度確認してください。</li>
            <li>設定変更後は、ログ管理で反映状況を確認すると安心です。</li>
        </ul>
    </div>
</div>
@endsection