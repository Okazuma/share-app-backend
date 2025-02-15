<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Like;
use App\Models\Post;
use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Cache;

class LikeController extends Controller
{
    private $auth;

    public function __construct()
    {
        $factory = (new Factory())
            ->withServiceAccount(env('FIREBASE_CREDENTIALS'))
            ->withProjectId(env('FIREBASE_PROJECT_ID'));

        $this->auth = $factory->createAuth();
    }



    public function index(Request $request)
    {
        $startTime = microtime(true);

        if (!$request->bearerToken()) {
            return response()->json([]);
        }

        try {
            $token = $request->bearerToken();
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $userId = $verifiedIdToken->claims()->get('sub');
        } catch (\Kreait\Firebase\Exception\Auth\InvalidIdToken $e) {
            return response()->json([]);
        }

        $likes = Like::where('user_id', $userId)
            ->with(['post:id,user_id'])
            ->get(['id', 'post_id', 'user_id']);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        \Log::info("🔥 投稿一覧いいねデータ取得時間: " . $executionTime . "秒");

        return response()->json($likes);
    }






    public function store(Request $request)
    {
        $userId = $request->input('user_id');
        $postId = $request->input('post_id');

        if (!$userId || !$postId) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        $token = $request->bearerToken();

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');
        } catch (\Kreait\Firebase\Exception\Auth\InvalidIdToken $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $existingLike = Like::where('user_id', $uid)
                            ->where('post_id', $request->input('post_id'))
                            ->first();

        if ($existingLike) {
            return response()->json(['error' => 'Already liked'], 400);
        }

        $like = Like::create([
            'user_id' => $uid,
            'post_id' => $request->input('post_id'),
        ]);
        return response()->json($like, 201);
    }



    public function destroy(Request $request)
    {
        $token = $request->bearerToken();

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');
        } catch (\Kreait\Firebase\Exception\Auth\InvalidIdToken $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $like = Like::where('user_id', $uid)
                    ->where('post_id', $request->input('post_id'))
                    ->first();

        if (!$like) {
            return response()->json(['error' => 'Like not found'], 404);
        }

        $like->delete();

        return response()->json(['message' => 'Like removed'], 200);
    }
}
