<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'thread_id',
        'parent_id',
        'name',
        'title',
        'message',
        'image_path',
        'password_hash',
        'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public $timestamps = false;
}
