<?php

namespace App\Filament\Support;

use App\Models\User;

final class AdminActor
{
    public static function current(): User
    {
        $actor = auth('admin')->user();
        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
