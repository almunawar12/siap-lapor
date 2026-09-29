<?php

namespace App\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $sha256
 * @property int $uploaded_by
 */
class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory;

    protected $fillable = [
        'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'sha256', 'uploaded_by',
    ];
}
