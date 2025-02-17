<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'story_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function contentImages()
    {
        return $this->hasManyThrough(ContentImage::class, Story::class, 'id', 'story_id', 'story_id', 'id');
    }

    // Menambahkan relasi ke Category melalui Story
    public function category()
    {
        return $this->hasOneThrough(Category::class, Story::class, 'id', 'id', 'story_id', 'id');
    }
}
