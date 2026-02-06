
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\MigrationController;

Route::get('/', function () {
    return file_get_contents(__DIR__.'/../public/welcome.html');
});

Route::get('/login', [PageController::class, 'login']);
Route::get('/master', [PageController::class, 'master']);
Route::get('/student', [PageController::class, 'student']);
Route::get('/admin', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.index');
Route::get('/migrate', [MigrationController::class, 'migrate']);
Route::get('/migrate-fresh', [MigrationController::class, 'fresh']);
Route::get('/admin/users', [App\Http\Controllers\AdminController::class, 'users'])->name('admin.users');
