<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'required_achievements', 'icon', 'color'];

    public function users()
    {
        return $this->hasMany(User::class, 'current_badge_id');
    }
}
