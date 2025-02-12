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



    public function index()
    {
        $posts = Post::withCount('likes')
                        ->orderBy('created_at','desc')
                        ->get();
        return response()->json($posts);
    }



    public function show($postId)
    {
        $post = Post::withCount('likes')->find($postId);

        if (!$post) {
            return response()->json(['message' => '投稿が見つかりません'], 404);
        }

        return response()->json($post);
    }



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

