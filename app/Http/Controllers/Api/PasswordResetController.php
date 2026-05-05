<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Services\LineMessagingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function sendCode(Request $request, LineMessagingService $lineMessagingService)
    {
        $validated = $request->validate([
            'student_id' => 'required|string|max:50',
        ]);

        $studentId = trim((string) $validated['student_id']);
        $user = User::where('student_id', $studentId)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'ユーザーが見つかりません',
            ], Response::HTTP_NOT_FOUND);
        }

        $lineUserId = trim((string) ($user->line_user_id ?: $user->line_id ?: ''));
        if ($lineUserId === '') {
            return response()->json([
                'success' => false,
                'code' => 'LINE_NOT_LINKED',
                'message' => 'LINE連携が未完了です。管理者に連絡してください。',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $throttleKey = 'password-reset:send-code:' . $studentId;
        if (Cache::has($throttleKey)) {
            return response()->json([
                'success' => false,
                'code' => 'TOO_MANY_REQUESTS',
                'message' => 'しばらくしてから再度お試しください。',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $code = (string) random_int(100000, 999999);
        $record = PasswordResetCode::updateOrCreate(
            ['student_id' => $studentId],
            [
                'user_id' => $user->id,
                'line_user_id' => $lineUserId,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
                'sent_at' => now(),
                'used_at' => null,
                'attempts' => 0,
            ]
        );

        try {
            $response = $lineMessagingService->sendPasswordResetCode($lineUserId, $code);
            if (!$response->successful()) {
                throw new \RuntimeException('LINE API response: ' . $response->status());
            }
        } catch (\Throwable $e) {
            $record->delete();

            return response()->json([
                'success' => false,
                'code' => 'LINE_SEND_FAILED',
                'message' => '認証コードの送信に失敗しました',
            ], Response::HTTP_BAD_GATEWAY);
        }

        Cache::put($throttleKey, true, now()->addSeconds(60));

        return response()->json([
            'success' => true,
            'message' => '認証コードを送信しました',
        ]);
    }

    public function verifyAndUpdate(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|string|max:50',
            'code' => 'required|string|size:6',
            'new_password' => 'required|string|min:6|max:255',
        ]);

        $studentId = trim((string) $validated['student_id']);
        $resetCode = PasswordResetCode::where('student_id', $studentId)
            ->whereNull('used_at')
            ->first();

        if (!$resetCode) {
            return response()->json([
                'success' => false,
                'code' => 'CODE_NOT_FOUND',
                'message' => '認証コードが見つかりません',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($resetCode->expires_at && now()->greaterThan($resetCode->expires_at)) {
            $resetCode->delete();

            return response()->json([
                'success' => false,
                'code' => 'CODE_EXPIRED',
                'message' => '認証コードの有効期限が切れています',
            ], Response::HTTP_GONE);
        }

        if (!Hash::check($validated['code'], $resetCode->code_hash)) {
            $resetCode->increment('attempts');

            return response()->json([
                'success' => false,
                'code' => 'CODE_MISMATCH',
                'message' => '認証コードが一致しません',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::find($resetCode->user_id) ?: User::where('student_id', $studentId)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'ユーザーが見つかりません',
            ], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($user, $resetCode, $validated) {
            $user->update([
                'password' => Hash::make($validated['new_password']),
            ]);

            $resetCode->update([
                'used_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'パスワードを更新しました',
        ]);
    }
}