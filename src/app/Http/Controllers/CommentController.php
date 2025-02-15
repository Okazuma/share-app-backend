<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Post;
use App\Http\Requests\CommentRequest;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth\Uid;
// use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cache;
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



    public function index()
    {
        $comments = Comment::all();
        return response()->json($comments);
    }




    public function show($postId)
    {
        $start = microtime(true);
        $comments = Comment::where('post_id', $postId)->get();
        $userIds = $comments->pluck('user_id')->unique()->toArray();

        $cachedUserNames = Cache::get('user_names', []);
        Log::info(' 🔥 コメントユーザーキャッシュデータ: ' . json_encode($cachedUserNames));

        $missingUserIds = array_diff($userIds, array_keys($cachedUserNames));
        \Log::info(" 🔥 コメントFirebase 取得前: " . (microtime(true) - $start) . "秒");

        if (!empty($missingUserIds)) {
            try {
                $userRecords = $this->auth->getUsers($missingUserIds);
                \Log::info(" 🔥 コメントFirebase 取得後: " . (microtime(true) - $start) . "秒");

                $newUserNames = [];
                foreach ($userRecords as $userRecord) {
                    $newUserNames[$userRecord->uid] = $userRecord->displayName ?? "Unknown";
                }

                $cachedUserNames = array_merge($cachedUserNames, $newUserNames);
                Cache::put('user_names', $cachedUserNames, now()->addMinutes(10));
            } catch (\Exception $e) {
                return response()->json(['error' => 'ユーザー情報の取得に失敗しました: ' . $e->getMessage()], 500);
            }
        }

        $commentsWithUserNames = $comments->map(function ($comment) use ($cachedUserNames) {
            $comment->user_name = $cachedUserNames[$comment->user_id] ?? "Unknown";
            return $comment;
        });
        \Log::info(" 🔥 コメント全体の処理時間: " . (microtime(true) - $start) . "秒");

        return response()->json($commentsWithUserNames);
    }




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

            $userName = trim($request->input('user_name', 'Unknown'));
            if ($userName === '') {
                $userName = 'Unknown';
            }

            \Log::info('コメント情報:', [
                'user_id' => $firebaseUid,
                'user_name' => $userName,
                'post_id' => $postId,
                'message' => $validated['message'],
            ]);

            $comment = Comment::create([
                'user_id' => $firebaseUid,
                'post_id' => $postId,
                'message' => $validated['message'],
            ]);

            return response()->json([
                'id' => $comment->id,
                'user_id' => $comment->user_id,
                'user_name' => $userName,
                'post_id' => $comment->post_id,
                'message' => $comment->message,
                'created_at' => $comment->created_at,
            ], 201);

        } catch (\Kreait\Firebase\Exception\Auth\InvalidIdToken $e) {
            \Log::error('Firebase認証エラー: ' . $e->getMessage());
            return response()->json(['error' => '無効なトークンです'], 401);
        }catch(\Exception $e) {
            \Log::error('コメント保存エラー: ' . $e->getMessage());
            return response()->json(['error' => 'コメントの保存に失敗しました'], 500);
        }
    }



    public function destroy(Request $request,$postId)
    {
        Log::info('削除リクエスト受信:', ['postId' => $postId]);
        try {
            $token = $request->bearerToken();
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            $comment = Comment::findOrFail($postId);
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
