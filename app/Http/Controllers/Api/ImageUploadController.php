<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());

            // 1) 基本は storage/app/public/images へ保存
            $storageImagesDir = storage_path('app/public/images');
            if (!is_dir($storageImagesDir)) {
                mkdir($storageImagesDir, 0755, true);
            }
            $image->move($storageImagesDir, $filename);

            $storagePublicDir = storage_path('app/public');
            $publicStorageDir = public_path('storage');

            // 2) storage:link 未実行環境では可能ならリンクを作成
            if (!is_link($publicStorageDir) && !is_dir($publicStorageDir)) {
                @symlink($storagePublicDir, $publicStorageDir);
            }

            $urlPath = '/storage/images/' . $filename;
            $publicImagePath = public_path('images/' . $filename);

            // 3) リンク不可環境向けフォールバック（public/imagesへ複製）
            if (!is_link($publicStorageDir) && !is_dir($publicStorageDir)) {
                $publicImagesDir = public_path('images');
                if (!is_dir($publicImagesDir)) {
                    mkdir($publicImagesDir, 0755, true);
                }

                @copy($storageImagesDir . DIRECTORY_SEPARATOR . $filename, $publicImagePath);
                $urlPath = '/images/' . $filename;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => URL::to($urlPath),
                    'path' => $urlPath,
                    'filename' => $filename
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => '画像のアップロードに失敗しました'
        ], 400);
    }
}
