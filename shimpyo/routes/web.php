<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

// 로컬/메인 도메인에서 관리자로 들어가는 경로
Route::get('/admin/{any?}', [SpaController::class, 'admin'])->where('any', '.*');

// 나머지는 호스트명으로 차량 앱과 관리자를 가른다
Route::get('/{any?}', [SpaController::class, 'app'])->where('any', '^(?!api|build|storage).*$');
