<?php

namespace Tests\Feature;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Events\PurchaseCompleted;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Purchase;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BumpaLoyaltyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\LoyaltyProgramSeeder::class);
    }


    public function test_achievements_endpoint_returns_correct_structure(): void
    {
        $user = User::first();

        $response = $this->getJson("/api/users/{$user->id}/achievements");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'unlocked_achievements',
                    'next_available_achievements',
                    'current_badge',
                    'next_badge',
                    'remaining_to_unlock_next_badge',
                ],
                'user' => ['id', 'name', 'email'],
            ]);
    }

    public function test_achievements_endpoint_returns_correct_data_for_user_with_progress(): void
    {
        $user = User::where('email', 'emmanuelOlawuni@example.com')->first();

        $response = $this->getJson("/api/users/{$user->id}/achievements");

        $response->assertOk();

        $data = $response->json('data');

        // Adaeze has 12 purchases = 4 achievements unlocked
        $this->assertCount(4, $data['unlocked_achievements']);
        $this->assertContains('First Purchase', $data['unlocked_achievements']);
        $this->assertContains('Loyal Shopper', $data['unlocked_achievements']);

        $this->assertEquals('Silver', $data['current_badge']);

        $this->assertEquals('Gold', $data['next_badge']);

        $this->assertEquals(2, $data['remaining_to_unlock_next_badge']);
    }

    public function test_achievements_endpoint_for_new_user(): void
    {
        $user = User::where('email', 'chidi@example.com')->first();

        $response = $this->getJson("/api/users/{$user->id}/achievements");

        $response->assertOk();

        $data = $response->json('data');

        $this->assertCount(1, $data['unlocked_achievements']);
        $this->assertContains('First Purchase', $data['unlocked_achievements']);
        $this->assertEquals('Beginner', $data['current_badge']);
    }

    public function test_achievements_endpoint_returns_404_for_nonexistent_user(): void
    {
        $response = $this->getJson('/api/users/99999/achievements');
        $response->assertNotFound();
    }

    // ── Event System Tests ─────────────────────────────────────

    public function test_purchase_completed_event_fires_on_purchase_creation(): void
    {
        Event::fake([PurchaseCompleted::class]);

        $user = User::first();

        Purchase::create([
            'user_id' => $user->id,
            'amount' => 5000,
            'product_name' => 'Test Product',
            'status' => 'completed',
        ]);

        Event::assertDispatched(PurchaseCompleted::class);
    }

    public function test_purchase_completed_event_does_not_fire_for_pending_purchases(): void
    {
        Event::fake([PurchaseCompleted::class]);

        $user = User::first();

        Purchase::create([
            'user_id' => $user->id,
            'amount' => 5000,
            'product_name' => 'Test Product',
            'status' => 'pending',
        ]);

        Event::assertNotDispatched(PurchaseCompleted::class);
    }

    public function test_achievement_unlocked_event_fires_when_threshold_reached(): void
    {
        Event::fake([AchievementUnlocked::class, BadgeUnlocked::class]);

        // Create a fresh user with no achievements
        $user = User::factory()->create();

        // Create enough purchases to unlock "First Purchase" achievement
        Purchase::create([
            'user_id' => $user->id,
            'amount' => 3000,
            'product_name' => 'Test Item',
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $service = app(AchievementService::class);
        $service->checkAndUnlockAchievements($user);

        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->achievement->name === 'First Purchase';
        });
    }

    public function test_badge_unlocked_event_fires_when_enough_achievements(): void
    {
        Event::fake([AchievementUnlocked::class, BadgeUnlocked::class]);

        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            Purchase::create([
                'user_id' => $user->id,
                'amount' => 3000,
                'product_name' => "Product {$i}",
                'status' => 'completed',
            ]);
        }

        $service = app(AchievementService::class);
        $service->checkAndUnlockAchievements($user);

        Event::assertDispatched(BadgeUnlocked::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->badge->name === 'Bronze';
        });
    }

    // ── Service Logic Tests ────────────────────────────────────

    public function test_achievement_service_does_not_duplicate_achievements(): void
    {
        Event::fake();

        $user = User::where('email', 'emmanuelOlawuni@example.com')->first();
        $initialCount = $user->achievements()->count();

        $service = app(AchievementService::class);
        $service->checkAndUnlockAchievements($user);

        // Should not add duplicate achievements
        $this->assertEquals($initialCount, $user->fresh()->achievements()->count());
    }

    public function test_get_user_progress_returns_complete_data(): void
    {
        $user = User::where('email', 'emmanuelOlawuni@example.com')->first();

        $service = app(AchievementService::class);
        $progress = $service->getUserProgress($user);

        $this->assertArrayHasKey('unlocked_achievements', $progress);
        $this->assertArrayHasKey('next_available_achievements', $progress);
        $this->assertArrayHasKey('current_badge', $progress);
        $this->assertArrayHasKey('next_badge', $progress);
        $this->assertArrayHasKey('remaining_to_unlock_next_badge', $progress);
        $this->assertArrayHasKey('total_achievements', $progress);
        $this->assertArrayHasKey('total_unlocked', $progress);

        $this->assertIsArray($progress['unlocked_achievements']);
        $this->assertIsArray($progress['next_available_achievements']);
        $this->assertIsString($progress['current_badge']);
        $this->assertIsString($progress['next_badge']);
        $this->assertIsInt($progress['remaining_to_unlock_next_badge']);
    }
}
