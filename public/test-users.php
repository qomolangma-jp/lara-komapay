<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: application/json');

try {
    $users = App\Models\User::select('id', 'username', 'name_2nd', 'name_1st', 'student_id', 'status', 'is_admin', 'shop_name', 'line_id', 'email')
        ->orderBy('name_2nd')
        ->orderBy('name_1st')
        ->paginate(100);
    
    echo json_encode([
        'success' => true,
        'count' => count($users->items()),
        'data' => $users->items(),
        'pagination' => [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ],
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
