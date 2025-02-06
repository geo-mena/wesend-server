<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryDatabase extends Model
{
    use HasFactory;

    protected $table = 'temporary_databases';

    protected $fillable = [
        'connection_url',
        'expires_at',
        'branch_id'
    ];
}
