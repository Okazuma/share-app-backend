<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



// 投稿一覧の取得
Route::get('/posts',[PostController::class,'index']);
// 特定の投稿を取得
Route::get('/posts/{id}',[PostController::class,'show']);
// 投稿の作成
Route::post('/posts',[PostController::class,'store']);
// 投稿の更新
Route::put('/posts/{id}',[PostController::class,'update']);
// 投稿の削除
Route::delete('/posts/{id}',[PostController::class,'destroy']);



// 特定の投稿に紐づくコメントを取得
Route::get('/comments/post/{postId}', [CommentController::class, 'getCommentByPost']);
// コメントの作成
Route::post('/comments', [CommentController::class, 'store']);
// コメントの削除
Route::delete('/comments/{id}', [CommentController::class, 'destroy']);




// Likeを追加
Route::post('/likes', [LikeController::class, 'store']);
// Likeを削除
Route::delete('/likes/{id}', [LikeController::class, 'destroy']);


