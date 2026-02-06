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
        // LINE IDログインまたはusername/passwordログインに対応
        if ($request->has('line_id')) {
            // LINE IDでログイン
            $validated = $request->validate([
                'line_id' => 'required|string',
            ]);

            $user = User::where('line_id', $validated['line_id'])->first();
        } else {
            // username/passwordでログイン
            $validated = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            $user = User::where('username', $validated['username'])->first();

            // パスワードチェック（パスワードがnullの場合はスキップ）
            if ($user && $user->password && !Hash::check($validated['password'], $user->password)) {
                $user = null;
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'ユーザーが見つかりません、またはパスワードが間違っています',
                'token' => null,
                'user' => null,
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
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

        return response()->json([
            'success' => true,
            'message' => 'ログアウトしました',
        ]);
    }
}
