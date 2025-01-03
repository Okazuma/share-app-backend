<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Http\Requests\PostRequest;


class PostController extends Controller
{
    // 投稿一覧の取得
    public function index()
    {
        $posts = Post::all();
        return response()->json($posts);
    }


    // 特定の投稿を取得
    public function show($id)
    {
        return Post::findOrFail($id);
    }


    // 投稿の作成（追加）
    public function store(PostRequest $request)
    {
        // トークンを取得して検証
        $token = $request->bearerToken();
        $verifiedIdToken = $this->auth->verifyIdToken($token);
        $firebaseUid = $verifiedIdToken->claims()->get('sub');

        $post = Post::create([
            'user_id' => $firebaseUid, // Firebase UIDをuser_idに保存
            'content' => $request->validated()['content'], // バリデート済みデータ
        ]);
        return response()->json($post,201);
    }


    // 投稿の更新
    public function update(PostRequest $request,$id)
    {
        // トークンを取得して検証
        $token = $request->bearerToken();
        $verifiedIdToken = $this->auth->verifyIdToken($token);
        $firebaseUid = $verifiedIdToken->claims()->get('sub');

        $post = Post::findOrFail($id);
        // 自分の投稿のみ編集可能にする
        if ($post->user_id !== $firebaseUid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $post->update([
            'content' => $request->validated()['content'], // バリデート済みデータ
        ]);
        return response()->json($post,200);
    }


    // 投稿の削除
    public function destroy($id)
    {
        // トークンを取得して検証
        $token = $request->bearerToken();
        $verifiedIdToken = $this->auth->verifyIdToken($token);
        $firebaseUid = $verifiedIdToken->claims()->get('sub');

        $post = Post::findOrFail($id);
        // 自分の投稿のみ削除可能にする
        if ($post->user_id !== $firebaseUid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $post->delete();

        return response()->json(['message'=> 'Post deleted',200]);
    }
}

