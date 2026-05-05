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
        <h5 class="mb-0">画面別の使い方</h5>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-12">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        <h6 class="mb-0">売上統計画面</h6>
                    </div>
                    <p class="text-muted mb-2">店舗全体の売上や注文の動きを確認する画面です。日付を指定して、売上の推移、注文数、利用者数、平均客単価、人気商品をまとめて確認できます。</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">主な見方</div>
                                <ul class="mb-0 ps-3">
                                    <li>開始日と終了日を変更して、集計期間を切り替えます。</li>
                                    <li>注文ステータスで絞り込み、特定の状態だけを確認できます。</li>
                                    <li>総売上、注文数、利用ユーザー数、販売個数、平均客単価を上部カードで確認できます。</li>
                                    <li>売上／注文数トレンドで日ごとの増減を確認できます。</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">ボタン・操作</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>更新</strong>：最新の集計結果を読み込み直します。</li>
                                    <li><strong>適用</strong>：日付範囲とステータス条件を反映します。</li>
                                    <li><strong>期間入力</strong>：開始日・終了日を指定して集計対象を絞ります。</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-users text-primary me-2"></i>
                        <h6 class="mb-0">ユーザー管理画面</h6>
                    </div>
                    <p class="text-muted mb-2">ユーザー情報を一覧で確認し、新規登録や編集を行う画面です。学生、販売者、管理者の情報をまとめて管理できます。</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">一覧画面の説明</div>
                                <ul class="mb-0 ps-3">
                                    <li>検索欄でユーザー名、氏名、学生IDを探せます。</li>
                                    <li>ステータスで student / teacher / seller を絞り込めます。</li>
                                    <li>管理者フィルターで一般ユーザーと管理者を切り替えられます。</li>
                                    <li>表示列メニューで、ID、LINE ID、学生IDなどの列の表示を調整できます。</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">ボタン・操作</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>一覧画面</strong>：一覧表示に切り替えます。</li>
                                    <li><strong>登録・編集画面</strong>：入力フォームに切り替えます。</li>
                                    <li><strong>新規登録</strong>：新しいユーザーの入力を開始します。</li>
                                    <li><strong>登録</strong>：入力内容を保存します。</li>
                                    <li><strong>キャンセル</strong>：編集をやめて一覧に戻ります。</li>
                                    <li><strong>操作列の編集・削除</strong>：既存ユーザーの修正や削除を行います。</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-shopping-bag text-primary me-2"></i>
                        <h6 class="mb-0">商品管理画面</h6>
                    </div>
                    <p class="text-muted mb-2">商品情報、価格、在庫、画像、販売者情報を管理する画面です。注文画面に表示される商品内容をここで整えます。</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">一覧画面の説明</div>
                                <ul class="mb-0 ps-3">
                                    <li>商品名や説明文で検索できます。</li>
                                    <li>カテゴリや販売者で商品を絞り込めます。</li>
                                    <li>並び替えで価格順、在庫順、商品名順に変更できます。</li>
                                    <li>表示列メニューで画像、アレルギー、販売者などの表示を調整できます。</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">ボタン・操作</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>一覧画面</strong>：一覧表示に戻ります。</li>
                                    <li><strong>登録・編集画面</strong>：商品入力フォームを開きます。</li>
                                    <li><strong>新規追加</strong>：新しい商品を追加します。</li>
                                    <li><strong>一覧に戻る</strong>：フォームを閉じて一覧へ戻ります。</li>
                                    <li><strong>画像を選択／削除</strong>：商品画像の登録と差し替えを行います。</li>
                                    <li><strong>保存</strong>：商品情報を登録・更新します。</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-receipt text-primary me-2"></i>
                        <h6 class="mb-0">注文管理画面</h6>
                    </div>
                    <p class="text-muted mb-2">注文の状況を確認し、調理中から受渡済までのステータスを更新する画面です。注文の流れを現場で確認するときに使います。</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">一覧画面の説明</div>
                                <ul class="mb-0 ps-3">
                                    <li>注文ID、ユーザー、学籍番号、商品点数、金額、ステータス、注文日時を確認できます。</li>
                                    <li>ステータス絞り込みで必要な注文だけを表示できます。</li>
                                    <li>日付指定で、その日の注文だけを見られます。</li>
                                    <li>「詳細」で注文内容を展開して確認できます。</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">ボタン・操作</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>更新</strong>：注文一覧を再読み込みします。</li>
                                    <li><strong>日付指定を解除</strong>：日付フィルターを外します。</li>
                                    <li><strong>ステータス選択</strong>：対象の状態だけ表示します。</li>
                                    <li><strong>各行の操作ボタン</strong>：調理中、完了、受渡済、キャンセルなどに変更します。</li>
                                    <li><strong>確認モーダルの実行</strong>：ステータス変更を確定します。</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-newspaper text-danger me-2"></i>
                        <h6 class="mb-0">ニュース管理画面</h6>
                    </div>
                    <p class="text-muted mb-2">お知らせや案内文を登録、編集、公開する画面です。学生への連絡や営業案内を出すときに使います。</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">一覧画面の説明</div>
                                <ul class="mb-0 ps-3">
                                    <li>タイトル、公開状態、投稿日時、更新日時を一覧で確認できます。</li>
                                    <li>タイトルや本文で検索できます。</li>
                                    <li>公開・非公開で絞り込みできます。</li>
                                    <li>表示列メニューで見たい項目だけ表示できます。</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">ボタン・操作</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>一覧画面</strong>：一覧表示に戻ります。</li>
                                    <li><strong>登録・編集画面</strong>：投稿フォームを開きます。</li>
                                    <li><strong>新規投稿</strong>：新しいニュースを作成します。</li>
                                    <li><strong>一覧に戻る</strong>：入力をやめて一覧に戻ります。</li>
                                    <li><strong>投稿</strong>：入力したニュースを保存します。</li>
                                    <li><strong>画像選択／削除</strong>：記事画像の追加や削除を行います。</li>
                                </ul>
                            </div>
                        </div>
                    </div>
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