<?php

namespace App\Providers;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Events\PurchaseCompleted;
use App\Listeners\CheckAchievementsOnPurchase;
use App\Listeners\LogAchievementUnlocked;
use App\Listeners\ProcessBadgeCashback;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PurchaseCompleted::class => [
            CheckAchievementsOnPurchase::class,
        ],
        AchievementUnlocked::class => [
            LogAchievementUnlocked::class,
        ],
        BadgeUnlocked::class => [
            ProcessBadgeCashback::class,
        ],
    ];
}
