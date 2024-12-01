<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferFile extends Model
{
    use HasFactory;

    protected $table = 'transfer_files';

    protected $fillable = [
        'transfer_id',
        'file_id',
    ];
}
