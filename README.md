# アプリケーション名
    share-app API  (Twitter風投稿アプリのバックエンド)
<img width="650" src="https://github.com/user-attachments/assets/4e23b45c-8a55-48f2-9642-268ccd3f6040">




## 概要説明
- テキストベースで情報共有することができる

- コメントやいいね機能で投稿に対してのアクションができる




## 作成目的
- share-app(フロントエンド)と連携し、投稿・コメント・リアクションデータを管理するため.

- フロントエンドからのリクエストを受け取り、データベース（MySQL）とのやり取りを行う。




## バックエンドAPIの役割
- share-app のバックエンド API として、データ管理を担う
- ユーザー認証（Firebase Authentication を使用）
- 投稿データの管理（作成、取得、削除）
- コメント機能（作成・取得・削除）
- いいね機能（追加、取得、削除）
- Firebase Authentication のトークン検証（認証済みユーザーのみ操作を許可）
- データベース（MySQL）との連携（Eloquent ORM を利用）




## アプリケーションURL

### ローカル環境
`http://localhost/`




## 機能一覧
#### ユーザー管理 (firebase Authentication)
- ユーザーの登録・認証
- ユーザー情報の取得

#### 投稿管理
- 投稿の全体取得（`GET /api/posts`）
- 投稿を個別に取得（`GET /api/posts/{postId}`）
- 投稿の作成（`POST /api/posts`）
- 投稿の編集（`PUT /api/posts/{postId}`）
- 投稿の削除（`DELETE /api/posts/{postId}`）

#### コメント管理
- コメントの作成（`POST /api/comments`）
- コメントの取得（`GET /api/comments/{postId}`）
- コメントの削除（`DELETE /api/comments/{commentId}`）

#### リアクション（いいねなど）
- いいねの追加（`POST /api/likes`）
- いいねの取得（`GET /api/likes`）
- いいねの削除（`DELETE /api/likes/{postId}`）

#### その他
- ユーザー別の各データ取得
- フォームリクエストによるバリデーション




## 詳細内容
#### テスト用ユーザーはfirebase Authenticationで登録済み。
- ユーザー名:test1  email: test1@example.com    password: 11111111
- ユーザー名:test2  email: test2@example.com    password: 22222222

- 登録済みユーザーに対してシーディングでテスト用の投稿とコメントが作成される。

- 投稿の取得・追加・削除
    投稿の閲覧は誰でも可能
    投稿の追加、編集、削除にはユーザー認証が必要

- コメント機能(追加と削除)
    それぞれの投稿に対するコメントは誰でも閲覧可能
    コメントの追加と削除はユーザー認証が必要

- いいね機能(適用と取り消し)
    それぞれの投稿に対するいいねの数は誰でも閲覧可能
    いいねの追加と削除はユーザー認証が必要




## 使用技術
- Docker 27.3.1
- php 8.3.13
- Laravel 8.83.29
- Composer 2.8.4
- nginx 1.21.1
- Mysql 8.0.37
- phpMyAdmin 5.2.1




## テーブル設計
<img width="650" src="https://github.com/user-attachments/assets/f017f6b7-3575-4df0-86e6-51dfe383c4b9">




## ER図
<img width="650" src="https://github.com/user-attachments/assets/e313a860-bb03-42e9-81db-e0024f5cd081">




## dockerビルド
    1 git clone リンク  https://github.com/Okazuma/share-app-backend.git

    2 docker compose up -d --build

    ※ MysqlはOSによって起動しない場合があるので、それぞれのPCに合わせてdocker-compose.ymlを編集してください。




## Laravelの環境構築
- phpコンテナにログイン        $docker compose exec php bash

- パッケージのインストール      $composer-install

- .envファイルの作成          cp .env.example .env

- アプリケーションキーの生成    $php artisan key:generate

- マイグレーション            $php artisan migrate

- シーディング               $php artisan db:seed




## CORS 設定について
- フロントエンド（例: `http://localhost:3000`）から API にアクセスできるようにするため、'config/cors.php'で以下の設定を追加しています。
'allowed_origins' => ['http://localhost:3000']