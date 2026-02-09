@extends('layouts.master_layout')

@section('title', 'ダッシュボード')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">ダッシュボード</h1>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>売上統計</h5>
                <p class="card-text">売上データの確認・分析</p>
                <a href="/master/stats" class="btn btn-light btn-sm">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-users me-2"></i>ユーザー管理</h5>
                <p class="card-text">ユーザーの登録・編集・削除</p>
                <a href="/master/users" class="btn btn-light btn-sm">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-shopping-bag me-2"></i>商品管理</h5>
                <p class="card-text">商品の登録・編集・削除</p>
                <a href="/master/products" class="btn btn-light btn-sm">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-receipt me-2"></i>注文管理</h5>
                <p class="card-text">注文の確認・ステータス変更</p>
                <a href="/master/orders" class="btn btn-light btn-sm">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-newspaper me-2"></i>ニュース管理</h5>
                <p class="card-text">お知らせの登録・編集・削除</p>
                <a href="/master/news" class="btn btn-light btn-sm">管理画面へ</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-secondary">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-file-alt me-2"></i>ログ管理</h5>
                <p class="card-text">システムログの確認・分析</p>
                <a href="/master/logs" class="btn btn-light btn-sm">管理画面へ</a>
            </div>
        </div>
    </div>
</div>
@endsection
