<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function supportsLineUserId(): bool
    {
        return Schema::hasColumn('users', 'line_user_id');
    }

    private function findUserByLineId(string $lineId): ?User
    {
        $query = User::query();

        if ($this->supportsLineUserId()) {
            $query->where('line_user_id', $lineId)->orWhere('line_id', $lineId);
        } else {
            $query->where('line_id', $lineId);
        }

        return $query->first();
    }

    /**
     * LINE IDでユーザーが存在するかチェック
     */
    public function check(Request $request)
    {
        try {
            $lineId = (string) (
                $request->input('line_id')
                ?? $request->query('line_id')
                ?? $request->header('X-Line-Id')
                ?? ''
            );

            if ($lineId === '') {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'line_id は必須です',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = $this->findUserByLineId($lineId);

            if (!$user) {
                return $this->jsonResponse([
                    'success' => false,
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'ユーザーが見つかりません',
                    'user' => null,
                ], Response::HTTP_OK);
            }

            $user->tokens()->where('name', 'line_check_token')->delete();
            $token = $user->createToken('line_check_token')->plainTextToken;

            return $this->buildAuthSuccessResponse($user, $token);
        } catch (\Throwable $e) {
            \Log::error('Auth check error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->jsonResponse([
                'success' => false,
                'message' => '認証チェックに失敗しました',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * LINE IDでログイン
     */
    public function login(Request $request)
    {
        try {
            // LINE IDログインまたはusername/student_id/passwordログインに対応
            // filled() を使って、line_idが存在し、かつ空でない場合のみLINEログインとする
            if ($request->filled('line_id')) {
                // LINE IDでログイン
                $validated = $request->validate([
                    'line_id' => 'required|string',
                ]);

                $user = $this->findUserByLineId($validated['line_id']);
            } else {
                // username/student_id + passwordでログイン
                $validated = $request->validate([
                    'password' => 'required|string',
                ]);

                // student_idまたはusernameで検索
                $identifier = $request->input('student_id') ?: $request->input('username');
                
                if (!$identifier) {
                    return response()->json([
                        'message' => 'student_idまたはusernameが必要です',
                    ], Response::HTTP_BAD_REQUEST);
                }

                // student_idまたはusernameで検索
                $user = User::where('student_id', $identifier)
                            ->orWhere('username', $identifier)
                            ->first();

                // パスワードチェック（パスワードがnullの場合はスキップ）
                if ($user && $user->password && !Hash::check($validated['password'], $user->password)) {
                    $user = null;
                }
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザーが見つかりません、またはパスワードが間違っています',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // セッションにuser_idを保存（Web認証用）
            session(['user_id' => $user->id]);
            
            return $this->buildAuthSuccessResponse($user, $token);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'サーバーエラーが発生しました: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * ユーザーを登録
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'line_id' => 'required|string|unique:users',
            'name_2nd' => 'required|string|max:50',
            'name_1st' => 'required|string|max:50',
            'student_id' => 'nullable|string|unique:users|max:50',
            'username' => 'nullable|string|unique:users|max:150|regex:/^[\x21-\x7E]+$/',
            'status' => 'nullable|string|max:50',
        ], [
            'username.regex' => 'ユーザー名は半角英数字と記号のみで入力してください。',
        ]);

        // usernameが指定されていない場合は、半角英数字記号のみの安全な既定値を作成
        $username = $validated['username'] ?? null;
        if (!$username) {
            $seed = (string) ($validated['student_id'] ?? $validated['line_id'] ?? 'user');
            $seed = preg_replace('/[^\x21-\x7E]/', '', $seed);
            if ($seed === '') {
                $seed = 'user' . now()->format('YmdHis');
            }
            $username = $seed;

            $suffix = 1;
            while (User::where('username', $username)->exists()) {
                $username = $seed . $suffix;
                $suffix++;
            }
        }

        $userData = [
            'username' => $username,
            'line_id' => $validated['line_id'],
            'name_2nd' => $validated['name_2nd'],
            'name_1st' => $validated['name_1st'],
            'student_id' => $validated['student_id'] ?? null,
            'status' => $validated['status'] ?? 'student',
        ];

        if ($this->supportsLineUserId()) {
            $userData['line_user_id'] = $validated['line_id'];
        }

        $user = User::create($userData);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->buildAuthSuccessResponse($user, $token, Response::HTTP_CREATED);
    }

    /**
     * 現在のユーザー情報を取得
     */
    public function me()
    {
        $user = auth('sanctum')->user();

        return response()->json([
            'success' => true,
            'user' => $this->serializeAuthUser($user),
            'cart_count' => $this->getCartCount($user),
        ]);
    }

    /**
     * ログアウト
     */
    public function logout()
    {
        auth('sanctum')->user()->tokens()->delete();

        // セッションからuser_idを削除
        session()->forget('user_id');

        return response()->json([
            'success' => true,
            'message' => 'ログアウトしました',
        ]);
    }

    /**
     * 全ユーザーを取得
     */
    public function users(Request $request)
    {
        try {
            // 開発環境用：認証チェックを緩和
            $perPage = $request->input('per_page', 50); // デフォルト50件
            
            \Log::info('Users API called', ['per_page' => $perPage]);
            
            $columns = ['id', 'username', 'name_2nd', 'name_1st', 'student_id', 'status', 'is_admin', 'shop_name', 'line_id', 'created_at', 'updated_at'];
            if ($this->supportsLineUserId()) {
                $columns[] = 'line_user_id';
            }

            $users = User::select($columns)
                ->orderBy('name_2nd')
                ->orderBy('name_1st')
                ->paginate($perPage);

            \Log::info('Users fetched successfully', ['count' => count($users->items())]);

            return response()->json([
                'success' => true,
                'data' => array_map([$this, 'serializeUser'], $users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Users API error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * マスター管理画面からユーザーを作成
     */
    public function create(Request $request)
    {
        // 開発環境用：認証チェックを緩和

        $validated = $request->validate([
            'username' => 'required|string|max:150|regex:/^[\x21-\x7E]+$/|unique:users',
            'name_2nd' => 'required|string|max:50',
            'name_1st' => 'required|string|max:50',
            'shop_name' => 'nullable|string|max:100',
            'line_id' => 'nullable|string|max:100|unique:users',
            'student_id' => 'nullable|string|max:50|unique:users',
            'status' => 'nullable|string|max:50',
            'is_admin' => 'boolean',
            'password' => 'required|string|min:4',
        ], [
            'username.regex' => 'ユーザー名は半角英数字と記号のみで入力してください。',
        ]);

        $userData = [
            'username' => $validated['username'],
            'name_2nd' => $validated['name_2nd'],
            'name_1st' => $validated['name_1st'],
            'shop_name' => $validated['shop_name'] ?? null,
            'line_id' => $validated['line_id'] ?? null,
            'student_id' => $validated['student_id'] ?? null,
            'status' => $validated['status'] ?? 'student',
            'is_admin' => $validated['is_admin'] ?? false,
            'password' => Hash::make($validated['password']),
        ];

        if ($this->supportsLineUserId()) {
            $userData['line_user_id'] = $validated['line_id'] ?? null;
        }

        $user = User::create($userData);

        return response()->json([
            'success' => true,
            'data' => $this->serializeUser($user),
            'message' => 'ユーザーを作成しました',
        ], Response::HTTP_CREATED);
    }

    /**
     * ユーザーを更新
     */
    public function update(Request $request, User $user)
    {
        // 開発環境用：認証チェックを緩和

        $validated = $request->validate([
            'username' => 'required|string|max:150|regex:/^[\x21-\x7E]+$/|unique:users,username,' . $user->id,
            'name_2nd' => 'required|string|max:50',
            'name_1st' => 'required|string|max:50',
            'shop_name' => 'nullable|string|max:100',
            'line_id' => 'nullable|string|max:100|unique:users,line_id,' . $user->id,
            'student_id' => 'nullable|string|max:50|unique:users,student_id,' . $user->id,
            'status' => 'nullable|string|max:50',
            'is_admin' => 'boolean',
            'password' => 'nullable|string|min:6',
        ], [
            'username.regex' => 'ユーザー名は半角英数字と記号のみで入力してください。',
        ]);

        $updateData = [
            'username' => $validated['username'],
            'name_2nd' => $validated['name_2nd'],
            'name_1st' => $validated['name_1st'],
            'shop_name' => $validated['shop_name'] ?? null,
            'line_id' => $validated['line_id'] ?? null,
            'student_id' => $validated['student_id'] ?? null,
            'status' => $validated['status'] ?? 'student',
            'is_admin' => $validated['is_admin'] ?? false,
        ];

        if ($this->supportsLineUserId()) {
            $updateData['line_user_id'] = $validated['line_id'] ?? null;
        }

        // パスワードが指定されている場合のみ更新
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'data' => $this->serializeUser($user->fresh()),
            'message' => 'ユーザーを更新しました',
        ]);
    }

    /**
     * ユーザーを削除
     */
    public function destroy(User $user)
    {
        // 開発環境用：認証チェックを緩和
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'ユーザーを削除しました',
        ]);
    }

    /**
     * .env ファイルから環境変数を直接取得
     */
    private function getEnvValue(string $key): ?string
    {
        // 標準的な env() 関数を試す
        $value = env($key);
        if ($value) {
            return $value;
        }

        // .env ファイルから直接読み込む（キャッシュ対策）
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $content = file_get_contents($envPath);
            if (preg_match('/^' . preg_quote($key) . '\s*=\s*(.+?)$/m', $content, $matches)) {
                $value = trim($matches[1], '\'" ');
                return $value ?: null;
            }
        }

        return null;
    }

    /**
     * 管理画面からパスワードを再発行してLINEへ送信する
     */
    public function resetPassword(Request $request, User $user)
    {
        try {
            \Log::debug('resetPassword called', ['user_id' => $user->id, 'request' => $request->all()]);
            $lineTarget = null;
            if ($this->supportsLineUserId()) {
                $lineTarget = $user->line_user_id ?: $user->line_id ?: null;
            } else {
                $lineTarget = $user->line_id ?: null;
            }

            if (!$lineTarget) {
                return response()->json([
                    'success' => false,
                    'message' => 'LINE ID が登録されていないため送信できません',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // .env ファイルから直接トークンを取得
            $token = $this->getEnvValue('LINE_CHANNEL_ACCESS_TOKEN');
            \Log::debug('LINE token present', ['has_token' => (bool) $token, 'token_length' => strlen($token ?? '')]);
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'LINE チャネルアクセストークンが未設定です',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $newPassword = Str::random(8);
            $loginUrl = env('APP_URL', '') ?: ($request->getSchemeAndHttpHost() . '/login');
            $message = "パスワード再発行のお知らせ\nユーザー: " . ($user->username ?? '') . "\n新しいパスワード: {$newPassword}\nログイン: {$loginUrl}";

            $resp = Http::withToken($token)
                ->post('https://api.line.me/v2/bot/message/push', [
                    'to' => $lineTarget,
                    'messages' => [
                        ['type' => 'text', 'text' => $message],
                    ],
                ]);

            \Log::debug('LINE push response', ['status' => $resp->status(), 'body' => $resp->body()]);

            if ($resp->successful()) {
                $user->password = Hash::make($newPassword);
                $user->save();

                return response()->json([
                    'success' => true,
                    'message' => 'パスワードを再発行し、LINEへ送信しました',
                ]);
            }

            \Log::error('LINE push failed for resetPassword', ['status' => $resp->status(), 'body' => $resp->body()]);

            return response()->json([
                'success' => false,
                'message' => 'LINE送信に失敗しました',
                'detail' => $resp->body(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            \Log::error('resetPassword error', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'パスワード再発行中にエラーが発生しました',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function serializeUser(User $user): array
    {
        $displayName = $user->display_name ?: trim(($user->name_2nd ?? '') . ' ' . ($user->name_1st ?? ''));
        $displayName = $displayName !== '' ? $displayName : ($user->username ?? '');

        return [
            'id' => $user->id,
            'username' => $user->username ?? '',
            'student_id' => $user->student_id ?? '',
            'status' => $user->status ?? '',
            'role' => $user->isAdmin() ? 'admin' : ($user->status ?? 'student'),
            'is_admin' => (bool) $user->is_admin,
            'name_2nd' => $user->name_2nd ?? '',
            'name_1st' => $user->name_1st ?? '',
            'shop_name' => $user->shop_name ?? '',
            'line_id' => $user->line_id ?? '',
            'line_user_id' => $user->line_user_id ?? '',
            'display_name' => $displayName,
            'name' => $displayName,
            'icon' => '',
            'created_at' => optional($user->created_at)->toIso8601String(),
            'updated_at' => optional($user->updated_at)->toIso8601String(),
        ];
    }

    private function serializeAuthUser(User $user): array
    {
        $name = trim(($user->name_2nd ?? '') . ' ' . ($user->name_1st ?? ''));
        $name = $name !== '' ? $name : ($user->display_name ?: ($user->username ?? ''));

        $displayName = $user->shop_name ?: (($user->name_2nd ?? '') . ($user->name_1st ?? ''));
        $displayName = $displayName !== '' ? $displayName : ($user->username ?? '');

        // DBのstudent_idを優先し、NULL/空文字の場合はフロント用の既定値を返す
        $studentId = $user->student_id;
        if (is_string($studentId)) {
            $studentId = trim($studentId);
        }
        if (empty($studentId)) {
            $studentId = $user->line_id ?: ($user->username ?: 'UNASSIGNED');
        }

        $role = $user->isAdmin() ? 'admin' : ($user->status ?: 'student');

        return [
            'id' => $user->id ?? ($user->line_id ?: ($user->student_id ?: $user->username)),
            'username' => $user->username ?? '',
            'name' => $name,
            'displayName' => $displayName,
            'picture' => '',
            'student_id' => (string) $studentId,
            'status' => $user->status ?? 'student',
            'is_admin' => (bool) $user->is_admin,
            'shop_name' => $user->shop_name ?? '',
            'line_user_id' => $user->line_user_id ?? '',
            'role' => $role,
        ];
    }

    private function buildAuthSuccessResponse(User $user, string $token, int $status = Response::HTTP_OK)
    {
        return $this->jsonResponse([
            'success' => true,
            'user' => $this->serializeAuthUser($user),
            'cart_count' => $this->getCartCount($user),
            'token' => $token,
        ], $status);
    }

    private function jsonResponse(array $payload, int $status = Response::HTTP_OK)
    {
        return response(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    private function getCartCount(User $user): int
    {
        return (int) CartItem::where('user_id', $user->id)->sum('quantity');
    }

    /**
     * LINE ログインコールバック処理
     */
    public function lineCallback(Request $request)
    {
        try {
            $validated = $request->validate([
                'line_id' => 'required|string',
                'name' => 'nullable|string',
                'picture' => 'nullable|string|url',
            ]);

            $lineId = $validated['line_id'];
            
            // 既存ユーザーをチェック
            $user = User::where('line_id', $lineId)->first();
            
            if (!$user) {
                // 新規ユーザー作成
                $nameData = $this->parseLineName($validated['name'] ?? null);
                
                // usernameを生成（LINEユーザーの場合）
                $username = 'line_' . substr($lineId, 0, 12);
                $suffix = 1;
                while (User::where('username', $username)->exists()) {
                    $username = 'line_' . substr($lineId, 0, 12) . $suffix;
                    $suffix++;
                }
                
                $userData = [
                    'username' => $username,
                    'line_id' => $lineId,
                    'name_2nd' => $nameData['name_2nd'] ?? '',
                    'name_1st' => $nameData['name_1st'] ?? $validated['name'] ?? 'LINE User',
                    'status' => 'student',
                ];

                if ($this->supportsLineUserId()) {
                    $userData['line_user_id'] = $lineId;
                }

                $user = User::create($userData);
            }

            // トークン生成
            $user->tokens()->where('name', 'line_auth_token')->delete();
            $token = $user->createToken('line_auth_token')->plainTextToken;

            return $this->buildAuthSuccessResponse($user, $token);
        } catch (\Throwable $e) {
            \Log::error('LINE callback error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'LINE ログインに失敗しました',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * LINE ユーザー名をパース（苗字と名前に分割）
     */
    private function parseLineName(?string $fullName): array
    {
        if (!$fullName) {
            return ['name_2nd' => '', 'name_1st' => 'ユーザー'];
        }

        // スペースで分割
        $parts = explode(' ', trim($fullName), 2);
        
        if (count($parts) === 2) {
            return [
                'name_2nd' => mb_substr($parts[0], 0, 50),
                'name_1st' => mb_substr($parts[1], 0, 50),
            ];
        }

        return [
            'name_2nd' => '',
            'name_1st' => mb_substr($fullName, 0, 50),
        ];
    }
}
