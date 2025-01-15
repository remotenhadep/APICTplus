<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;
    protected $fillable = [
    	'playlistid',
    	'title',
        'category_id',
        'publishedat',
        'thumbnail',
        'nextpagetoken',
    ];

    public function category(){
    	return $this->belongsTo('App\Models\Category');
    }
}
