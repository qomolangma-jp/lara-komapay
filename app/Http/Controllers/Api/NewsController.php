<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NewsController extends Controller
{
    /**
     * お知らせ一覧を取得
     */
    public function index()
    {
        $news = News::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $news,
        ]);
    }

    /**
     * お知らせを投稿（管理者のみ）
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'boolean',
        ]);

        $news = News::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_published' => $validated['is_published'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'お知らせを投稿しました',
            'data' => $news,
        ], Response::HTTP_CREATED);
    }

    /**
     * お知らせを更新（管理者のみ）
     */
    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'boolean',
        ]);

        $news->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_published' => $validated['is_published'] ?? $news->is_published,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'お知らせを更新しました',
            'data' => $news,
        ]);
    }

    /**
     * お知らせを削除（管理者のみ）
     */
    public function destroy(News $news)
    {
        $news->delete();

        return response()->json([
            'success' => true,
            'message' => 'お知らせを削除しました',
        ]);
    }
}
