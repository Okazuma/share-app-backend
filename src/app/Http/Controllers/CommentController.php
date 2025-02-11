<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Post;
use App\Http\Requests\CommentRequest;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{

    private $auth;

    public function __construct()
    {
        $factory = (new Factory())
            ->withServiceAccount(env('FIREBASE_CREDENTIALS'))
            ->withProjectId(env('FIREBASE_PROJECT_ID'));

        $this->auth = $factory->createAuth();
    }


    // 特定の投稿に関連するコメントを取得
    public function index()
    {
        $comments = Comment::all();
        return response()->json($comments);
    }


    public function show($postId)
    {
        $comments = Comment::where('post_id', $postId)->get();
        return response()->json($comments);
    }




    // コメントを作成
    public function store(CommentRequest $request)
    {
        $validated = $request->validated();

        $postId = (int)$validated['post_id'];

        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['error' => '指定された投稿が存在しません'], 404);
        }

        try{
            $token = $request->bearerToken();
            if (!$token) {
                return response()->json(['error' => 'トークンが提供されていません'], 401);
            }

            $verifiedIdToken = $this->auth->verifyIdToken($token);

            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            // user_id, post_id, message をログに出力
            \Log::info('コメント情報:', [
                'user_id' => $firebaseUid,
                'post_id' => $postId,
                'message' => $validated['message'],
            ]);

            $comment = Comment::create([
                'user_id' => $firebaseUid,
                'post_id' => $postId,
                'message' => $validated['message'],
            ]);

            return response()->json($comment, 201);
        } catch (\Kreait\Firebase\Exception\Auth\InvalidIdToken $e) {
            \Log::error('Firebase認証エラー: ' . $e->getMessage());
            return response()->json(['error' => '無効なトークンです'], 401);
        }catch(\Exception $e) {
            \Log::error('コメント保存エラー: ' . $e->getMessage());
            return response()->json(['error' => 'コメントの保存に失敗しました'], 500);
        }
    }



    public function destroy(Request $request,$id)
    {
        Log::info('削除リクエスト受信:', ['id' => $id]);
    // 削除処理
        try {
            $token = $request->bearerToken();
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            $comment = Comment::findOrFail($id);
            Log::info('取得したコメント:', ['comment' => $comment]);

            if ($comment->user_id !== $firebaseUid) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $comment->delete();

            return response()->json(['message' => 'コメントを削除しました'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'コメント削除に失敗しました', 'details' => $e->getMessage()], 500);
        }
    }
}
