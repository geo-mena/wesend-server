<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'message',
        'password',
        'sender_email',
        'recipient_email',
        'download_token',
        'expires_at',
        'single_download'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'single_download' => 'boolean'
    ];

    public function files()
    {
        return $this->belongsToMany(File::class, 'transfer_files');
    }
}
