<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockPaymentService
{
    public function processCashback(User $user, float $amount, string $description): array
    {
        $ref = 'BUMPA-CB-' . strtoupper(Str::random(12));

        Log::info('CASHBACK PROCESSED', [
            'transaction_ref' => $ref,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'amount' => $amount,
            'currency' => 'NGN',
            'description' => $description,
        ]);

        return [
            'success' => true,
            'transaction_ref' => $ref,
            'amount' => $amount,
            'message' => "₦{$amount} cashback sent to {$user->email}",
        ];
    }
}
