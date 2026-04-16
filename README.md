# Bumpa Loyalty Program

A full-stack e-commerce loyalty system where customers unlock achievements through purchases and earn badge tiers with ₦300 cashback rewards. Built with **Laravel 11** (API) and **Next.js + React** (Frontend).

---

## Architecture

The system uses an event-driven architecture. When a customer completes a purchase, a chain of events automatically handles achievement unlocking, badge upgrades, and cashback payments — all decoupled from the main request.

```
Purchase Created (status: completed)
  └─▸ PurchaseCompleted Event
        └─▸ CheckAchievementsOnPurchase Listener
              ├─▸ Unlocks qualifying achievements
              │     └─▸ AchievementUnlocked Event
              │           └─▸ LogAchievementUnlocked Listener
              └─▸ Checks for badge upgrade
                    └─▸ BadgeUnlocked Event
                          └─▸ ProcessBadgeCashback Listener
                                └─▸ MockPaymentService (₦300 cashback)
```

### Why Events?

Each component has a single responsibility. The `Purchase` model doesn't know about achievements. The achievement system doesn't know about payments. Adding new behavior (e.g., sending an email on badge unlock) means adding a new listener. there is no existing code changes.

### Key Design Decisions

- **Service Layer** — `AchievementService` centralizes all business logic. Controllers stay thin, and the same logic is reusable from seeders, commands, or other contexts.
- **Queued Listeners** — `CheckAchievementsOnPurchase` and `ProcessBadgeCashback` implement `ShouldQueue` for async processing so the purchase response isn't blocked.
- **Mock Payment Provider** — `MockPaymentService` simulates Paystack/Flutterwave with transaction references and logging. Easy to swap for a real provider.
- **Route Model Binding** — The API endpoint uses Laravel's implicit binding (`User $user`), which auto-resolves the user ID and returns 404 if not found.

---

## Prerequisites

- PHP 8.2+
- Composer
- MySQL
- Node.js 18+ (for the frontend)

---

## Backend Setup

```bash
# Clone the repository
git clone https://github.com/Emmanuel-Olawuni/bumpa_assessment_backend.git
cd bumpa_assessment_backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env
php artisan key:generate

# Database setup — configure your .env with MySQL credentials:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=bumpa_loyalty
# DB_USERNAME=root
# DB_PASSWORD=

# Create the database, then run migrations
php artisan migrate

# Seed demo data
php artisan db:seed --class=Database\\Seeders\\LoyaltyProgramSeeder

# Start the server
php artisan serve
```

The API will be available at `http://localhost:8000`.

---

## API Endpoint

### GET `/api/users/{user}/achievements`

Returns the full achievement progress for a given user.

**Example:** `GET /api/users/1/achievements`

**Response:**

```json
{
    "data": {
        "unlocked_achievements": [
            "First Purchase",
            "Getting Started",
            "Regular Customer",
            "Loyal Shopper"
        ],
        "next_available_achievements": [
            "Dedicated Buyer",
            "Shopping Enthusiast",
            "Power Shopper"
        ],
        "current_badge": "Silver",
        "next_badge": "Gold",
        "remaining_to_unlock_next_badge": 2,
        "unlocked_achievements_details": [
            {
                "name": "First Purchase",
                "description": "Made your very first purchase",
                "icon": "🛒",
                "unlocked_at": "2026-04-15T09:28:08.000000Z"
            }
        ],
        "current_badge_details": {
            "name": "Silver",
            "icon": "🥈",
            "color": "#C0C0C0"
        },
        "next_badge_details": {
            "name": "Gold",
            "icon": "🥇",
            "color": "#FFD700",
            "required_achievements": 6
        },
        "total_achievements": 9,
        "total_unlocked": 4
    },
    "user": {
        "id": 1,
        "name": "Emmanuel Olawuni",
        "email": "emmanuelOlawuni@example.com"
    }
}
```

---

## Project Structure

```
app/
├── Events/
│   ├── PurchaseCompleted.php        # Fired when a purchase is created with status "completed"
│   ├── AchievementUnlocked.php      # Fired when a user unlocks a new achievement
│   └── BadgeUnlocked.php            # Fired when a user earns a new badge tier
├── Listeners/
│   ├── CheckAchievementsOnPurchase.php   # Calls AchievementService on purchase
│   ├── LogAchievementUnlocked.php        # Logs achievement unlocks
│   └── ProcessBadgeCashback.php          # Triggers ₦300 cashback via MockPaymentService
├── Services/
│   ├── AchievementService.php       # Core business logic for achievements & badges
│   └── MockPaymentService.php       # Simulates payment provider with transaction refs
├── Models/
│   ├── User.php                     # Extended with achievements(), currentBadge(), purchases()
│   ├── Achievement.php              # Milestone definitions (e.g., "First Purchase" = 1 purchase)
│   ├── Badge.php                    # Tier definitions (e.g., "Silver" = 4 achievements)
│   └── Purchase.php                 # Fires PurchaseCompleted event on creation
├── Http/Controllers/Api/
│   └── UserAchievementController.php    # Single endpoint: GET /api/users/{user}/achievements
└── Providers/
    └── EventServiceProvider.php     # Wires events to their listeners
```

---

## Achievements & Badges

### Achievements (unlocked by purchase count)

| Achievement         | Required Purchases |
| ------------------- | ------------------ |
| First Purchase      | 1                  |
| Getting Started     | 3                  |
| Regular Customer    | 5                  |
| Loyal Shopper       | 10                 |
| Dedicated Buyer     | 15                 |
| Shopping Enthusiast | 20                 |
| Power Shopper       | 30                 |
| Elite Customer      | 40                 |
| Shopping Legend     | 50                 |

### Badges (unlocked by achievement count)

| Badge    | Required Achievements | Cashback Reward |
| -------- | --------------------- | --------------- |
| Bronze   | 2                     | ₦300            |
| Silver   | 4                     | ₦300            |
| Gold     | 6                     | ₦300            |
| Platinum | 8                     | ₦300            |

Each badge unlock automatically triggers a **₦300 cashback** payment via the `ProcessBadgeCashback` listener. Payments are logged to `storage/logs/payments.log`.

---

## Running Tests

```bash
php artisan test --filter=BumpaLoyaltyTest
```

**10 tests covering:**

| Test                                                                | What it verifies                                                              |
| ------------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| `achievements_endpoint_returns_correct_structure`                   | API returns 200 with expected JSON shape                                      |
| `achievements_endpoint_returns_correct_data_for_user_with_progress` | Correct achievements, badge, and remaining count for a user with 12 purchases |
| `achievements_endpoint_for_new_user`                                | New user shows 1 achievement and "Beginner" badge                             |
| `achievements_endpoint_returns_404_for_nonexistent_user`            | Route model binding returns 404                                               |
| `purchase_completed_event_fires_on_purchase_creation`               | `PurchaseCompleted` dispatches for completed purchases                        |
| `purchase_completed_event_does_not_fire_for_pending_purchases`      | Event does NOT fire for pending status                                        |
| `achievement_unlocked_event_fires_when_threshold_reached`           | `AchievementUnlocked` fires with correct user and achievement                 |
| `badge_unlocked_event_fires_when_enough_achievements`               | `BadgeUnlocked` fires when 2+ achievements unlock Bronze                      |
| `achievement_service_does_not_duplicate_achievements`               | Running the service twice doesn't create duplicates                           |
| `get_user_progress_returns_complete_data`                           | Service returns all required keys with correct types                          |

---

## Demo Users (after seeding)

| User             | Email                       | Purchases | Achievements | Badge    |
| ---------------- | --------------------------- | --------- | ------------ | -------- |
| Emmanuel Olawuni | emmanuelOlawuni@example.com | 12        | 4            | Silver   |
| Chidi Eze        | chidi@example.com           | 1         | 1            | Beginner |

---

## Frontend Setup

See the `frontend/` directory README for React dashboard setup instructions.

---

## Tech Stack

- **Backend:** Laravel 11, PHP 8.2, MySQL
- **Frontend:** Next.js, React, TypeScript, Tailwind CSS
- **Auth Scaffolding:** Laravel Breeze (API)
- **Testing:** PHPUnit with RefreshDatabase
