<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase; // テスト後にデータベースをリセット

    public function test_create_post()
    {
        $response = $this->postJson('/api/posts', [
            'content' => '新しい投稿です！',
        ], [
            'Authorization' => 'Bearer VALID_TOKEN', // Firebaseのトークン
        ]);

        $response->assertStatus(201) // ステータスコード201（作成成功）
                ->assertJson([
                     'content' => '新しい投稿です！', // 作成した投稿の内容
                ]);
    }

    public function test_get_all_posts()
    {
        Post::factory()->create(['content' => 'テスト投稿', 'user_id' => 'VALID_USER_ID']);

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    '*' => ['id', 'content', 'user_id', 'created_at', 'updated_at'],
                ]);
    }

    public function test_update_post()
    {
        $post = Post::factory()->create(['user_id' => 'VALID_USER_ID']);

        $response = $this->putJson("/api/posts/{$post->id}", [
            'content' => '更新した投稿です！',
        ], [
            'Authorization' => 'Bearer VALID_TOKEN',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'content' => '更新した投稿です！',
                ]);
    }

    public function test_delete_post()
    {
        $post = Post::factory()->create(['user_id' => 'VALID_USER_ID']);

        $response = $this->deleteJson("/api/posts/{$post->id}", [], [
            'Authorization' => 'Bearer VALID_TOKEN',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Post deleted',
                ]);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
