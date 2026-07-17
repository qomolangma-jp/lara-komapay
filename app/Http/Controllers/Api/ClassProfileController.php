<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class ClassProfileController extends Controller
{
    private function resolveApiUser(Request $request): ?User
    {
        $user = auth('sanctum')->user();
        if ($user instanceof User) {
            return $user;
        }

        $sessionUserId = session('user_id');
        if ($sessionUserId) {
            return User::find($sessionUserId);
        }

        $webUser = $request->user();
        if ($webUser instanceof User) {
            return $webUser;
        }

        return null;
    }

    private function resolveLoginUserId(User $user): string
    {
        $studentId = trim((string) ($user->student_id ?? ''));
        if ($studentId !== '') {
            return $studentId;
        }

        return trim((string) ($user->username ?? ''));
    }

    private function ensureTableExists()
    {
        if (!Schema::hasTable('class_profiles')) {
            return response()->json([
                'success' => false,
                'message' => 'class_profiles テーブルが未作成です。マイグレーションを実行してください。',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return null;
    }

    private function serialize(ClassProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'class_code' => $profile->class_code,
            'student_number' => (int) $profile->student_number,
            'student_name' => $profile->student_name,
            'created_at' => optional($profile->created_at)->toIso8601String(),
            'updated_at' => optional($profile->updated_at)->toIso8601String(),
        ];
    }

    public function index(Request $request)
    {
        if ($response = $this->ensureTableExists()) {
            return $response;
        }

        $query = ClassProfile::query();
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('user_id', 'like', "%{$search}%")
                    ->orWhere('class_code', 'like', "%{$search}%")
                    ->orWhere('student_name', 'like', "%{$search}%");
            });
        }

        $profiles = $query
            ->orderBy('class_code')
            ->orderBy('student_number')
            ->orderBy('user_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $profiles->map(fn (ClassProfile $profile) => $this->serialize($profile))->values(),
        ]);
    }

    public function upsert(Request $request)
    {
        if ($response = $this->ensureTableExists()) {
            return $response;
        }

        $validated = $request->validate([
            'user_id' => 'required|string|max:50',
            'class_code' => ['required', 'string', 'size:2', 'regex:/^[0-9]{2}$/'],
            'student_number' => 'required|integer|min:1|max:99',
            'student_name' => 'required|string|max:100',
        ]);

        $profile = ClassProfile::updateOrCreate(
            ['user_id' => trim($validated['user_id'])],
            [
                'class_code' => $validated['class_code'],
                'student_number' => (int) $validated['student_number'],
                'student_name' => trim($validated['student_name']),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'クラス情報を保存しました。',
            'data' => $this->serialize($profile),
        ]);
    }

    public function destroy(ClassProfile $classProfile)
    {
        if ($response = $this->ensureTableExists()) {
            return $response;
        }

        $classProfile->delete();

        return response()->json([
            'success' => true,
            'message' => 'クラス情報を削除しました。',
        ]);
    }

    public function me(Request $request)
    {
        if ($response = $this->ensureTableExists()) {
            return $response;
        }

        $user = $this->resolveApiUser($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です。',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $loginUserId = $this->resolveLoginUserId($user);
        if ($loginUserId === '') {
            return response()->json([
                'success' => false,
                'message' => 'ユーザーIDが設定されていません。',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $profile = ClassProfile::where('user_id', $loginUserId)->first();

        return response()->json([
            'success' => true,
            'user_id' => $loginUserId,
            'data' => $profile ? $this->serialize($profile) : null,
        ]);
    }
}
