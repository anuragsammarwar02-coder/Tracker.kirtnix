<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'permission_key',
        'is_granted',
    ];

    protected $casts = [
        'is_granted' => 'boolean',
    ];
}
