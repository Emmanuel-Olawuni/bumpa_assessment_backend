<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Purchase;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Database\Seeder;

class LoyaltyProgramSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            ['name' => 'First Purchase',       'description' => 'Made your very first purchase',           'required_purchases' => 1,  'icon' => '🛒'],
            ['name' => 'Getting Started',      'description' => 'Completed 3 purchases',                  'required_purchases' => 3,  'icon' => '🚀'],
            ['name' => 'Regular Customer',     'description' => 'Completed 5 purchases',                  'required_purchases' => 5,  'icon' => '⭐'],
            ['name' => 'Loyal Shopper',        'description' => 'Completed 10 purchases',                 'required_purchases' => 10, 'icon' => '💎'],
            ['name' => 'Dedicated Buyer',      'description' => 'Completed 15 purchases',                 'required_purchases' => 15, 'icon' => '🔥'],
            ['name' => 'Shopping Enthusiast',   'description' => 'Completed 20 purchases',                'required_purchases' => 20, 'icon' => '🎯'],
            ['name' => 'Power Shopper',        'description' => 'Completed 30 purchases',                 'required_purchases' => 30, 'icon' => '⚡'],
            ['name' => 'Elite Customer',       'description' => 'Completed 40 purchases',                 'required_purchases' => 40, 'icon' => '👑'],
            ['name' => 'Shopping Legend',       'description' => 'Completed 50 purchases',                 'required_purchases' => 50, 'icon' => '🏆'],
        ];

        foreach ($achievements as $data) {
            Achievement::create($data);
        }

        $badges = [
            ['name' => 'Bronze',    'required_achievements' => 2, 'icon' => '🥉', 'color' => '#CD7F32'],
            ['name' => 'Silver',    'required_achievements' => 4, 'icon' => '🥈', 'color' => '#C0C0C0'],
            ['name' => 'Gold',      'required_achievements' => 6, 'icon' => '🥇', 'color' => '#FFD700'],
            ['name' => 'Platinum',  'required_achievements' => 8, 'icon' => '💠', 'color' => '#E5E4E2'],
        ];

        foreach ($badges as $data) {
            Badge::create($data);
        }

        $user = User::factory()->create([
            'name' => 'Emmanuel Olawuni',
            'email' => 'emmanuelOlawuni@example.com',
        ]);

        $products = [
            'Ankara Dress',
            'Leather Bag',
            'Skincare Set',
            'Headwrap',
            'Beaded Necklace',
            'Shea Butter Lotion',
            'Palm Slippers',
            'Dashiki Shirt',
            'Waist Beads',
            'Essential Oil Set',
            'Adire Fabric',
            'Clay Earrings',
        ];

        foreach ($products as $i => $product) {
            Purchase::withoutEvents(function () use ($user, $product, $i, $products) {
                Purchase::create([
                    'user_id' => $user->id,
                    'amount' => rand(1500, 25000),
                    'product_name' => $product,
                    'status' => 'completed',
                    'created_at' => now()->subDays(count($products) - $i),
                ]);
            });
        }

        $service = app(AchievementService::class);
        $service->checkAndUnlockAchievements($user);

        $user2 = User::factory()->create([
            'name' => 'Chidi Eze',
            'email' => 'chidi@example.com',
        ]);

        Purchase::withoutEvents(function () use ($user2) {
            Purchase::create([
                'user_id' => $user2->id,
                'amount' => 5000,
                'product_name' => 'Bumpa Starter Kit',
                'status' => 'completed',
            ]);
        });

        $service->checkAndUnlockAchievements($user2);
    }
}
