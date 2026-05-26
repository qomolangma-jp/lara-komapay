<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSearchKeyword;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SearchHistoryController extends Controller
{
    const MAX_KEYWORDS_PER_USER = 10;
    const ALLOWED_SEARCH_TYPES = ['product', 'order', 'news', 'cart'];

    private function resolveUser(Request $request)
    {
        return $request->user('sanctum') ?? auth('sanctum')->user() ?? auth()->user();
    }

    private function ensureTableReady()
    {
        if (!Schema::hasTable('user_search_keywords')) {
            Log::error('search history table is missing', [
                'table' => 'user_search_keywords',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search history table is not available',
            ], 503);
        }

        return null;
    }

    /**
     * ユーザーの検索キーワード履歴を取得
     */
    public function index(Request $request)
    {
        if ($response = $this->ensureTableReady()) {
            return $response;
        }

        $user = $this->resolveUser($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $searchType = strtolower((string) $request->query('search_type', 'product'));
        $limit = (int) $request->query('limit', 5);
        $limit = max(1, min($limit, 10));

        if (!in_array($searchType, self::ALLOWED_SEARCH_TYPES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search_type',
            ], 422);
        }

        try {
            $keywords = UserSearchKeyword::where('user_id', $user->id)
                ->where('search_type', $searchType)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('keyword')
                ->unique()
                ->values()
                ->toArray();
        } catch (Throwable $exception) {
            Log::error('Failed to load search history', [
                'user_id' => $user->id,
                'search_type' => $searchType,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load search history',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $keywords,
        ]);
    }

    /**
     * 検索キーワードを保存
     */
    public function store(Request $request)
    {
        if ($response = $this->ensureTableReady()) {
            return $response;
        }

        $user = $this->resolveUser($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'search_type' => 'required|string|in:product,order,news,cart',
        ]);

        // 空のキーワードは保存しない
        if (empty(trim($validated['keyword']))) {
            return response()->json([
                'success' => false,
                'message' => 'Keyword cannot be empty'
            ], 400);
        }

        $keyword = trim($validated['keyword']);
        $searchType = strtolower($validated['search_type']);

        if (!in_array($searchType, self::ALLOWED_SEARCH_TYPES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search_type',
            ], 422);
        }

        try {
            DB::transaction(function () use ($user, $keyword, $searchType) {
                UserSearchKeyword::where('user_id', $user->id)
                    ->where('search_type', $searchType)
                    ->where('keyword', $keyword)
                    ->delete();

                UserSearchKeyword::create([
                    'user_id' => $user->id,
                    'keyword' => $keyword,
                    'search_type' => $searchType,
                ]);

                $overflowIds = UserSearchKeyword::where('user_id', $user->id)
                    ->where('search_type', $searchType)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->skip(self::MAX_KEYWORDS_PER_USER)
                    ->pluck('id');

                if ($overflowIds->isNotEmpty()) {
                    UserSearchKeyword::whereIn('id', $overflowIds)->delete();
                }
            });
        } catch (QueryException $exception) {
            Log::error('Failed to save search history', [
                'user_id' => $user->id,
                'search_type' => $searchType,
                'keyword' => $keyword,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save search history',
            ], 500);
        } catch (Throwable $exception) {
            Log::error('Unexpected error while saving search history', [
                'user_id' => $user->id,
                'search_type' => $searchType,
                'keyword' => $keyword,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save search history',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Keyword saved successfully',
        ], 201);
    }

    /**
     * 検索キーワード履歴をクリア
     */
    public function destroy(Request $request)
    {
        if ($response = $this->ensureTableReady()) {
            return $response;
        }

        $user = $this->resolveUser($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $searchType = strtolower((string) $request->query('search_type', 'product'));

        if (!in_array($searchType, self::ALLOWED_SEARCH_TYPES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search_type',
            ], 422);
        }

        try {
            UserSearchKeyword::where('user_id', $user->id)
                ->where('search_type', $searchType)
                ->delete();
        } catch (Throwable $exception) {
            Log::error('Failed to clear search history', [
                'user_id' => $user->id,
                'search_type' => $searchType,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear search history',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Search history cleared',
        ]);
    }
}
