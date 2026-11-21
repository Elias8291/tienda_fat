<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['path', 'position'])]
class ProductImage extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isUploaded(): bool
    {
        return ! Str::startsWith($this->path, ['http://', 'https://']);
    }

    protected function url(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->isUploaded()) {
                return $this->path;
            }

            return '/storage/'.ltrim(str_replace('\\', '/', $this->path), '/');
        });
    }

    protected static function booted(): void
    {
        static::deleted(function (ProductImage $image) {
            if ($image->isUploaded()) {
                Storage::disk('public')->delete($image->path);
            }
        });
    }
}
