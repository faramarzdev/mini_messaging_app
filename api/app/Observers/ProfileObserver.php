<?php

namespace App\Observers;

use App\Models\Profile;
use Illuminate\Support\Str;

class ProfileObserver
{
    public function creating(Profile $profile): void
    {
        if (blank($profile->handle)) {
            $handle = $profile->profileable_type.'_'.microtime().rand(1000, 9999);
            $handle = Str::lower(Str::replaceMatches('/[^A-Za-z0-9_]++/', '', $handle));

            $profile->handle = Str::limit($handle, 60, '');
        }
    }
}
