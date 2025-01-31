<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Like;
use App\Models\Post;
use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Factory;

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
        $userId = null;

        if ($request->bearerToken()) {
            try {
                $token = $request->bearerToken();
                $verifiedIdToken = $this->auth->verifyIdToken($token);
                $userId = $verifiedIdToken->claims()->get('sub');
            } catch (\Kreait\Firebase\Exception\Auth\InvalidIdToken $e) {
                $userId = null;
            }
        }

        // ユーザーがログインしている場合、そのユーザーのいいね情報を取得
    if ($userId) {
        $likes = Like::where('user_id', $userId)->with('post')->get();

        return response()->json($likes);
    }

    return response()->json([]);
    }




    // Likeを追加
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

        // 同じユーザーがすでにこの投稿にいいねしていないか確認
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



    // Likeを削除
    public function destroy(Request $request)
    {
        $token = $request->bearerToken();

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');
        } catch (\Kreait\Firebase\Exception\Auth\InvalidIdToken $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // いいねの存在を確認（削除対象の「いいね」を取得）
        $like = Like::where('user_id', $uid)
                    ->where('post_id', $request->input('post_id'))
                    ->first();

        if (!$like) {
            return response()->json(['error' => 'Like not found'], 404);
        }

        // 「いいね」を削除
        $like->delete();

        return response()->json(['message' => 'Like removed'], 200);
    }
}
