<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

Route::get('/', function () {
    return file_get_contents(__DIR__.'/../public/welcome.html');
});

Route::get('/login', [PageController::class, 'login']);
Route::get('/master', [PageController::class, 'master']);
Route::get('/student', [PageController::class, 'student']);
