<?php

namespace App\Models;

use App\Events\PurchaseCompleted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'amount', 'product_name', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    protected static function booted(): void
    {
        static::created(function (Purchase $purchase) {
            if ($purchase->status === 'completed') {
                PurchaseCompleted::dispatch($purchase);
            }
        });
    }
}
