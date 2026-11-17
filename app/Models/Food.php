<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'category', 'description', 'price', 'offer_price', 'image_path', 'is_available', 'is_featured'])]
class Food extends Model
{
    public const CATEGORIES = [
        'Platos criollos',
        'Platos a la carta',
        'Entradas criollas',
        'Sopas criollas',
        'Pastas',
        'Hamburguesas',
        'Antojitos',
        'Saludables',
        'Postres',
        'Bebidas peruanas',
    ];

    use SoftDeletes;

    protected $table = 'foods';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'offer_price' => 'decimal:2',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getCurrentPriceAttribute(): string
    {
        return $this->offer_price ?? $this->price;
    }
}
