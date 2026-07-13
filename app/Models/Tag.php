<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blogs::class, 'blog_tag', 'tag_id', 'blog_id')->withTimestamps();
    }
}
