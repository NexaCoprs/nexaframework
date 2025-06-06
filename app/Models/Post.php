<?php

namespace App\Models;

use Nexa\Database\Model;

class Post extends Model
{
    protected $table = 'posts';
    
    protected $fillable = [
        'title',
        'content',
        'user_id'
    ];
    
    /**
     * Un post appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Un post a plusieurs commentaires
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    
    /**
     * Un post peut avoir plusieurs tags (relation many-to-many)
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }
}