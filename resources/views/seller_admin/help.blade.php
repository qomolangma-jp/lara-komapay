@extends('layouts.seller_layout')

@section('title', '使い方')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">使い方</h1>
        <p class="text-muted mb-0">販売者管理画面の各機能とボタンの役割をまとめています。</p>
    </div>
    <a href="/seller" class="btn btn-outline-success">
        <i class="fas fa-store me-1"></i>ダッシュボードへ
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h5 class="mb-0">販売者管理画面の共通の見方</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="fw-semibold mb-2">左メニュー</div>
                    <div class="text-muted small">各機能へ移動する入口です。まずはここから目的の画面を開きます。</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="fw-semibold mb-2">一覧画面</div>
                    <div class="text-muted small">登録済みデータを確認し、検索、並び替え、絞り込みを行う画面です。</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="fw-semibold mb-2">登録・編集画面</div>
                    <div class="text-muted small">新規作成や既存データの修正を行う入力フォームです。</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="fw-semibold mb-2">更新ボタン</div>
                    <div class="text-muted small">一覧や集計を最新状態に読み直すときに使います。</div>
                </div>
            </div>
        </div>
    </div>
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
                        <i class="fas fa-tachometer-alt text-success me-2"></i>
                        <h6 class="mb-0">ダッシュボード</h6>
                    </div>
                    <p class="text-muted mb-2">販売者としての状況をざっくり確認する画面です。今日の注文や売上、対応状況を把握するための入口になります。</p>
                    <ul class="mb-0 ps-3">
                        <li>本日の注文状況を確認する</li>
                        <li>売上や注文の傾向を把握する</li>
                        <li>各管理画面へ素早く移動する</li>
                    </ul>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-shopping-bag text-success me-2"></i>
                        <h6 class="mb-0">商品管理画面</h6>
                    </div>
                    <p class="text-muted mb-2">販売者が自分の商品を登録・編集する画面です。商品名、価格、在庫、カテゴリ、画像などをここで管理します。</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">一覧画面でできること</div>
                                <ul class="mb-0 ps-3">
                                    <li>商品名や説明で検索する</li>
                                    <li>カテゴリや販売者で絞り込む</li>
                                    <li>価格順、在庫順、商品名順に並び替える</li>
                                    <li>表示列を切り替えて見やすくする</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">主なボタン</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>新規追加</strong>：商品登録フォームを開きます。</li>
                                    <li><strong>一覧画面</strong>：商品一覧へ戻ります。</li>
                                    <li><strong>登録・編集画面</strong>：入力フォームに切り替えます。</li>
                                    <li><strong>一覧に戻る</strong>：編集をやめて一覧へ戻ります。</li>
                                    <li><strong>画像の選択・削除</strong>：商品の画像を登録・差し替えます。</li>
                                    <li><strong>保存</strong>：入力内容を反映します。</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-receipt text-success me-2"></i>
                        <h6 class="mb-0">注文管理画面</h6>
                    </div>
                    <p class="text-muted mb-2">注文の確認と対応を行う画面です。調理中や完了など、注文の状態を変えて現場の進行を管理します。</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">画面の見方</div>
                                <ul class="mb-0 ps-3">
                                    <li>注文ID、ユーザー、学籍番号、商品点数、金額を確認する</li>
                                    <li>ステータスで表示を絞り込む</li>
                                    <li>日付を指定してその日の注文だけを見る</li>
                                    <li>各行を展開して詳細を確認する</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">主なボタン</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>更新</strong>：注文一覧を再取得します。</li>
                                    <li><strong>日付指定を解除</strong>：日付フィルターを外します。</li>
                                    <li><strong>ステータス絞り込み</strong>：必要な注文だけを表示します。</li>
                                    <li><strong>ステータス変更</strong>：調理中、完了、受渡済、キャンセルに更新します。</li>
                                    <li><strong>実行</strong>：確認モーダルで変更を確定します。</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-newspaper text-success me-2"></i>
                        <h6 class="mb-0">ニュース管理画面</h6>
                    </div>
                    <p class="text-muted mb-2">お知らせを投稿し、学生や利用者へ案内を出す画面です。営業案内、臨時休業、注意事項の連絡に向いています。</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">一覧画面でできること</div>
                                <ul class="mb-0 ps-3">
                                    <li>タイトルや本文で検索する</li>
                                    <li>公開・非公開で絞り込む</li>
                                    <li>投稿日時や更新日時で並び替える</li>
                                    <li>表示列を切り替える</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-2">主なボタン</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>新規投稿</strong>：ニュース入力フォームを開きます。</li>
                                    <li><strong>一覧画面</strong>：ニュース一覧を表示します。</li>
                                    <li><strong>登録・編集画面</strong>：投稿フォームに切り替えます。</li>
                                    <li><strong>投稿</strong>：タイトル、本文、画像、公開状態を保存します。</li>
                                    <li><strong>画像の選択・削除</strong>：記事画像を追加・削除します。</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-chart-line text-success me-2"></i>
                        <h6 class="mb-0">売上・履歴レポート</h6>
                    </div>
                    <p class="text-muted mb-2">売上や注文履歴を確認する画面です。販売状況を一覧で見たいときや、期間ごとの傾向を確認したいときに使います。</p>
                    <ul class="mb-0 ps-3">
                        <li>売上の推移を確認する</li>
                        <li>注文履歴を期間指定で確認する</li>
                        <li>集計内容を見て販売傾向を把握する</li>
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
            <li>一覧画面は検索と並び替えを先に使うと、探したいデータを見つけやすくなります。</li>
            <li>編集画面は、保存前に商品名・価格・在庫・公開状態を確認してください。</li>
            <li>注文管理では、更新後に一覧を再読み込みして反映を確認すると安心です。</li>
            <li>ニュースは、公開状態を確認してから投稿すると誤公開を防げます。</li>
        </ul>
    </div>
</div>
@endsection
