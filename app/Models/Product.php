<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'price', 'old_price', 'active', 'position'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'float',
            'old_price' => 'float',
            'active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toCatalog(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'old_price' => $this->old_price,
            'active' => $this->active,
            'images' => $this->images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'url' => $image->url,
            ])->values(),
        ];
    }
}
