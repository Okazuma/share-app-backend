<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Like;
use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Laravel\Firebase\Facades\Firebase;

class LikeController extends Controller
{
    // Likeを追加
    public function store(Request $request)
    {
        // トークンを取得
        $token = $request->bearerToken();

        // トークンの検証
        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');  // Firebase UID
        } catch (\Kreait\Firebase\Exception\Auth\InvalidIdToken $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $like = Like::create([
            'user_id' => $uid,  // Firebase UIDを保存
            'post_id' => $request->input('post_id'),
        ]);
        return response()->json($like, 201);
    }

    // Likeを削除
    public function destroy($id)
    {
        $like = Like::findOrFail($id);
        // 自分のLikeのみ削除できるようにする場合
        if ($like->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $like->delete();

        return response()->json(['message' => 'Like removed'], 200);
    }
}
