<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use Illuminate\Support\Facades\Log;

class LogAchievementUnlocked
{
    public function handle(AchievementUnlocked $event): void
    {
        Log::info('Achievement Unlocked', [
            'user_id' => $event->user->id,
            'achievement' => $event->achievement->name,
        ]);
    }
}
