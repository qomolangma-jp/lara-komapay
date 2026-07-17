<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

    private function resolveStudentId(User $user): string
    {
        return trim((string) ($user->student_id ?? ''));
    }

    private function ensureTableExists()
    {
        if (!Schema::hasTable('class_profiles')) {
            return response()->json([
                'success' => false,
                'message' => 'class_profiles テーブルが未作成です。マイグレーションを実行してください。',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!Schema::hasColumn('class_profiles', 'student_id') || !Schema::hasColumn('class_profiles', 'class')) {
            return response()->json([
                'success' => false,
                'message' => 'class_profiles テーブル定義が古いため、最新マイグレーションを実行してください。',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return null;
    }

    private function serialize(ClassProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'student_id' => $profile->student_id,
            'class' => $profile->class,
            'created_at' => optional($profile->created_at)->toIso8601String(),
            'updated_at' => optional($profile->updated_at)->toIso8601String(),
        ];
    }

    public function index(Request $request)
    {
        if ($response = $this->ensureTableExists()) {
            return $response;
        }

        $query = ClassProfile::query()
            ->leftJoin('users', 'users.student_id', '=', 'class_profiles.student_id')
            ->select([
                'class_profiles.id',
                'class_profiles.student_id',
                'class_profiles.class',
                'class_profiles.created_at',
                'class_profiles.updated_at',
                'users.username',
                'users.name_2nd',
                'users.name_1st',
            ]);

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('class_profiles.student_id', 'like', "%{$search}%")
                    ->orWhere('class_profiles.class', 'like', "%{$search}%")
                    ->orWhere('users.username', 'like', "%{$search}%")
                    ->orWhere('users.name_2nd', 'like', "%{$search}%")
                    ->orWhere('users.name_1st', 'like', "%{$search}%");
            });
        }

        $profiles = $query
            ->orderBy('class_profiles.class')
            ->orderBy('class_profiles.student_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $profiles->map(function ($row) {
                return [
                    'id' => $row->id,
                    'student_id' => $row->student_id,
                    'class' => $row->class,
                    'username' => $row->username,
                    'name_2nd' => $row->name_2nd,
                    'name_1st' => $row->name_1st,
                    'student_name' => trim((string) ($row->name_2nd ?? '') . ' ' . (string) ($row->name_1st ?? '')),
                    'created_at' => optional($row->created_at)->toIso8601String(),
                    'updated_at' => optional($row->updated_at)->toIso8601String(),
                ];
            })->values(),
        ]);
    }

    public function upsert(Request $request)
    {
        if ($response = $this->ensureTableExists()) {
            return $response;
        }

        $validated = $request->validate([
            'student_id' => 'required|string|max:50',
            'class' => ['required', 'string', 'max:10', 'regex:/^[0-9]{1,2}-[0-9]{1,2}$/'],
        ]);

        $profile = ClassProfile::updateOrCreate(
            ['student_id' => trim($validated['student_id'])],
            [
                'class' => trim($validated['class']),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'クラス情報を保存しました。',
            'data' => $this->serialize($profile),
        ]);
    }

    public function importCsv(Request $request)
    {
        if ($response = $this->ensureTableExists()) {
            return $response;
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $handle = fopen($validated['file']->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json([
                'success' => false,
                'message' => 'CSVファイルを読み込めませんでした。',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rows = [];
        $line = 0;
        while (($cols = fgetcsv($handle)) !== false) {
            $line++;

            if ($line === 1 && isset($cols[0]) && preg_match('/student_id|学籍|ユーザー/i', (string) $cols[0])) {
                continue;
            }

            $studentId = trim((string) ($cols[0] ?? ''));
            $classValue = trim((string) ($cols[1] ?? ''));

            if ($studentId === '' && $classValue === '') {
                continue;
            }

            if ($studentId === '' || $classValue === '') {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => "CSV {$line} 行目: student_id と class は必須です。",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (!preg_match('/^[0-9]{1,2}-[0-9]{1,2}$/', $classValue)) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => "CSV {$line} 行目: class は 3-2 の形式で入力してください。",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $rows[] = [
                'student_id' => $studentId,
                'class' => $classValue,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => '取り込み対象データがありません。',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($rows) {
            ClassProfile::query()->upsert($rows, ['student_id'], ['class', 'updated_at']);
        });

        return response()->json([
            'success' => true,
            'message' => count($rows) . ' 件のクラス情報を一括登録/更新しました。',
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

        $studentId = $this->resolveStudentId($user);
        if ($studentId === '') {
            return response()->json([
                'success' => false,
                'message' => 'student_id が設定されていません。',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $profile = ClassProfile::where('student_id', $studentId)->first();

        return response()->json([
            'success' => true,
            'student_id' => $studentId,
            'data' => $profile ? $this->serialize($profile) : null,
        ]);
    }
}
