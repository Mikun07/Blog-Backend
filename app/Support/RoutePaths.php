<?php

namespace App\Support;

final class RoutePaths
{
    public const BLOG = 'blogs/{blog}';

    public const COMMENT = 'comments/{comment}';

    private function __construct()
    {
        throw new \LogicException('RoutePaths is a constants-only utility class.');
    }
}
