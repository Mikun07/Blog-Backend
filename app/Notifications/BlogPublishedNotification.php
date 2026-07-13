<?php

namespace App\Notifications;

use App\Models\Blogs;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BlogPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Blogs $blog)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->blog->loadMissing([
            'authorUser:id,name,username',
            'category:id,name,slug',
        ]);

        return [
            'type' => 'blog.published',
            'message' => "New post published: {$this->blog->title}",
            'blog' => [
                'id' => $this->blog->id,
                'title' => $this->blog->title,
                'slug' => $this->blog->slug,
                'excerpt' => $this->blog->excerpt,
                'published_at' => optional($this->blog->published_at)->toISOString(),
            ],
            'author' => $this->blog->authorUser ? [
                'id' => $this->blog->authorUser->id,
                'name' => $this->blog->authorUser->name,
                'username' => $this->blog->authorUser->username,
            ] : [
                'id' => null,
                'name' => $this->blog->author,
                'username' => $this->blog->author,
            ],
            'category' => $this->blog->category ? [
                'id' => $this->blog->category->id,
                'name' => $this->blog->category->name,
                'slug' => $this->blog->category->slug,
            ] : null,
        ];
    }
}
