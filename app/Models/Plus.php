<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plus extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'aprove_userid',
        'category_id',
        'playlist_id',
        'title',
        'description',
        'shortdescription',
        'thumbnails',
        'youtubeid',
        'mp4_link',
        'viewcount',
        'likeCount',
        'shareCount',
        'commentCount',
        'publishedAt',
        'order',
        'state',
        'status',
    ];
    
    public function tags(){
        return $this->belongsToMany(Tag::class);
    }

    function category(){
        return $this->belongsTo(Category::class);
    }
}
