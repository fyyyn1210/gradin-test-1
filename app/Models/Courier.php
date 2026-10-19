<?php

namespace App\Models;

use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'phone',
    'email',
    'address',
    'vehicle_type',
    'vehicle_plate',
    'level',
    'is_active',
    'registered_at',
])]
class Courier extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleType::class,
            'level' => 'integer',
            'is_active' => 'boolean',
            'registered_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        foreach (explode(' ', Str::squish($term)) as $word) {
            $query->whereLike('name', "%{$word}%");
        }
    }
}
