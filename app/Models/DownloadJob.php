<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadJob extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'status', 'file_path'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
