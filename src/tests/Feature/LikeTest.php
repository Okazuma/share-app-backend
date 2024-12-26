<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_post()
    {
        $post = Post::factory()->create();

        $response = $this->postJson('/api/likes', [
            'post_id' => $post->id,
        ], [
            'Authorization' => 'Bearer VALID_TOKEN',
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'post_id' => $post->id,
                ]);
    }

    public function test_unlike_post()
    {
        // LikeはFirebase UIDをuser_idとして設定するので、まずはPostを作成
        $post = Post::factory()->create();

        // Likeをユーザーがつける（Firebaseトークンで認証）
        $like = Like::create([
            'user_id' => auth()->id(),  // Firebase UID（認証されたユーザーのID）
            'post_id' => $post->id,
        ]);

        $response = $this->deleteJson("/api/likes/{$like->id}", [], [
            'Authorization' => 'Bearer VALID_TOKEN', // Firebaseのトークン
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Like removed',
                ]);

        $this->assertDatabaseMissing('likes', ['id' => $like->id]);
    }
}
