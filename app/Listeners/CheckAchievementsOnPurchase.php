<?php

namespace App\Listeners;

use App\Events\PurchaseCompleted;
use App\Services\AchievementService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckAchievementsOnPurchase implements ShouldQueue
{
    public function __construct(private AchievementService $achievementService) {}

    public function handle(PurchaseCompleted $event): void
    {
        $this->achievementService->checkAndUnlockAchievements($event->purchase->user);
    }
}
