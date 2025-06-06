<?php

namespace App\Models;

use Nexa\Database\Model;

class Profile extends Model
{
    protected $table = 'profiles';
    
    protected $fillable = [
        'bio',
        'avatar',
        'website',
        'user_id'
    ];
    
    /**
     * Un profil appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}