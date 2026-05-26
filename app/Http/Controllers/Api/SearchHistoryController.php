<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSearchKeyword;
use Illuminate\Http\Request;

class SearchHistoryController extends Controller
{
    /**
     * ユーザーの検索キーワード履歴を取得
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $searchType = $request->query('search_type', 'product');
        $limit = $request->query('limit', 10);

        $keywords = UserSearchKeyword::where('user_id', $user->id)
            ->where('search_type', $searchType)
            ->latest('created_at')
            ->limit($limit)
            ->pluck('keyword')
            ->unique()
            ->values();

        return response()->json([
            'keywords' => $keywords,
        ]);
    }

    /**
     * 検索キーワードを保存
     */
    public function store(Request $request)
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'search_type' => 'required|string|in:product,order,news,cart',
        ]);

        // 空のキーワードは保存しない
        if (empty(trim($validated['keyword']))) {
            return response()->json(['error' => 'Keyword cannot be empty'], 400);
        }

        UserSearchKeyword::create([
            'user_id' => $user->id,
            'keyword' => $validated['keyword'],
            'search_type' => $validated['search_type'],
        ]);

        return response()->json([
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
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $searchType = $request->query('search_type', 'product');

        UserSearchKeyword::where('user_id', $user->id)
            ->where('search_type', $searchType)
            ->delete();

        return response()->json([
            'message' => 'Search history cleared',
        ]);
    }
}
