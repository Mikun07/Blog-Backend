<?php

namespace App\Services;

use App\Models\Blogs;
use App\Models\User;
use App\Notifications\BlogPublishedNotification;
use Illuminate\Support\Facades\Notification;

class BlogNotificationService
{
    public function notifyPublished(Blogs $blog): void
    {
        if ($blog->status !== Blogs::STATUS_PUBLISHED) {
            return;
        }

        $recipients = User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new BlogPublishedNotification($blog));
    }
}
