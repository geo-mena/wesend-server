<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_name',
        'storage_path',
        'size',
        'mime_type',
        'encryption_key',
        'expires_at',
    ];

    public function transfers()
    {
        return $this->belongsToMany(Transfer::class, 'transfer_files');
    }
}
