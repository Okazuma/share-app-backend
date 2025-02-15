<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Http\Requests\PostRequest;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Cache;

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
        $start = microtime(true);

        $queryStart = microtime(true);
        $posts = Post::withCount('likes')->orderBy('created_at', 'desc')->get();
        Log::info('🔥 投稿一覧取得時間: ' . (microtime(true) - $queryStart) * 1000 . ' ms');

        $userIds = $posts->pluck('user_id')->unique()->toArray();

        $cacheKey = 'firebase_users_' . md5(json_encode($userIds));
        $cachedData = Cache::get($cacheKey);
        Log::info('🔥 投稿一覧ユーザーキャッシュデータ: ' . json_encode($cachedData));

        $users = Cache::remember($cacheKey, 600, function () use ($userIds) {
            $firebaseStart = microtime(true);
            try {
                $userRecords = $this->auth->getUsers($userIds);
                Log::info('🔥 投稿一覧Firebaseユーザー取得時間: ' . (microtime(true) - $firebaseStart) * 1000 . ' ms');

                $users = [];
                foreach ($userRecords as $uid => $userRecord) {
                    $users[$uid] = $userRecord->displayName ?? "Unknown";
                }
                return $users;
            } catch (\Exception $e) {
                Log::error('Firebase getUsers error: ' . $e->getMessage());
                return [];
            }
        });

        $mapStart = microtime(true);
        $postsWithUserNames = $posts->map(function ($post) use ($users) {
            $post->user_name = $users[$post->user_id] ?? "Unknown";
            return $post;
        });
        Log::info('🔥 投稿一覧データマッピング時間: ' . (microtime(true) - $mapStart) * 1000 . ' ms');
        Log::info('🔥 投稿一覧トータル処理時間: ' . (microtime(true) - $start) * 1000 . ' ms');

        return response()->json($postsWithUserNames);
    }




    public function show($postId){
        $cacheKey = "post_{$postId}";
        $startTotal = microtime(true);

        $startCache = microtime(true);
        $post = Cache::get($cacheKey);
        $cacheTime = microtime(true) - $startCache;
        \Log::info(" 🔥 特定の投稿キャッシュ取得時間: {$cacheTime}秒");

        if (!$post) {
            $startDB = microtime(true);
            $post = Post::find($postId);
            $dbTime = microtime(true) - $startDB;
            \Log::info(" 🔥 特定の投稿データ取得時間: {$dbTime}秒");

            if(!$post){
                return response()->json(['message' => '投稿が見つかりません'],404);
            }

            $startUser = microtime(true);
            try{
                $user = $this->auth->getUser($post->user_id);
                $post->user_name = $user->displayName ?? "Unknown";
            }catch(\Exception $e){
                $post->user_name = "Unknown";
            }
            $userTime = microtime(true) - $startUser;
            \Log::info(" 🔥 特定の投稿ユーザー情報取得時間: {$userTime}秒");

            $startCachePut = microtime(true);
            $postData = $post->toArray();
            Cache::put($cacheKey, $postData, now()->addMinutes(10));
            $cachePutTime = microtime(true) - $startCachePut;
            \Log::info(" 🔥 特定の投稿キャッシュ保存時間: {$cachePutTime}秒");
        }
        $totalTime = microtime(true) - $startTotal;
        \Log::info(" 🔥 特定の投稿全体の処理時間: {$totalTime}秒");

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

