
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\MigrationController;

Route::get('/', function () {
    return file_get_contents(__DIR__.'/../public/welcome.html');
});

Route::get('/login', [PageController::class, 'login']);
Route::get('/student', [PageController::class, 'student']);
Route::get('/master', [App\Http\Controllers\MasterController::class, 'index'])->name('master.index');
Route::get('/master/users', [App\Http\Controllers\MasterController::class, 'users'])->name('master.users');
Route::get('/master/products', [App\Http\Controllers\MasterController::class, 'products'])->name('master.products');
Route::get('/migrate', [MigrationController::class, 'migrate']);
Route::get('/migrate-fresh', [MigrationController::class, 'fresh']);
