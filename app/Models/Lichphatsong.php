<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class Lichphatsong extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'time',
        'ngay',
        'youtubeid',
        'url_mp4',
        'type',
        'timecode',
        'duration',
    ];

}
