<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class ProductController extends Controller
{
    /**
     * 全商品を取得
     */
    public function index(Request $request)
    {
        try {
            $relations = ['vendor'];
            if (method_exists(Product::class, 'seller')) {
                $relations[] = 'seller';
            }

            if (method_exists(Product::class, 'category')) {
                $relations[] = 'category';
            }

            $query = Product::with($relations);
            $useListThumbnail = !$request->is('api/master/*');
            $favoriteIds = $this->normalizeIdList($request->input('favorite_ids'));

            // カテゴリでフィルタリング
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // 在庫がある商品のみ
            if ($request->get('available') === 'true') {
                $query->where('stock', '>', 0);
            }

            // キーワード検索
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
                });
            }
            // ソート（バックエンドから制御可能にする）
            $allowedSorts = ['name', 'price', 'stock', 'created_at', 'updated_at', 'category', 'seller_id'];
            $sortBy = $request->get('sort_by');
            $sortDir = strtolower((string) $request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

            if (!empty($favoriteIds)) {
                $placeholders = implode(',', array_fill(0, count($favoriteIds), '?'));
                $query->orderByRaw("CASE WHEN id IN ($placeholders) THEN 0 ELSE 1 END", $favoriteIds);
            }

            if ($sortBy && in_array($sortBy, $allowedSorts, true)) {
                $query->orderBy($sortBy, $sortDir);
            }
            // デフォルトは sort_order があればそれで並び替え
            if (! $request->has('sort_by') && Schema::hasColumn('products', 'sort_order')) {
                $query->orderBy('sort_order', 'asc');
            }

            $products = $query->get()
                ->filter(function ($item) {
                    return $item instanceof Product;
                })
                ->map(function (Product $item) use ($useListThumbnail, $favoriteIds) {
                    try {
                        $normalized = $this->normalizeProductResponse($item, $useListThumbnail);

                        // category/vendor が null でも落ちないように明示的にフォールバック
                        $normalized['category_name'] = data_get($item, 'category.name')
                            ?? ($normalized['category'] ?? '未設定');
                        if ($normalized['category_name'] === '') {
                            $normalized['category_name'] = '未設定';
                        }

                        $normalized['vendor_name'] = data_get($item, 'vendor.shop_name')
                            ?? data_get($item, 'vendor.display_name')
                            ?? $normalized['seller_name']
                            ?? '未設定';

                        $normalized['is_favorite'] = in_array((int) $item->id, $favoriteIds, true);
                        $normalized['favorite_rank'] = $normalized['is_favorite']
                            ? array_search((int) $item->id, $favoriteIds, true)
                            : null;

                        return $normalized;
                    } catch (\Throwable $itemError) {
                        \Log::warning('Product normalize skipped', [
                            'product_id' => $item->id ?? null,
                            'error' => $itemError->getMessage(),
                        ]);
                        // 画像整形エラーがあっても商品本体は返す
                        $fallback = $this->buildFallbackProductResponse($item);
                        $fallback['is_favorite'] = in_array((int) $item->id, $favoriteIds, true);
                        $fallback['favorite_rank'] = $fallback['is_favorite']
                            ? array_search((int) $item->id, $favoriteIds, true)
                            : null;
                        return $fallback;
                    }
                })
                ->filter(function ($item) {
                    return is_array($item);
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $products,
                'count' => $products->count(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Product index error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 管理画面からの並び替えを保存する
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:products,id',
        ]);

        $ids = $validated['order'];

        try {
            \DB::beginTransaction();
            foreach ($ids as $index => $id) {
                Product::where('id', $id)->update(['sort_order' => $index]);
            }
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => '並び順を保存しました',
            ]);
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('Product reorder failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '並び順の保存に失敗しました',
            ], 500);
        }
    }

    /**
     * 商品詳細を取得
     */
    public function show(int $id)
    {
        try {
            $relations = ['vendor'];
            if (method_exists(Product::class, 'seller')) {
                $relations[] = 'seller';
            }
            if (method_exists(Product::class, 'category')) {
                $relations[] = 'category';
            }

            $product = Product::with($relations)->find($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => '商品が見つかりません',
                ], Response::HTTP_NOT_FOUND);
            }

            $normalized = $this->normalizeProductResponse($product);

            return response()->json([
                'success' => true,
                'data' => $normalized,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Product show error', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 商品を作成（管理者のみ）
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'price' => 'required|integer|min:0',
                'stock' => 'required|integer|min:0',
                'daily_purchase_limit_per_user' => 'nullable|integer|min:1',
                'category' => 'nullable|string|max:50',
                'seller_id' => 'nullable|exists:users,id',
                'label' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'image_url' => 'nullable|string|max:500',
                'additional_image_urls' => 'nullable|array',
                'additional_image_urls.*' => 'nullable|string|max:500',
                'allergens' => 'nullable|string',
                'size_options' => 'nullable|array',
                'size_options.*.label' => 'nullable|string|max:30',
                'size_options.*.price_adjustment' => 'nullable|integer',
            ]);

            $validated = $this->sanitizeForSave($validated);
            $validated = $this->processSizeOptionsForSave($validated);
            $validated = $this->processImageForSave($validated);
            $validated = $this->processAdditionalImagesForSave($validated);

            if (!Schema::hasColumn('products', 'additional_image_urls')) {
                unset($validated['additional_image_urls']);
            }

            if (!Schema::hasColumn('products', 'size_options')) {
                unset($validated['size_options']);
            }

            if (!Schema::hasColumn('products', 'daily_purchase_limit_per_user')) {
                unset($validated['daily_purchase_limit_per_user']);
            }

            $product = Product::create($validated);
            $product->load('seller');
            $product = $this->normalizeProductResponse($product);

            return response()->json([
                'success' => true,
                'message' => '商品を作成しました',
                'data' => $product,
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Product store validation error', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '入力値の検証に失敗しました',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            \Log::error('Product store error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '商品の作成に失敗しました: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 商品を更新（管理者または販売者）
     */
    public function update(Request $request, Product $product)
    {
        try {
            \Log::info('Product update request', ['product_id' => $product->id, 'data' => $request->all()]);
            
            $user = auth('sanctum')->user();
            
            // 認証ユーザーがいる場合のみチェック（/api/master/* は認証不要）
            if ($user && !$user->isAdmin() && $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => '自分の商品のみ編集できます',
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:100',
                'price' => 'sometimes|integer|min:0',
                'stock' => 'sometimes|integer|min:0',
                'daily_purchase_limit_per_user' => 'nullable|integer|min:1',
                'category' => 'sometimes|string|max:50',
                'seller_id' => 'nullable|exists:users,id',
                'label' => 'nullable|string|max:50',
                'description' => 'sometimes|string',
                'image_url' => 'sometimes|string|max:500',
                'additional_image_urls' => 'nullable|array',
                'additional_image_urls.*' => 'nullable|string|max:500',
                'allergens' => 'nullable|string',
                'size_options' => 'nullable|array',
                'size_options.*.label' => 'nullable|string|max:30',
                'size_options.*.price_adjustment' => 'nullable|integer',
            ]);

            \Log::info('Validated data', $validated);

            $oldImageUrl = (string) ($product->image_url ?? '');

            $validated = $this->sanitizeForSave($validated);
            $validated = $this->processSizeOptionsForSave($validated);
            $validated = $this->processImageForSave($validated);
            $validated = $this->processAdditionalImagesForSave($validated);

            if (!Schema::hasColumn('products', 'additional_image_urls')) {
                unset($validated['additional_image_urls']);
            }

            if (!Schema::hasColumn('products', 'size_options')) {
                unset($validated['size_options']);
            }

            if (!Schema::hasColumn('products', 'daily_purchase_limit_per_user')) {
                unset($validated['daily_purchase_limit_per_user']);
            }

            if (array_key_exists('additional_image_urls', $validated)) {
                $existingGallery = $this->normalizeImageUrlArrayForSave($product->additional_image_urls ?? []);
                $incomingGallery = $this->normalizeImageUrlArrayForSave($validated['additional_image_urls'] ?? []);
                $validated['additional_image_urls'] = array_values(array_unique(array_merge($existingGallery, $incomingGallery)));
            }

            if (array_key_exists('image_url', $validated)) {
                $newImageUrl = (string) ($validated['image_url'] ?? '');
                if ($oldImageUrl !== '' && $oldImageUrl !== $newImageUrl) {
                    $this->deleteImageFileIfLocal($oldImageUrl);
                }
            }

            $product->update($validated);
            $product->load('seller');
            $productId = $product->id;
            $productData = $this->normalizeProductResponse($product);

            \Log::info('Product updated successfully', ['product_id' => $productId]);

            return response()->json([
                'success' => true,
                'message' => '商品を更新しました',
                'data' => $productData,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Product update validation error', [
                'product_id' => $product->id ?? null,
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '入力値の検証に失敗しました',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            $productId = isset($product) ? $product->id : null;
            \Log::error('Product update error', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '商品の更新に失敗しました: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 商品を削除（管理者または販売者）
     */
    public function destroy(Product $product)
    {
        try {
            $user = auth('sanctum')->user();

            // 認証ユーザーがいる場合のみチェック（/api/master/* は認証不要）
            if ($user && ! $user->isAdmin() && $product->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => '自分の商品のみ削除できます',
                ], Response::HTTP_FORBIDDEN);
            }

            // 画像削除（エラーがあってもスキップして続ける）
            try {
                $oldImageUrl = (string) ($product->image_url ?? '');
                if ($oldImageUrl !== '') {
                    $this->deleteImageFileIfLocal($oldImageUrl);
                }
                $this->deleteImageFilesIfLocal($this->normalizeImageUrlArrayForSave($product->additional_image_urls ?? []));
            } catch (\Throwable $imageError) {
                \Log::warning('Failed to delete image files', [
                    'product_id' => $product->id ?? null,
                    'error' => $imageError->getMessage(),
                ]);
                // 画像削除失敗は無視して続行
            }

            // 商品削除
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => '商品を削除しました',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => '商品が見つかりません',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Illuminate\Database\QueryException $e) {
            // 外部キー制約違反など DB エラー
            $message = '商品の削除に失敗しました';
            if (str_contains($e->getMessage(), 'FOREIGN KEY')) {
                $message = 'この商品は注文などで参照されているため削除できません';
            } else if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                $message = 'この商品は削除できない関連データがあります';
            }
            
            \Log::error('Product delete DB error', [
                'product_id' => $product->id ?? null,
                'error' => $e->getMessage(),
                'sql_state' => $e->getSQLState(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $message,
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            \Log::error('Product delete error', [
                'product_id' => $product->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '商品の削除に失敗しました: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 在庫数を更新（管理者または販売者）
     */
    public function updateStock(Request $request, Product $product)
    {
        $user = auth('sanctum')->user();
        
        // 認証ユーザーがいる場合のみチェック（/api/master/* は認証不要）
        if ($user && !$user->isAdmin() && $product->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => '自分の商品のみ更新できます',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $beforeStock = (int) $product->stock;
        $afterStock = (int) $validated['stock'];

        $product->update(['stock' => $afterStock]);
        $product->load('seller');

        AuditLogService::record(
            $request,
            'product.stock.updated',
            'product',
            (int) $product->id,
            ['stock' => $beforeStock],
            ['stock' => $afterStock],
            [
                'product_id' => (int) $product->id,
                'product_name' => (string) $product->name,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => '在庫を更新しました',
            'data' => $product,
        ]);
    }

    /**
     * カテゴリ一覧を取得
     */
    public function categories()
    {
        $categories = Product::distinct()->pluck('category')->filter();

        return response()->json([
            'success' => true,
            'data' => $categories->values(),
        ]);
    }

    public function importCsv(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240',
            ]);

            $csvFile = $validated['file'] ?? $request->file('file');
            if (!$csvFile || ! $csvFile->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSVファイルが正しくアップロードされていません',
                ], Response::HTTP_BAD_REQUEST);
            }

            $content = file_get_contents($csvFile->getRealPath());
            if ($content === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSVファイルを読み込めませんでした',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
                $encoding = mb_detect_encoding($content, ['UTF-8', 'SJIS-win', 'SJIS', 'EUC-JP', 'JIS', 'ISO-8859-1'], true);
                if ($encoding && strtolower($encoding) !== 'utf-8') {
                    $converted = mb_convert_encoding($content, 'UTF-8', $encoding);
                    if ($converted !== false) {
                        $content = $converted;
                    }
                }
            }
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            $tmpPath = tempnam(sys_get_temp_dir(), 'csv_import_');
            file_put_contents($tmpPath, $content);
            $rows = [];
            if (($handle = fopen($tmpPath, 'r')) !== false) {
                while (($data = fgetcsv($handle)) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
            @unlink($tmpPath);

            if (count($rows) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSVの内容が不正です',
                ], Response::HTTP_BAD_REQUEST);
            }

            $headerRow = array_shift($rows);
            $headerRow = array_map(fn ($value) => $this->normalizeCsvCellValue((string) $value), $headerRow);
            $headers = array_map([$this, 'normalizeImportHeader'], $headerRow);
            $headerMap = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $headerMap[$index] = $header;
                }
            }

            if (!in_array('name', $headerMap, true) && !in_array('id', $headerMap, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSVに商品IDまたは商品名の列が必要です',
                ], Response::HTTP_BAD_REQUEST);
            }

            $created = 0;
            $updated = 0;
            $errors = [];

            \DB::beginTransaction();

            foreach ($rows as $rowIndex => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $isEmptyRow = true;
                foreach ($row as $value) {
                    if (trim((string) $value) !== '') {
                        $isEmptyRow = false;
                        break;
                    }
                }
                if ($isEmptyRow) {
                    continue;
                }

                $record = [];
                foreach ($headerMap as $index => $field) {
                    if (!array_key_exists($index, $row)) {
                        continue;
                    }
                    $value = trim($this->normalizeCsvCellValue((string) $row[$index]));
                    if ($value === '') {
                        continue;
                    }
                    $record[$field] = $value;
                }

                if (empty($record)) {
                    continue;
                }

                if (isset($record['price'])) {
                    $record['price'] = preg_replace('/[^0-9\-]/', '', (string) $record['price']);
                }
                if (isset($record['stock'])) {
                    $record['stock'] = preg_replace('/[^0-9\-]/', '', (string) $record['stock']);
                }
                if (isset($record['seller'])) {
                    $sellerId = $this->resolveSellerIdFromCsvValue((string) $record['seller']);
                    if ($sellerId !== null) {
                        $record['seller_id'] = $sellerId;
                    }
                    unset($record['seller']);
                }
                if (isset($record['size_options'])) {
                    $record['size_options'] = $this->parseCsvSizeOptions((string) $record['size_options']);
                }

                if (isset($record['id']) && $record['id'] !== '') {
                    $productId = (int) $record['id'];
                    $product = Product::find($productId);
                    if ($product) {
                        unset($record['id']);
                        if (!empty($record)) {
                            $record = $this->sanitizeForSave($record);
                            $record = $this->processSizeOptionsForSave($record);
                            $record = $this->processImageForSave($record);
                            $record = $this->processAdditionalImagesForSave($record);
                            if (!Schema::hasColumn('products', 'additional_image_urls')) {
                                unset($record['additional_image_urls']);
                            }
                            if (!Schema::hasColumn('products', 'size_options')) {
                                unset($record['size_options']);
                            }
                            if (!Schema::hasColumn('products', 'daily_purchase_limit_per_user')) {
                                unset($record['daily_purchase_limit_per_user']);
                            }
                            $product->update($record);
                            $updated++;
                        }
                        continue;
                    }
                    unset($record['id']);
                }

                if (empty($record['name'])) {
                    $errors[] = sprintf('行%d: 商品名が必要です', $rowIndex + 2);
                    continue;
                }
                if (!isset($record['price']) || $record['price'] === '') {
                    $errors[] = sprintf('行%d: 価格が必要です', $rowIndex + 2);
                    continue;
                }
                if (!isset($record['stock']) || $record['stock'] === '') {
                    $record['stock'] = 0;
                }

                $record = $this->sanitizeForSave($record);
                $record = $this->processSizeOptionsForSave($record);
                $record = $this->processImageForSave($record);
                $record = $this->processAdditionalImagesForSave($record);
                if (!Schema::hasColumn('products', 'additional_image_urls')) {
                    unset($record['additional_image_urls']);
                }
                if (!Schema::hasColumn('products', 'size_options')) {
                    unset($record['size_options']);
                }
                if (!Schema::hasColumn('products', 'daily_purchase_limit_per_user')) {
                    unset($record['daily_purchase_limit_per_user']);
                }

                Product::create($record);
                $created++;
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => sprintf('CSVのインポートが完了しました。作成: %d件、更新: %d件。', $created, $updated),
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('Product importCsv failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'CSVのインポート中にエラーが発生しました: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function normalizeImportHeader(string $header): string
    {
        $value = trim($this->normalizeCsvCellValue((string) $header));
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        if (function_exists('mb_convert_kana')) {
            $value = mb_convert_kana($value, 'KV', 'UTF-8');
        }
        $value = function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
        $value = preg_replace('/[^\p{L}\p{N}]/u', '', $value);

        switch ($value) {
            case '商品id':
            case 'id':
                return 'id';
            case '商品名':
            case 'name':
                return 'name';
            case '価格':
            case 'price':
                return 'price';
            case '在庫':
            case 'stock':
                return 'stock';
            case 'カテゴリ':
            case 'category':
                return 'category';
            case '販売者':
            case 'seller':
            case '販売者名':
            case 'sellername':
                return 'seller';
            case 'seller_id':
            case 'sellerid':
                return 'seller_id';
            case 'ラベル':
            case 'label':
                return 'label';
            case 'アレルギー':
            case 'allergens':
                return 'allergens';
            case 'サイズ':
            case 'size':
            case 'sizeoptions':
                return 'size_options';
            default:
                return '';
        }
    }

    private function parseCsvSizeOptions(string $value): array
    {
        $value = trim($this->normalizeCsvCellValue($value));
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/[\/、,]+/', $value);
        $options = [];
        foreach ($parts as $part) {
            $label = trim($this->normalizeCsvCellValue((string) $part));
            if ($label === '') {
                continue;
            }
            $options[] = [
                'label' => $label,
                'price_adjustment' => 0,
            ];
        }

        return array_values($options);
    }

    private function resolveSellerIdFromCsvValue(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            $user = User::find((int) $value);
            if ($user) {
                return $user->id;
            }
        }

        $lower = function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
        $user = User::whereRaw('LOWER(shop_name) = ?', [$lower])
            ->orWhereRaw('LOWER(name_2nd) = ?', [$lower])
            ->orWhereRaw('LOWER(name_1st) = ?', [$lower])
            ->orWhereRaw('LOWER(CONCAT(name_2nd, " ", name_1st)) = ?', [$lower])
            ->first();

        return $user ? $user->id : null;
    }

    private function sanitizeForSave(array $data): array
    {
        // 文字列フィールド: null → 空文字
        foreach (['category', 'label', 'description', 'image_url', 'allergens', 'daily_purchase_limit_per_user'] as $field) {
            if (array_key_exists($field, $data) && is_null($data[$field])) {
                $data[$field] = $field === 'daily_purchase_limit_per_user' ? null : '';
            }
        }
        if (array_key_exists('additional_image_urls', $data) && is_null($data['additional_image_urls'])) {
            $data['additional_image_urls'] = [];
        }
        if (array_key_exists('size_options', $data) && is_null($data['size_options'])) {
            $data['size_options'] = [];
        }
        // 数値フィールド: null → 0
        foreach (['price', 'stock'] as $field) {
            if (array_key_exists($field, $data) && is_null($data[$field])) {
                $data[$field] = 0;
            }
        }
        return $data;
    }

    private function processSizeOptionsForSave(array $data): array
    {
        if (!array_key_exists('size_options', $data)) {
            return $data;
        }

        $source = $data['size_options'];
        if (is_string($source)) {
            $decoded = json_decode($source, true);
            $source = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($source)) {
            $data['size_options'] = [];
            return $data;
        }

        $normalized = [];
        foreach ($source as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim($this->normalizeCsvCellValue((string) ($item['label'] ?? '')));
            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'price_adjustment' => (int) ($item['price_adjustment'] ?? 0),
            ];
        }

        $data['size_options'] = array_values($normalized);

        return $data;
    }

    private function normalizeCsvCellValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $value = str_replace("\0", '', $value);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;

        if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        if (function_exists('mb_convert_encoding')) {
            foreach (['SJIS-win', 'CP932', 'SJIS', 'EUC-JP', 'JIS', 'ISO-8859-1'] as $encoding) {
                $converted = @mb_convert_encoding($value, 'UTF-8', $encoding);
                if ($converted !== false) {
                    if (!function_exists('mb_check_encoding') || mb_check_encoding($converted, 'UTF-8')) {
                        return $converted;
                    }
                }
            }
        }

        if (function_exists('iconv')) {
            $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($cleaned)) {
                return $cleaned;
            }
        }

        return $value;
    }

    private function processAdditionalImagesForSave(array $data): array
    {
        if (!array_key_exists('additional_image_urls', $data)) {
            return $data;
        }

        $data['additional_image_urls'] = $this->normalizeImageUrlArrayForSave($data['additional_image_urls']);

        return $data;
    }

    private function normalizeImageUrlArrayForSave($values): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            if (is_array($decoded)) {
                $values = $decoded;
            } else {
                $values = [$values];
            }
        }

        if (!is_array($values)) {
            return [];
        }

        $normalized = [];
        foreach ($values as $value) {
            $url = $this->normalizeUploadedImagePathForSave((string) $value);
            if ($url !== '') {
                $normalized[] = $url;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeIdList($values): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            if (is_array($decoded)) {
                $values = $decoded;
            } else {
                $values = preg_split('/\s*,\s*/', $values, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            }
        }

        if (!is_array($values)) {
            return [];
        }

        $ids = [];
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function normalizeUploadedImagePathForSave(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return '';
        }

        $parsedUrl = parse_url($imageUrl);
        $path = $parsedUrl['path'] ?? '';
        if ($path && (str_starts_with($path, '/images/') || str_starts_with($path, '/storage/images/'))) {
            return $path;
        }

        if (!str_contains($imageUrl, '/') && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imageUrl)) {
            return '/storage/images/' . ltrim($imageUrl, '/');
        }

        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            throw new \RuntimeException('画像URL入力は使用できません。画像ファイルをアップロードしてください。');
        }

        throw new \RuntimeException('画像の保存形式が不正です。画像ファイルをアップロードしてください。');
    }

    private function deleteImageFilesIfLocal(array $imageUrls): void
    {
        foreach ($imageUrls as $imageUrl) {
            $this->deleteImageFileIfLocal((string) $imageUrl);
        }
    }

    private function buildFallbackProductResponse(Product $product): array
    {
        $data = $product->toArray();

        $data['image_url'] = '';
        $data['thumbnail_url'] = '';
        $data['image_original_url'] = '';
        $data['additional_image_urls'] = [];
        $data['gallery_image_urls'] = [];

        $category = $data['category'] ?? '';
        $data['category_name'] = $category !== '' ? $category : '未設定';
        $data['category_id'] = $data['category_id'] ?? null;

        $seller = $product->vendor ?? $product->seller;
        $sellerName = optional($seller)->display_name
            ?? optional($seller)->shop_name
            ?? trim((optional($seller)->name_2nd ?? '') . ' ' . (optional($seller)->name_1st ?? ''));
        $data['seller_name'] = $sellerName !== '' ? $sellerName : '未設定';
        $data['vendor_id'] = $data['seller_id'] ?? null;
        $data['vendor_name'] = $data['seller_name'];

        if (empty($data['label'])) {
            $data['label'] = '未入力';
        }
        if (empty($data['allergens'])) {
            $data['allergens'] = '未入力';
        }

        $data['size_options'] = Schema::hasColumn('products', 'size_options')
            ? $this->normalizeSizeOptionsForResponse($data['size_options'] ?? [])
            : [];

        unset($data['seller']);

        return $data;
    }

    private function processImageForSave(array $data): array
    {
        if (!array_key_exists('image_url', $data)) {
            return $data;
        }

        $imageUrl = trim((string) $data['image_url']);
        if ($imageUrl === '') {
            $data['image_url'] = '';
            return $data;
        }

        $parsedUrl = parse_url($imageUrl);
        $path = $parsedUrl['path'] ?? '';
        if ($path && (str_starts_with($path, '/images/') || str_starts_with($path, '/storage/images/'))) {
            $data['image_url'] = $path;
            return $data;
        }

        if (!str_contains($imageUrl, '/') && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imageUrl)) {
            $data['image_url'] = '/storage/images/' . ltrim($imageUrl, '/');
            return $data;
        }

        // 外部URL入力は廃止。画像はアップロード済みのパスのみ許可する
        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            throw new \RuntimeException('画像URL入力は使用できません。画像ファイルをアップロードしてください。');
        }

        throw new \RuntimeException('画像の保存形式が不正です。画像ファイルをアップロードしてください。');
        return $data;
    }

    private function deleteImageFileIfLocal(string $imageUrl): void
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return;
        }

        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            $parsed = parse_url($imageUrl);
            $imageUrl = $parsed['path'] ?? '';
        }

        if ($imageUrl === '') {
            return;
        }

        $filename = basename($imageUrl);
        if ($filename === '' || str_contains($filename, '..')) {
            return;
        }

        $candidates = [
            storage_path('app/public/images/' . $filename),
            public_path('images/' . $filename),
            public_path('storage/images/' . $filename),
            storage_path('app/public/images/thumb_43_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function normalizeProductResponse(Product $product, bool $useListThumbnail = false): array
    {
        $data = $product->toArray();

        $rawImageUrl = (string) ($data['image_url'] ?? '');
        $data['image_url'] = $this->normalizeImageUrlForResponse($rawImageUrl);
        if ($useListThumbnail) {
            $thumbnailUrl = $this->buildThumbnailUrlForResponse($rawImageUrl);
            $data['thumbnail_url'] = $thumbnailUrl !== '' ? $thumbnailUrl : $data['image_url'];
            $data['image_original_url'] = $data['image_url'];
            $data['image_url'] = $data['thumbnail_url'];
        }

        $categoryRelation = null;
        if (method_exists($product, 'category') && $product->relationLoaded('category')) {
            $categoryRelation = $product->getRelation('category');
        }
        $categoryName = optional($categoryRelation)->name ?? ($data['category'] ?? '未設定');
        if ($categoryName === null || $categoryName === '') {
            $categoryName = '未設定';
        }
        $data['category_name'] = $categoryName;
        $data['category_id'] = $data['category_id'] ?? optional($categoryRelation)->id ?? null;

        if (empty($data['label'])) {
            $data['label'] = '未入力';
        }

        if (empty($data['allergens'])) {
            $data['allergens'] = '未入力';
        }

        $seller = $product->vendor ?? $product->seller;
        $sellerName = optional($seller)->display_name
            ?? optional($seller)->shop_name
            ?? trim((optional($seller)->name_2nd ?? '') . ' ' . (optional($seller)->name_1st ?? ''));
        $data['seller_name'] = $sellerName !== '' ? $sellerName : '未設定';
        $data['vendor_id'] = $data['seller_id'] ?? null;
        $data['vendor_name'] = $data['seller_name'];

        $galleryUrls = Schema::hasColumn('products', 'additional_image_urls')
            ? $this->normalizeImageUrlArrayForResponse($data['additional_image_urls'] ?? [])
            : [];
        $data['additional_image_urls'] = $galleryUrls;
        $data['gallery_image_urls'] = $galleryUrls;
        $galleryUrls = Schema::hasColumn('products', 'additional_image_urls')
            ? $this->normalizeImageUrlArrayForResponse($data['additional_image_urls'] ?? [])
            : [];
        $data['additional_image_urls'] = $galleryUrls;
        $data['gallery_image_urls'] = $galleryUrls;
        // Reactがオブジェクトを直接レンダリングしてクラッシュするのを防ぐため
        // sellerオブジェクト全体は除去し、seller_id（整数）とseller_name（文字列）でアクセスさせる
        unset($data['seller']);

        return $data;
    }

    private function normalizeImageUrlForResponse(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            return $imageUrl;
        }

        if (!str_contains($imageUrl, '/') && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imageUrl)) {
            $imageUrl = '/storage/images/' . $imageUrl;
        }

        if (str_starts_with($imageUrl, 'images/')) {
            $imageUrl = '/' . $imageUrl;
        }

        if (str_starts_with($imageUrl, 'storage/')) {
            $imageUrl = '/' . $imageUrl;
        }

        if (str_starts_with($imageUrl, '/')) {
            return URL::to($imageUrl);
        }

        return URL::to('/' . ltrim($imageUrl, '/'));
    }

    private function normalizeImageUrlArrayForResponse($values): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            if (is_array($decoded)) {
                $values = $decoded;
            } else {
                $values = [$values];
            }
        }

        if (!is_array($values)) {
            return [];
        }

        $normalized = [];
        foreach ($values as $value) {
            $url = $this->normalizeImageUrlForResponse((string) $value);
            if ($url !== '') {
                $normalized[] = $url;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeSizeOptionsForResponse($values): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            $values = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($values)) {
            return [];
        }

        $normalized = [];
        foreach ($values as $value) {
            if (!is_array($value)) {
                continue;
            }

            $label = trim((string) ($value['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'price_adjustment' => (int) ($value['price_adjustment'] ?? 0),
            ];
        }

        return array_values($normalized);
    }

    private function buildThumbnailUrlForResponse(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return '';
        }

        $localPath = '';

        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            $parsed = parse_url($imageUrl);
            $path = $parsed['path'] ?? '';
            if (str_starts_with($path, '/images/') || str_starts_with($path, '/storage/images/')) {
                $localPath = $path;
            }
        } elseif (str_starts_with($imageUrl, '/images/') || str_starts_with($imageUrl, '/storage/images/')) {
            $localPath = $imageUrl;
        } elseif (!str_contains($imageUrl, '/') && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imageUrl)) {
            $localPath = '/storage/images/' . $imageUrl;
        }

        if ($localPath === '') {
            return '';
        }

        $sourceAbsolutePath = str_starts_with($localPath, '/storage/images/')
            ? storage_path('app/public/images/' . basename($localPath))
            : public_path(ltrim($localPath, '/'));
        if (!is_file($sourceAbsolutePath) || !function_exists('imagecreatefromstring')) {
            return '';
        }

        $pathInfo = pathinfo($sourceAbsolutePath);
        $thumbFilename = 'thumb_43_' . ($pathInfo['filename'] ?? 'image') . '.jpg';
        $thumbRelativePath = '/storage/images/' . $thumbFilename;
        $thumbAbsolutePath = storage_path('app/public/images/' . $thumbFilename);

        if (!is_file($thumbAbsolutePath)) {
            $imageBinary = @file_get_contents($sourceAbsolutePath);
            if ($imageBinary === false) {
                return '';
            }

            $sourceImage = @imagecreatefromstring($imageBinary);
            if (!$sourceImage) {
                return '';
            }

            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);

            $targetWidth = 800;
            $targetHeight = 600;
            $thumbImage = imagecreatetruecolor($targetWidth, $targetHeight);
            imagecopyresampled(
                $thumbImage,
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

            imagejpeg($thumbImage, $thumbAbsolutePath, 88);
            imagedestroy($sourceImage);
            imagedestroy($thumbImage);
        }

        return $this->normalizeImageUrlForResponse($thumbRelativePath);
    }
}
