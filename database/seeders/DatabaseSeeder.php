<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ユーザー作成
        $admin = User::create([
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'is_admin' => true,
            'name_2nd' => '管理',
            'name_1st' => '太郎',
        ]);

        $student = User::create([
            'username' => 'student',
            'password' => Hash::make('1234'),
            'is_admin' => false,
            'name_2nd' => '学生',
            'name_1st' => '花子',
            'status' => 'student',
        ]);

        $seller = User::create([
            'username' => 'seller',
            'password' => Hash::make('seller'),
            'is_admin' => false,
            'name_2nd' => '販売',
            'name_1st' => '次郎',
            'shop_name' => '学食A店舗',
            'status' => 'seller',
        ]);

        // 商品作成
        $products = [
            [
                'name' => '日替わり定食（ハンバーグ）',
                'price' => 500,
                'stock' => 20,
                'category' => '定食',
                'seller_id' => $seller->id,
                'description' => '国産牛を使用したジューシーなハンバーグ。サラダ・スープ付き。',
                'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => '特製カツカレー',
                'price' => 450,
                'stock' => 15,
                'category' => 'カレー',
                'seller_id' => $seller->id,
                'description' => 'サクサクのロースカツをトッピング。スパイスの効いた本格派。',
                'image_url' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => '醤油ラーメン',
                'price' => 400,
                'stock' => 30,
                'category' => '麺類',
                'seller_id' => $seller->id,
                'description' => '昔ながらの鶏ガラスープ。チャーシュー2枚入り。',
                'image_url' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => '唐揚げ単品（3個）',
                'price' => 150,
                'stock' => 50,
                'category' => 'サイド',
                'seller_id' => $seller->id,
                'description' => '秘伝のタレに漬け込んだ自慢の唐揚げ。小腹が空いた時に。',
                'image_url' => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'name' => 'シーザーサラダ',
                'price' => 200,
                'stock' => 15,
                'category' => 'サイド',
                'seller_id' => $seller->id,
                'description' => '新鮮野菜たっぷり。クルトンの食感がアクセント。',
                'image_url' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=800&q=80',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
