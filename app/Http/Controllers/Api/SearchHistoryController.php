<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSearchKeyword;
use Illuminate\Http\Request;

class SearchHistoryController extends Controller
{
    const MAX_KEYWORDS_PER_USER = 10;

    /**
     * ユーザーの検索キーワード履歴を取得
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $searchType = $request->query('search_type', 'product');
        $limit = $request->query('limit', 5);

        $keywords = UserSearchKeyword::where('user_id', $user->id)
            ->where('search_type', $searchType)
            ->latest('created_at')
            ->limit($limit)
            ->pluck('keyword')
            ->unique()
            ->values()
            ->toArray();

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
        $user = auth('sanctum')->user() ?? auth()->user();
        
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
        $searchType = $validated['search_type'];

        // 既に存在する同じキーワードを削除（更新のため）
        UserSearchKeyword::where('user_id', $user->id)
            ->where('search_type', $searchType)
            ->where('keyword', $keyword)
            ->delete();

        // 新しいキーワードを作成
        UserSearchKeyword::create([
            'user_id' => $user->id,
            'keyword' => $keyword,
            'search_type' => $searchType,
        ]);

        // ユーザーごとにMAX_KEYWORDS_PER_USER件以上古いものを削除
        $excessCount = UserSearchKeyword::where('user_id', $user->id)
            ->where('search_type', $searchType)
            ->count() - self::MAX_KEYWORDS_PER_USER;

        if ($excessCount > 0) {
            UserSearchKeyword::where('user_id', $user->id)
                ->where('search_type', $searchType)
                ->oldest('created_at')
                ->limit($excessCount)
                ->delete();
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
        $user = auth('sanctum')->user() ?? auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $searchType = $request->query('search_type', 'product');

        UserSearchKeyword::where('user_id', $user->id)
            ->where('search_type', $searchType)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Search history cleared',
        ]);
    }
}
