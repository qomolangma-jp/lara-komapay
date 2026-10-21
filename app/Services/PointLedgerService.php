<?php

namespace App\Services;

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PointLedgerService
{
    public const TYPES = ['charge', 'grant', 'adjustment', 'refund', 'purchase'];

    /**
     * 残高を増減し、台帳に記録する。ユーザー行ロックと台帳記録を同一トランザクションで行う。
     *
     * @param  int  $delta  付与は正、利用は負
     * @param  array{method?: ?string, description?: ?string, order_id?: ?int, charge_request_id?: ?int, transaction_type?: ?string, metadata?: ?array}  $extra
     */
    public static function apply(int $userId, int $delta, string $type, array $extra = []): PointTransaction
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException('Invalid point transaction type.');
        }

        return DB::transaction(function () use ($userId, $delta, $type, $extra) {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $before = (int) ($user->points_balance ?? 0);
            $after = $before + $delta;
            if ($after < 0) {
                throw new \DomainException('Insufficient points.');
            }

            $user->update(['points_balance' => $after]);

            return PointTransaction::create([
                'user_id' => $user->id,
                'order_id' => $extra['order_id'] ?? null,
                'charge_request_id' => $extra['charge_request_id'] ?? null,
                'type' => $type,
                'method' => $extra['method'] ?? null,
                'transaction_type' => $extra['transaction_type'] ?? $type,
                'amount' => abs($delta),
                'balance_before' => $before,
                'balance_after' => $after,
                'status' => 'completed',
                'description' => $extra['description'] ?? null,
                'metadata' => $extra['metadata'] ?? null,
            ]);
        });
    }
}
