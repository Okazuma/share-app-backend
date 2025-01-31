<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Http\Requests\PostRequest;
use Illuminate\Support\Facades\Log;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;

class PostController extends Controller
{
    protected $auth;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(env('FIREBASE_CREDENTIALS'))
            ->withProjectId(env('FIREBASE_PROJECT_ID'));

        $this->auth = $factory->createAuth();
    }


    // 投稿一覧の取得
    public function index()
    {
        $posts = Post::withCount('likes')->get();
        return response()->json($posts);
    }



    // 特定の投稿を取得
    // public function show($id)
    // {
    //     return Post::findOrFail($id);
    // }

    public function show($postId)
{
    $post = Post::withCount('likes')->find($postId);

    if (!$post) {
        return response()->json(['message' => '投稿が見つかりません'], 404);
    }

    return response()->json($post);
}



    // 投稿の作成（追加）
    public function store(PostRequest $request)
    {
        $token = $request->bearerToken();
        \Log::info('Received token: ' . $token);

        $verifiedIdToken = $this->auth->verifyIdToken($token);
        $firebaseUid = $verifiedIdToken->claims()->get('sub');

        $post = Post::create([
            'user_id' => $firebaseUid,
            'content' => $request->validated()['content'],
        ]);

        return response()->json([
            'id' => $post->id,
            'content' => $post->content,
            'user_id' => $post->user_id,
            'likes' => 0,
        ]);
    }



    // 投稿の更新
    public function update(PostRequest $request,$id)
    {
        $token = $request->bearerToken();
        $verifiedIdToken = $this->auth->verifyIdToken($token);
        $firebaseUid = $verifiedIdToken->claims()->get('sub');

        $post = Post::findOrFail($id);

        // 自分の投稿のみ編集可能にする
        if ($post->user_id !== $firebaseUid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $post->update([
            'content' => $request->validated()['content'],
        ]);
        return response()->json($post,200);
    }



    // 投稿の削除
    public function destroy(Request $request,$id)
    {
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

