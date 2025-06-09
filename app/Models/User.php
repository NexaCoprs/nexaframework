<?php

namespace App\Models;

use Nexa\Database\Model;

class User extends Model
{
    protected $table = 'users';
    
    protected $fillable = [
        'name',
        'email',
        'password'
    ];
    
    /**
     * Un utilisateur a plusieurs posts
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    /**
     * Un utilisateur a plusieurs commentaires
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    
    /**
     * Un utilisateur a un profil (relation one-to-one)
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
}