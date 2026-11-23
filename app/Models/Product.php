<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'description', 'price', 'old_price', 'active', 'position'])]
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

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (filled($product->code)) {
                $product->code = strtoupper(trim($product->code));
            }
        });

        static::created(function (Product $product) {
            if (filled($product->code)) {
                return;
            }

            $code = sprintf('FN-%04d', $product->id);

            if (static::query()->where('code', $code)->exists()) {
                $code = sprintf('FN-%d', $product->id);
            }

            $product->forceFill(['code' => $code])->saveQuietly();
        });
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
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'old_price' => $this->old_price,
            'active' => $this->active,
            'images' => $this->images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'url' => $image->url,
                'focus_x' => $image->focus_x,
                'focus_y' => $image->focus_y,
            ])->values(),
        ];
    }
}
