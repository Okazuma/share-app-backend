<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;

class PostSeeder extends Seeder
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

        Post::create([
            'user_id' => $firebaseUser1,
            'content' => '投稿テスト用データ１です'
        ]);

        Post::create([
            'user_id' => $firebaseUser2,
            'content' => '投稿テスト用データ２です'
        ]);

        Post::create([
            'user_id' => $firebaseUser1,
            'content' => '投稿テスト用データ3です'
        ]);
    }
}
