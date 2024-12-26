<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_comment()
    {
        $post = Post::factory()->create();

        $response = $this->postJson('/api/comments', [
            'message' => 'This is a test comment',
            'post_id' => $post->id,
        ],[
        'Authorization' => 'Bearer VALID_TOKEN', // Firebaseのトークン
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'This is a test comment',
                    'post_id' => $post->id,
                ]);
    }


    public function test_get_comments_by_post()
    {
        $post = Post::factory()->create();
        Comment::factory()->create(['post_id' => $post->id, 'message' => 'テストコメント', 'user_id' => 'VALID_USER_ID']);

        $response = $this->getJson("/api/comments/post/{$post->id}");

        $response->assertStatus(200)
                ->assertJsonFragment(['message' => 'テストコメント']);
    }
}
