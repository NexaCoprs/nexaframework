<?php

namespace App\Models;

use Nexa\Database\Model;

class Tag extends Model
{
    protected $table = 'tags';
    
    protected $fillable = [
        'name',
        'slug'
    ];
    
    /**
     * Un tag peut être associé à plusieurs posts (relation many-to-many)
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tags');
    }
}