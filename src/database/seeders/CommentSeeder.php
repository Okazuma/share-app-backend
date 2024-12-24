<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comment;
use App\Models\User;
use App\Models\Post;


class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user1 = User::find(1);
        $post1 = Post::find(1);
        Comment::create([
            'user_id' => $user1->id,
            'post_id' => $post1->id,
            'message' => 'テストメッセージ1です!テストメッセージ1です!'
        ]);

        $user2 = User::find(2);
        $post2 = Post::find(2);
        Comment::create([
            'user_id' => $user2->id,
            'post_id' => $post2->id,
            'message' => 'テストメッセージ2です!テストメッセージ2です!'
        ]);
    }
}
