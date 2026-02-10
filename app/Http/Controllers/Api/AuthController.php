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

    /**
     * 全ユーザーを取得（管理者のみ）
     */
    public function users()
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です',
            ], Response::HTTP_FORBIDDEN);
        }

        $users = User::orderBy('name_2nd')
            ->orderBy('name_1st')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * ユーザーを更新（管理者のみ）
     */
    public function update(Request $request, User $user)
    {
        $currentUser = auth('sanctum')->user();

        if (!$currentUser || !$currentUser->is_admin) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'username' => 'required|string|max:150|unique:users,username,' . $user->id,
            'name_2nd' => 'required|string|max:50',
            'name_1st' => 'required|string|max:50',
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
     * ユーザーを削除（管理者のみ）
     */
    public function destroy(User $user)
    {
        $currentUser = auth('sanctum')->user();

        if (!$currentUser || !$currentUser->is_admin) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です',
            ], Response::HTTP_FORBIDDEN);
        }

        // 自分自身は削除できない
        if ($currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => '自分自身を削除することはできません',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'ユーザーを削除しました',
        ]);
    }
}
