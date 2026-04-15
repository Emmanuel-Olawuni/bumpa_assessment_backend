<?php

namespace App\Listeners;

use App\Events\BadgeUnlocked;
use App\Services\MockPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessBadgeCashback implements ShouldQueue
{
    public function __construct(private MockPaymentService $paymentService) {}

    public function handle(BadgeUnlocked $event): void
    {
        $result = $this->paymentService->processCashback(
            user: $event->user,
            amount: 300,
            description: "Cashback for unlocking '{$event->badge->name}' badge"
        );

        Log::info('Badge Cashback', [
            'user' => $event->user->id,
            'badge' => $event->badge->name,
            'success' => $result['success'],
            'ref' => $result['transaction_ref'],
        ]);
    }
}
