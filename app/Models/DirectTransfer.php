<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'pin',
        'expires_at',
        'used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];

    public function files()
    {
        return $this->belongsToMany(File::class, 'direct_transfer_files');
    }
}
