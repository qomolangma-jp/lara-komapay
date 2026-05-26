<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SearchHistoryController extends Controller
{
    public const MAX_KEYWORDS_PER_USER = 10;
    private const ALLOWED_SEARCH_TYPES = ['product', 'order', 'news', 'cart'];

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

    private function isAllowedSearchType(string $searchType): bool
    {
        return in_array($searchType, self::ALLOWED_SEARCH_TYPES, true);
    }

    public function index(Request $request)
    {
        if ($response = $this->ensureTableReady()) {
            return $response;
        }

        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $searchType = strtolower((string) $request->query('search_type', 'product'));
        $limit = max(1, min((int) $request->query('limit', 5), self::MAX_KEYWORDS_PER_USER));

        if (!$this->isAllowedSearchType($searchType)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search_type',
            ], 422);
        }

        try {
            $keywords = DB::table('user_search_keywords')
                ->where('user_id', $user->id)
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

    public function store(Request $request)
    {
        if ($response = $this->ensureTableReady()) {
            return $response;
        }

        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'search_type' => 'required|string|in:product,order,news,cart',
        ]);

        $keyword = trim((string) $validated['keyword']);
        if ($keyword === '') {
            return response()->json([
                'success' => false,
                'message' => 'Keyword cannot be empty',
            ], 400);
        }

        $searchType = strtolower((string) $validated['search_type']);
        if (!$this->isAllowedSearchType($searchType)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search_type',
            ], 422);
        }

        try {
            DB::transaction(function () use ($user, $keyword, $searchType) {
                $table = DB::table('user_search_keywords');

                $table->where('user_id', $user->id)
                    ->where('search_type', $searchType)
                    ->where('keyword', $keyword)
                    ->delete();

                $table->insert([
                    'user_id' => $user->id,
                    'keyword' => $keyword,
                    'search_type' => $searchType,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $overflowIds = DB::table('user_search_keywords')
                    ->where('user_id', $user->id)
                    ->where('search_type', $searchType)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->skip(self::MAX_KEYWORDS_PER_USER)
                    ->pluck('id');

                if ($overflowIds->isNotEmpty()) {
                    DB::table('user_search_keywords')
                        ->whereIn('id', $overflowIds)
                        ->delete();
                }
            });
        } catch (Throwable $exception) {
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
        }

        return response()->json([
            'success' => true,
            'message' => 'Keyword saved successfully',
        ], 201);
    }

    public function destroy(Request $request)
    {
        if ($response = $this->ensureTableReady()) {
            return $response;
        }

        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $searchType = strtolower((string) $request->query('search_type', 'product'));
        if (!$this->isAllowedSearchType($searchType)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search_type',
            ], 422);
        }

        try {
            DB::table('user_search_keywords')
                ->where('user_id', $user->id)
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
