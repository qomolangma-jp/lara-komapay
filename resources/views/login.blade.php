<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - 学校食堂注文システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        .form-floating input {
            border-radius: 10px;
        }
        .btn-custom {
            border-radius: 10px;
            padding: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="login-card p-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 mb-3 fw-bold">🍽️ ログイン</h1>
                        <p class="text-muted">学校食堂注文システムへようこそ</p>
                    </div>
                    
                    <div id="error" class="alert alert-danger d-none" role="alert"></div>
                    
                    <form id="loginForm">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="ユーザー名" required>
                            <label for="username">ユーザー名</label>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" name="password" placeholder="パスワード" required>
                            <label for="password">パスワード</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 btn-custom">
                            ログイン
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="text-muted small mb-2">テストアカウント</p>
                        <div class="d-flex justify-content-around">
                            <button class="btn btn-sm btn-outline-primary" onclick="fillCredentials('student', '1234')">
                                学生
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="fillCredentials('seller', 'seller')">
                                販売者
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="fillCredentials('admin', 'admin')">
                                管理者
                            </button>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <a href="/" class="text-decoration-none">← トップページに戻る</a>
                    </div>

                    <div class="mt-4">
                        <button type="button" class="btn btn-outline-info w-100" onclick="testLoginEndpoint()">
                            /api/auth/login 接続テスト
                        </button>
                        <div id="loginApiTestResult" class="small text-muted mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error');
            
            errorDiv.classList.add('d-none');
            
            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success && data.token) {
                    // トークンとユーザー情報を保存
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    
                    // ユーザーの役割に応じて画面遷移
                    if (data.user && data.user.is_admin) {
                        window.location.href = '/master';
                    } else if (data.user && data.user.status === 'seller') {
                        window.location.href = '/seller';
                    } else {
                        window.location.href = '/student';
                    }
                } else {
                    errorDiv.textContent = data.message || 'ログインに失敗しました';
                    errorDiv.classList.remove('d-none');
                }
            } catch (error) {
                errorDiv.textContent = 'エラーが発生しました: ' + error.message;
                errorDiv.classList.remove('d-none');
            }
        });

        async function testLoginEndpoint() {
            const result = document.getElementById('loginApiTestResult');
            result.textContent = 'テスト中...';

            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        username: 'student',
                        password: '1234'
                    })
                });

                result.textContent = `status=${response.status} (${response.ok ? 'OK' : 'NG'})`;
            } catch (error) {
                result.textContent = `error: ${error.message}`;
            }
        }
    </script>
</body>
</html>
