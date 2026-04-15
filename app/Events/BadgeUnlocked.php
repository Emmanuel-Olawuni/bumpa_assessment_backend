<?php

namespace App\Events;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BadgeUnlocked
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user, public Badge $badge) {}
}
