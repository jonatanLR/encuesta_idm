<?php

namespace App\Support;

use Illuminate\Support\Str;

class CommunitySearch
{
    public static function normalize(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9\s]/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }
}