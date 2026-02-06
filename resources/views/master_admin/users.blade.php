@extends('layouts.master_layout')

@section('title', 'ユーザー管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">ユーザー管理</h1>
    <a href="#" class="btn btn-primary"><i class="fas fa-plus me-1"></i>新規登録</a>
</div>

<div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>ユーザー名</th>
                <th>氏名</th>
                <th>LINE ID</th>
                <th>管理者</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->username }}</td>
                <td>{{ $user->name_1st }} {{ $user->name_2nd }}</td>
                <td>{{ $user->line_id }}</td>
                <td>{{ $user->is_admin ? '○' : '×' }}</td>
                <td>
                    <a href="#" class="btn btn-sm btn-primary">編集</a>
                    <a href="#" class="btn btn-sm btn-danger">削除</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
