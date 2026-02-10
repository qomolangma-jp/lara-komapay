@extends('layouts.master_layout')

@section('title', 'マイグレーション管理')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-database me-2"></i>マイグレーション管理
        </h1>
    </div>

    <div id="alert-area"></div>

    <!-- 警告メッセージ -->
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>注意:</strong> この機能は開発・本番デプロイ時のみ使用してください。
        実行前に必ずデータベースのバックアップを取ってください。
    </div>

    <!-- マイグレーション状態 -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>マイグレーション状態</h5>
        </div>
        <div class="card-body">
            <button class="btn btn-primary mb-3" onclick="checkStatus()">
                <i class="fas fa-sync-alt me-1"></i>状態を確認
            </button>
            <pre id="migration-status" class="bg-light p-3" style="max-height: 400px; overflow-y: auto;">状態を確認してください...</pre>
        </div>
    </div>

    <!-- マイグレーション実行 -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-play me-2"></i>マイグレーション実行</h5>
                </div>
                <div class="card-body">
                    <p>実行されていないマイグレーションを実行します。</p>
                    <button class="btn btn-success" onclick="runMigration()">
                        <i class="fas fa-play me-1"></i>マイグレーション実行
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-undo me-2"></i>ロールバック</h5>
                </div>
                <div class="card-body">
                    <p>最新のマイグレーションをロールバックします。</p>
                    <div class="input-group mb-3">
                        <span class="input-group-text">ステップ数</span>
                        <input type="number" class="form-control" id="rollback-step" value="1" min="1">
                        <button class="btn btn-warning" onclick="runRollback()">
                            <i class="fas fa-undo me-1"></i>ロールバック実行
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- キャッシュクリア -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-broom me-2"></i>キャッシュクリア</h5>
        </div>
        <div class="card-body">
            <p>設定・ルート・ビューのキャッシュをクリアします。</p>
            <button class="btn btn-info" onclick="clearCache()">
                <i class="fas fa-broom me-1"></i>キャッシュクリア
            </button>
        </div>
    </div>

    <!-- テーブル構造確認 -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>テーブル構造確認</h5>
        </div>
        <div class="card-body">
            <div class="input-group mb-3">
                <span class="input-group-text">テーブル名</span>
                <input type="text" class="form-control" id="table-name" value="products">
                <button class="btn btn-primary" onclick="checkTable()">
                    <i class="fas fa-search me-1"></i>構造を確認
                </button>
            </div>
            <pre id="table-structure" class="bg-light p-3" style="max-height: 400px; overflow-y: auto; display: none;"></pre>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    // 管理者権限確認
    if (!token || !user.is_admin) {
        window.location.href = '/login';
    }

    // マイグレーション状態を確認
    async function checkStatus() {
        try {
            const response = await fetch('/migration/status', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                document.getElementById('migration-status').textContent = result.output;
            } else {
                showAlert('danger', result.message || '状態確認に失敗しました');
            }
        } catch (error) {
            console.error('エラー:', error);
            showAlert('danger', 'エラーが発生しました');
        }
    }

    // マイグレーション実行
    async function runMigration() {
        if (!confirm('マイグレーションを実行してもよろしいですか？\n\n⚠️ 実行前にデータベースのバックアップを取得していることを確認してください。')) {
            return;
        }

        try {
            const response = await fetch('/migration/migrate', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                document.getElementById('migration-status').textContent = result.output;
            } else {
                showAlert('danger', result.message || 'マイグレーション実行に失敗しました');
            }
        } catch (error) {
            console.error('エラー:', error);
            showAlert('danger', 'エラーが発生しました');
        }
    }

    // ロールバック実行
    async function runRollback() {
        const step = document.getElementById('rollback-step').value;
        
        if (!confirm(`最新の${step}個のマイグレーションをロールバックしてもよろしいですか？\n\n⚠️ この操作は元に戻せません。`)) {
            return;
        }

        try {
            const response = await fetch('/migration/rollback', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ step: parseInt(step) })
            });

            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                document.getElementById('migration-status').textContent = result.output;
            } else {
                showAlert('danger', result.message || 'ロールバックに失敗しました');
            }
        } catch (error) {
            console.error('エラー:', error);
            showAlert('danger', 'エラーが発生しました');
        }
    }

    // キャッシュクリア
    async function clearCache() {
        try {
            const response = await fetch('/migration/clear-cache', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
            } else {
                showAlert('danger', result.message || 'キャッシュクリアに失敗しました');
            }
        } catch (error) {
            console.error('エラー:', error);
            showAlert('danger', 'エラーが発生しました');
        }
    }

    // テーブル構造確認
    async function checkTable() {
        const tableName = document.getElementById('table-name').value;
        
        try {
            const response = await fetch('/migration/check-table', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ table: tableName })
            });

            const result = await response.json();
            
            if (result.success) {
                const structureDiv = document.getElementById('table-structure');
                structureDiv.style.display = 'block';
                
                let output = `テーブル: ${result.table}\n`;
                output += '='.repeat(100) + '\n';
                output += sprintf('%-20s %-20s %-10s %-10s %-20s\n', 'Field', 'Type', 'Null', 'Key', 'Extra');
                output += '-'.repeat(100) + '\n';
                
                result.columns.forEach(col => {
                    output += sprintf('%-20s %-20s %-10s %-10s %-20s\n',
                        col.Field,
                        col.Type,
                        col.Null,
                        col.Key,
                        col.Extra || ''
                    );
                });
                
                structureDiv.textContent = output;
            } else {
                showAlert('danger', result.message || 'テーブル構造の取得に失敗しました');
            }
        } catch (error) {
            console.error('エラー:', error);
            showAlert('danger', 'エラーが発生しました');
        }
    }

    // sprintf関数（簡易版）
    function sprintf(format, ...args) {
        let i = 0;
        return format.replace(/%-?(\d+)s/g, (match, width) => {
            const str = String(args[i++] || '');
            const isLeft = match.startsWith('%-');
            const w = parseInt(width);
            if (isLeft) {
                return str.padEnd(w, ' ');
            } else {
                return str.padStart(w, ' ');
            }
        });
    }

    function showAlert(type, message) {
        const alertArea = document.getElementById('alert-area');
        alertArea.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        setTimeout(() => alertArea.innerHTML = '', 5000);
    }

    // ページ読み込み時に状態を確認
    checkStatus();
</script>
@endsection
