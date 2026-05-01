<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_image_upload_accepts_valid_file()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.jpg')->size(500); // 500 KB

        $response = $this->postJson('/api/master/upload-image', [
            'image' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data' => ['url', 'path', 'filename']]);
        $this->assertTrue($response->json('success'));
    }

    public function test_master_image_upload_rejects_large_file()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('big.jpg')->size(3000); // 3 MB

        $response = $this->postJson('/api/master/upload-image', [
            'image' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }
}
