<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'blog_id',
        'user_id',
        'author_name',
        'author_email',
        'content',
        'status',
    ];

    protected $hidden = [
        'author_email',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blogs::class, 'blog_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
