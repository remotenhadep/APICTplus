<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
    ];

    public function roles()
    {
        return $this->belongsToMany('App\Models\Role');
    }
    public function users()
    {
        return $this->hasMany('App\Models\User');
    }
}
