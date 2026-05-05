<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditLogService
{
    public static function record(
        Request $request,
        string $action,
        string $targetType,
        ?int $targetId,
        array $beforeData = [],
        array $afterData = [],
        array $meta = []
    ): void {
        try {
            $actor = auth('sanctum')->user() ?: auth()->user();
            $actorName = null;

            if ($actor) {
                $actorName = $actor->display_name
                    ?: trim(($actor->name_2nd ?? '') . ' ' . ($actor->name_1st ?? ''))
                    ?: ($actor->username ?? null);
            }

            $payload = [
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'actor_user_id' => $actor->id ?? null,
                'actor_name' => $actorName,
                'http_method' => $request->method(),
                'endpoint' => $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'before_data' => empty($beforeData) ? null : $beforeData,
                'after_data' => empty($afterData) ? null : $afterData,
                'meta' => empty($meta) ? null : $meta,
            ];

            if (Schema::hasTable('audit_logs')) {
                AuditLog::create($payload);
            }

            Log::info('AUDIT_LOG', $payload);
        } catch (\Throwable $e) {
            Log::warning('Audit log record failed', [
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
