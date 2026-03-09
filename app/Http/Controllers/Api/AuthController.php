<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * LINE IDでユーザーが存在するかチェック
     */
    public function check(Request $request)
    {
        $validated = $request->validate([
            'line_id' => 'required|string',
        ]);

        $user = User::where('line_id', $validated['line_id'])->first();

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * LINE IDでログイン
     */
    public function login(Request $request)
    {
        // LINE IDログインまたはusername/student_id/passwordログインに対応
        if ($request->has('line_id')) {
            // LINE IDでログイン
            $validated = $request->validate([
                'line_id' => 'required|string',
            ]);

            $user = User::where('line_id', $validated['line_id'])->first();
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
                'message' => 'ユーザーが見つかりません、またはパスワードが間違っています',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // セッションにuser_idを保存（Web認証用）
        session(['user_id' => $user->id]);

        // フロントエンド期待形式: { "user": {...}, "token": "..." }
        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
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
            'username' => 'nullable|string|unique:users|max:150',
            'status' => 'nullable|string|max:50',
        ]);

        // usernameが指定されていない場合は、姓名を結合して作成
        $username = $validated['username'] ?? ($validated['name_2nd'] . $validated['name_1st']);

        $user = User::create([
            'username' => $username,
            'line_id' => $validated['line_id'],
            'name_2nd' => $validated['name_2nd'],
            'name_1st' => $validated['name_1st'],
            'student_id' => $validated['student_id'] ?? null,
            'status' => $validated['status'] ?? 'student',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ], Response::HTTP_CREATED);
    }

    /**
     * 現在のユーザー情報を取得
     */
    public function me()
    {
        $user = auth('sanctum')->user();

        return response()->json([
            'success' => true,
            'data' => $user,
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
    public function users()
    {
        // 開発環境用：認証チェックを緩和
        $users = User::orderBy('name_2nd')
            ->orderBy('name_1st')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * マスター管理画面からユーザーを作成
     */
    public function create(Request $request)
    {
        // 開発環境用：認証チェックを緩和

        $validated = $request->validate([
            'username' => 'required|string|max:150|unique:users',
            'name_2nd' => 'required|string|max:50',
            'name_1st' => 'required|string|max:50',
            'shop_name' => 'nullable|string|max:100',
            'line_id' => 'nullable|string|max:100|unique:users',
            'student_id' => 'nullable|string|max:50|unique:users',
            'status' => 'nullable|string|max:50',
            'is_admin' => 'boolean',
            'password' => 'required|string|min:4',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'name_2nd' => $validated['name_2nd'],
            'name_1st' => $validated['name_1st'],
            'shop_name' => $validated['shop_name'] ?? null,
            'line_id' => $validated['line_id'] ?? null,
            'student_id' => $validated['student_id'] ?? null,
            'status' => $validated['status'] ?? 'student',
            'is_admin' => $validated['is_admin'] ?? false,
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
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
            'username' => 'required|string|max:150|unique:users,username,' . $user->id,
            'name_2nd' => 'required|string|max:50',
            'name_1st' => 'required|string|max:50',
            'shop_name' => 'nullable|string|max:100',
            'line_id' => 'nullable|string|max:100|unique:users,line_id,' . $user->id,
            'student_id' => 'nullable|string|max:50|unique:users,student_id,' . $user->id,
            'status' => 'nullable|string|max:50',
            'is_admin' => 'boolean',
            'password' => 'nullable|string|min:6',
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

        // パスワードが指定されている場合のみ更新
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'data' => $user->fresh(),
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
}
