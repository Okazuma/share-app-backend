<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 'user_id' => ['required', 'string', new FirebaseUserExists($this->auth)],  // Firebaseユーザーの存在確認
            'post_id' => 'required|integer|exists:posts,id', // 関連する投稿が存在するか
            'message' => 'required|string|max:255', // コメントの内容
        ];
    }
}
