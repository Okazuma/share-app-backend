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
            ->withServiceAccount(env('FIREBASE_CREDENTIALS'));

        $this->auth = $factory->createAuth();
    }



    public function index()
    {
        $posts = Post::withCount('likes') // 投稿データのみ取得
                    ->orderBy('created_at', 'desc')
                    ->get();

        // 投稿に関連する全ユーザーのIDを取得（重複を除く）
        $userIds = $posts->pluck('user_id')->unique()->toArray();

        // Firebaseから全ユーザー情報を一括取得
        $users = [];
        foreach ($userIds as $uid) {
            try {
                $user = $this->auth->getUser($uid);
                $users[$uid] = $user->displayName ?? "Unknown";
            } catch (\Exception $e) {
                $users[$uid] = "Unknown";
            }
        }

        // 各投稿にユーザー名を追加
        $postsWithUserNames = $posts->map(function ($post) use ($users) {
            $post->user_name = $users[$post->user_id] ?? "Unknown";
            return $post;
        });

        return response()->json($postsWithUserNames);
    }



    public function show($postId)
    {
        $post = Post::withCount('likes')->find($postId);

        if (!$post) {
            return response()->json(['message' => '投稿が見つかりません'], 404);
        }

        try {
            $user = $this->auth->getUser($post->user_id);
            $post->user_name = $user->displayName ?? "Unknown";
        } catch (\Exception $e) {
            $post->user_name = "Unknown";
        }

        return response()->json($post);
    }



    public function store(PostRequest $request)
    {
        $token = $request->bearerToken();
        \Log::info('Received token: ' . $token);

        $verifiedIdToken = $this->auth->verifyIdToken($token);
        $firebaseUid = $verifiedIdToken->claims()->get('sub');

        $userName = $request->input('user_name', 'Unknown');

        $post = Post::create([
            'user_id' => $firebaseUid,
            'content' => $request->validated()['content'],
        ]);

        return response()->json([
            'id' => $post->id,
            'content' => $post->content,
            'user_id' => $post->user_id,
            'user_name' => $userName,
            'likes' => 0,
            'created_at' => $post->created_at,
        ]);
    }



    public function update(PostRequest $request,$postId)
    {
        try {

            $token = $request->bearerToken();
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            $post = Post::findOrFail($postId);

            if ($post->user_id !== $firebaseUid) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $post->update($request->validated());

            return response()->json([
                'message'=> 'Post update successfully',
                'post' => $post
            ],200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Something went wrong'],500);
        }
    }



    public function destroy(Request $request,$postId)
    {
        $token = $request->bearerToken();
        $verifiedIdToken = $this->auth->verifyIdToken($token);
        $firebaseUid = $verifiedIdToken->claims()->get('sub');

        $post = Post::findOrFail($postId);

        if ($post->user_id !== $firebaseUid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $post->delete();

        return response()->json(['message'=> 'Post deleted',200]);
    }
}

