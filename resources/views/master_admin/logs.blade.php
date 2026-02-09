@extends('layouts.master_layout')

@section('title', 'ログ管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-file-alt me-2"></i>ログ管理</h1>
</div>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>ログファイル選択</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('master.logs') }}" class="row g-3">
            <div class="col-md-8">
                <label for="logFile" class="form-label">ログファイル</label>
                <select name="file" id="logFile" class="form-select" onchange="this.form.submit()">
                    <option value="">-- ログファイルを選択 --</option>
                    @foreach($logFiles as $file)
                        <option value="{{ $file }}" {{ $selectedLog == $file ? 'selected' : '' }}>
                            {{ $file }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sync-alt me-2"></i>再読み込み
                </button>
            </div>
        </form>
    </div>
</div>

@if($logContent)
<div class="card">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-file-code me-2"></i>{{ $selectedLog }}
        </h5>
        <small class="text-light">最新100行を表示</small>
    </div>
    <div class="card-body p-0">
        <div style="background-color: #1e1e1e; color: #d4d4d4; padding: 20px; overflow-x: auto; max-height: 600px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6;">
            <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">{{ $logContent }}</pre>
        </div>
    </div>
    <div class="card-footer">
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>ファイルパス: storage/logs/{{ $selectedLog }}
                </small>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-sm btn-secondary" onclick="document.querySelector('pre').scrollTop = 0">
                    <i class="fas fa-arrow-up me-1"></i>先頭へ
                </button>
                <button class="btn btn-sm btn-secondary" onclick="document.querySelector('pre').scrollTop = document.querySelector('pre').scrollHeight">
                    <i class="fas fa-arrow-down me-1"></i>最後へ
                </button>
            </div>
        </div>
    </div>
</div>
@else
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>ログファイルを選択してください。
</div>
@endif

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>ログディレクトリ情報</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-folder me-2"></i>api/</h6>
                        <p class="small text-muted">APIリクエスト・レスポンス、認証ログ</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-folder me-2"></i>web/</h6>
                        <p class="small text-muted">Web画面アクセス、ユーザー操作ログ</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-folder me-2"></i>database/</h6>
                        <p class="small text-muted">データベースクエリ、マイグレーションログ</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-folder me-2"></i>errors/</h6>
                        <p class="small text-muted">アプリケーションエラー、例外ログ</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-folder me-2"></i>debug/</h6>
                        <p class="small text-muted">デバッグ、パフォーマンスログ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // ログ内容を自動スクロール（最新行へ）
    document.addEventListener('DOMContentLoaded', function() {
        const preElement = document.querySelector('pre');
        if (preElement) {
            const container = preElement.parentElement;
            container.scrollTop = container.scrollHeight;
        }
    });
    
    // エラー行をハイライト
    document.addEventListener('DOMContentLoaded', function() {
        const preElement = document.querySelector('pre');
        if (preElement) {
            let content = preElement.innerHTML;
            // エラーレベルに応じて色分け
            content = content.replace(/\[ERROR\]/gi, '<span style="color: #ff5555; font-weight: bold;">[ERROR]</span>');
            content = content.replace(/\[WARNING\]/gi, '<span style="color: #ffaa00; font-weight: bold;">[WARNING]</span>');
            content = content.replace(/\[INFO\]/gi, '<span style="color: #50fa7b; font-weight: bold;">[INFO]</span>');
            content = content.replace(/\[DEBUG\]/gi, '<span style="color: #8be9fd;">[DEBUG]</span>');
            preElement.innerHTML = content;
        }
    });
</script>
@endsection
