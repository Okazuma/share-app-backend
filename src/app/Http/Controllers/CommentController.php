<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Http\Requests\CommentRequest;

class CommentController extends Controller
{
    // 特定の投稿に関連するコメントを取得
    public function getCommentByPost($postId)
    {
        $comments = Comment::where('post_id',$postId)->get();
        return response()->json($comments);
    }

    // コメントを作成
    public function store(CommentRequest $request)
    {
        // ヘッダーからトークンを取得
        $token = $request->bearerToken();

        // Firebaseトークンの検証
        $verifiedIdToken = $auth->verifyIdToken($token);

        // トークンからFirebase UIDを取得
        $firebaseUid = $verifiedIdToken->claims()->get('sub');

        $comment = Comment::create([
            'user_id' => $firebaseUid,  // FirebaseのUIDをuser_idとして使用
            'post_id' => $request->validated()['post_id'],
            'message' => $request->validated()['message'],
        ]);
        // 成功レスポンス
        return response()->json($comment, 201);
    }
}
