<?php

namespace App\Services;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\User;

class AchievementService
{
    /**
     * Check and unlock any new achievements for a user after a purchase.
     */
    public function checkAndUnlockAchievements(User $user): void
    {
        $purchaseCount = $user->completedPurchasesCount();
        $unlockedIds = $user->achievements()->pluck('achievements.id')->toArray();

        $newAchievements = Achievement::where('required_purchases', '<=', $purchaseCount)
            ->whereNotIn('id', $unlockedIds)
            ->get();

        foreach ($newAchievements as $achievement) {
            $user->achievements()->attach($achievement->id, [
                'unlocked_at' => now(),
            ]);

            AchievementUnlocked::dispatch($user, $achievement);
        }

        if ($newAchievements->isNotEmpty()) {
            $this->checkAndUnlockBadge($user);
        }
    }

    /**
     * Check if the user qualifies for a new badge.
     */
    public function checkAndUnlockBadge(User $user): void
    {
        $totalUnlocked = $user->achievements()->count();

        $qualifiedBadge = Badge::where('required_achievements', '<=', $totalUnlocked)
            ->orderBy('required_achievements', 'desc')
            ->first();

        if (!$qualifiedBadge) {
            return;
        }

        if ($user->current_badge_id !== $qualifiedBadge->id) {
            $user->update(['current_badge_id' => $qualifiedBadge->id]);

            BadgeUnlocked::dispatch($user, $qualifiedBadge);
        }
    }

    /**
     * Get full achievement progress data for a user.
     */
    public function getUserProgress(User $user): array
    {
        $allAchievements = Achievement::orderBy('required_purchases')->get();
        $unlocked = $user->achievements()->orderBy('required_purchases')->get();
        $unlockedIds = $unlocked->pluck('id')->toArray();

        $nextAchievements = [];
        foreach ($allAchievements as $achievement) {
            if (!in_array($achievement->id, $unlockedIds)) {
                $nextAchievements[] = $achievement->name;
                if (count($nextAchievements) >= 3) break;
            }
        }

        $currentBadge = $user->currentBadge;
        $nextBadge = null;
        $remaining = 0;

        if ($currentBadge) {
            $nextBadge = Badge::where('required_achievements', '>', $currentBadge->required_achievements)
                ->orderBy('required_achievements')
                ->first();
        } else {
            $nextBadge = Badge::orderBy('required_achievements')->first();
        }

        if ($nextBadge) {
            $remaining = $nextBadge->required_achievements - count($unlockedIds);
        }

        return [
            'unlocked_achievements' => $unlocked->pluck('name')->toArray(),
            'next_available_achievements' => $nextAchievements,
            'current_badge' => $currentBadge ? $currentBadge->name : 'Beginner',
            'next_badge' => $nextBadge ? $nextBadge->name : 'Max Level Reached',
            'remaining_to_unlock_next_badge' => max(0, $remaining),

            'unlocked_achievements_details' => $unlocked->map(function ($a) {
                return [
                    'name' => $a->name,
                    'description' => $a->description,
                    'icon' => $a->icon,
                    'unlocked_at' => $a->pivot->unlocked_at,
                ];
            })->toArray(),

            'current_badge_details' => $currentBadge ? [
                'name' => $currentBadge->name,
                'icon' => $currentBadge->icon,
                'color' => $currentBadge->color,
            ] : null,

            'next_badge_details' => $nextBadge ? [
                'name' => $nextBadge->name,
                'icon' => $nextBadge->icon,
                'color' => $nextBadge->color,
                'required_achievements' => $nextBadge->required_achievements,
            ] : null,

            'total_achievements' => $allAchievements->count(),
            'total_unlocked' => count($unlockedIds),
        ];
    }
}
