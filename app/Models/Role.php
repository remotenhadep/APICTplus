<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','title',
    ];

    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Models\Group');
    }
}
