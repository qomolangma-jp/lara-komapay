
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\MigrationController;

Route::get('/', function () {
    return file_get_contents(__DIR__.'/../public/welcome.html');
});

Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/student', [PageController::class, 'student'])->name('student');
Route::get('/master', [App\Http\Controllers\MasterController::class, 'index'])->name('master.index');
Route::get('/master/users', [App\Http\Controllers\MasterController::class, 'users'])->name('master.users');
Route::get('/master/products', [App\Http\Controllers\MasterController::class, 'products'])->name('master.products');
Route::get('/master/orders', [App\Http\Controllers\MasterController::class, 'orders'])->name('master.orders');
Route::get('/master/news', [App\Http\Controllers\MasterController::class, 'news'])->name('master.news');
Route::get('/master/stats', [App\Http\Controllers\MasterController::class, 'stats'])->name('master.stats');
Route::get('/master/cart', [App\Http\Controllers\MasterController::class, 'cart'])->name('master.cart');
Route::get('/master/logs', [App\Http\Controllers\MasterController::class, 'logs'])->name('master.logs');

// マイグレーション管理（管理者のみ）
Route::get('/master/migration', [MigrationController::class, 'index'])->name('master.migration');
Route::get('/migration/status', [MigrationController::class, 'status']);
Route::post('/migration/migrate', [MigrationController::class, 'migrate']);
Route::post('/migration/rollback', [MigrationController::class, 'rollback']);
Route::post('/migration/clear-cache', [MigrationController::class, 'clearCache']);
Route::post('/migration/check-table', [MigrationController::class, 'checkTable']);

// 旧形式のマイグレーション（後方互換性のため保持）
Route::get('/migrate', [MigrationController::class, 'migrate']);
Route::get('/migrate-fresh', [MigrationController::class, 'fresh']);
