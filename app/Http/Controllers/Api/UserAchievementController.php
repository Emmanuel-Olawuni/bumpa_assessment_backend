<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;

class UserAchievementController extends Controller
{
    public function __construct(private AchievementService $achievementService) {}

    public function index(User $user): JsonResponse
    {
        $progress = $this->achievementService->getUserProgress($user);

        return response()->json([
            'data' => $progress,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
