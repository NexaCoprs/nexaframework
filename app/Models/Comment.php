<?php

namespace App\Models;

use Nexa\Database\Model;

class Comment extends Model
{
    protected $table = 'comments';
    
    protected $fillable = [
        'content',
        'user_id',
        'post_id'
    ];
    
    /**
     * Un commentaire appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Un commentaire appartient à un post
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}