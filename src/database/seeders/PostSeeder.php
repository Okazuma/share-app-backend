<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user1 = User::find(1);
        Post::create([
            'user_id' => $user1->id,
            'content' => 'テスト1テスト1テスト1テスト1テスト1'
        ]);

        $user2 = User::find(2);
        Post::create([
            'user_id' => $user2->id,
            'content' => 'テスト2テスト2テスト2テスト2テスト2'
        ]);
    }
}
