<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $filename);

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => '/images/' . $filename,
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
