<?php

use App\Models\Product;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('products:normalize-images-43', function () {
    if (!function_exists('imagecreatefromstring')) {
        $this->error('GDライブラリが有効でないため実行できません');
        return;
    }

    $products = Product::query()
        ->whereNotNull('image_url')
        ->where('image_url', '!=', '')
        ->orderBy('id')
        ->get();

    $processed = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($products as $product) {
        $imageUrl = trim((string) $product->image_url);

        if ($imageUrl === '') {
            $skipped++;
            continue;
        }

        $parsed = parse_url($imageUrl);
        $path = $parsed['path'] ?? '';
        if ($path && str_starts_with($path, '/images/processed_')) {
            $skipped++;
            continue;
        }

        if (!preg_match('/^https?:\/\//i', $imageUrl)) {
            $skipped++;
            continue;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'KomaPayImageProcessor/1.0'])
                ->get($imageUrl);

            if (!$response->successful()) {
                throw new \RuntimeException('HTTP ' . $response->status());
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            if (!str_starts_with($contentType, 'image/')) {
                throw new \RuntimeException('画像ではないレスポンス');
            }

            $sourceImage = imagecreatefromstring($response->body());
            if (!$sourceImage) {
                throw new \RuntimeException('画像データを読み込めません');
            }

            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);

            $targetWidth = 1200;
            $targetHeight = 900;
            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

            imagecopyresampled(
                $targetImage,
                $sourceImage,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $sourceWidth,
                $sourceHeight
            );

            $imagesDir = public_path('images');
            if (!is_dir($imagesDir)) {
                mkdir($imagesDir, 0755, true);
            }

            $filename = 'processed_' . now()->format('YmdHis') . '_' . $product->id . '_' . bin2hex(random_bytes(3)) . '.jpg';
            $savePath = $imagesDir . DIRECTORY_SEPARATOR . $filename;
            $saved = imagejpeg($targetImage, $savePath, 90);

            imagedestroy($sourceImage);
            imagedestroy($targetImage);

            if (!$saved) {
                throw new \RuntimeException('保存に失敗しました');
            }

            $product->image_url = '/images/' . $filename;
            $product->save();
            $processed++;
            $this->info("[OK] Product #{$product->id} {$product->name}");
        } catch (\Throwable $e) {
            $failed++;
            $this->warn("[NG] Product #{$product->id} {$product->name} - {$e->getMessage()}");
        }
    }

    $this->newLine();
    $this->info("完了: processed={$processed}, skipped={$skipped}, failed={$failed}");
})->purpose('既存商品の画像URLを4:3加工画像へ一括置換');
