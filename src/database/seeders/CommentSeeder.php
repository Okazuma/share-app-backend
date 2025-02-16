<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comment;
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
        $firebaseUser1 = 'PDQAQbQH7YM1FAwy2DzAis2ryJm2';
        $firebaseUser2 = 'dI9Z7ToyjQUcpxh2v4Dq23Ti9813';

        $post1 = Post::find(1);
        $post2 = Post::find(2);
        $post3 = Post::find(3);

        if ($post1 && $post2){
            Comment::create([
                'user_id' => $firebaseUser1,
                'post_id' => $post1->id,
                'message' => '投稿1に対するテスト用のコメントです!'
            ]);

            Comment::create([
                'user_id' => $firebaseUser2,
                'post_id' => $post1->id,
                'message' => '投稿1に対するテスト用のコメントです!'
            ]);

            Comment::create([
                'user_id' => $firebaseUser1,
                'post_id' => $post2->id,
                'message' => '投稿2に対するテスト用のコメントです!'
            ]);

            Comment::create([
                'user_id' => $firebaseUser2,
                'post_id' => $post2->id,
                'message' => '投稿2に対するテスト用のコメントです!'
            ]);

            Comment::create([
                'user_id' => $firebaseUser1,
                'post_id' => $post3->id,
                'message' => '投稿3に対するテスト用のコメントです!'
            ]);
        }
    }
}
