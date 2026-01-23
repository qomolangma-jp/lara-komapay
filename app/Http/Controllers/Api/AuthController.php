<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        $validated = $request->validate([
            'line_id' => 'required|string',
        ]);

        $user = User::where('line_id', $validated['line_id'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'ユーザーが見つかりません',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ログインしました',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    /**
     * ユーザーを登録
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users|max:150',
            'line_id' => 'required|string|unique:users',
            'name_2nd' => 'nullable|string|max:50',
            'name_1st' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'line_id' => $validated['line_id'],
            'name_2nd' => $validated['name_2nd'] ?? null,
            'name_1st' => $validated['name_1st'] ?? null,
            'status' => $validated['status'] ?? 'student',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'アカウントを作成しました',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
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
