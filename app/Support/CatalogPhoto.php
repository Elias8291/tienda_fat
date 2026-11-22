<?php

namespace App\Support;

use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CatalogPhoto
{
    public const MAX_EDGE = 960;

    public static function store(UploadedFile $file): string
    {
        $binary = self::shrink($file->getContent());

        if ($binary === null) {
            return $file->store('products', 'public');
        }

        $path = 'products/'.Str::random(40).'.'.self::extension();
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public static function shrinkStored(): int
    {
        $count = 0;
        $disk = Storage::disk('public');

        foreach (ProductImage::query()->get() as $image) {
            if (! $image->isUploaded() || ! $disk->exists($image->path)) {
                continue;
            }

            $info = @getimagesize($disk->path($image->path));
            $extension = strtolower(pathinfo($image->path, PATHINFO_EXTENSION));
            $withinFrame = is_array($info) && max($info[0], $info[1]) <= self::MAX_EDGE;
            $compact = $extension === self::extension() && $disk->size($image->path) <= 280_000;

            if ($withinFrame && $compact) {
                continue;
            }

            try {
                $binary = self::shrink($disk->get($image->path));

                if ($binary === null) {
                    continue;
                }

                $path = 'products/'.Str::random(40).'.'.self::extension();
                $disk->put($path, $binary);
                $previous = $image->path;
                $image->update(['path' => $path]);
                $disk->delete($previous);
                $count++;
            } catch (Throwable) {
                continue;
            }
        }

        return $count;
    }

    private static function shrink(string $contents): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return null;
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return null;
        }

        $source = self::orient($source, $contents);
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_EDGE / max($width, $height, 1));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($canvas === false) {
            imagedestroy($source);

            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($source);

        ob_start();
        $saved = imagewebp($canvas, null, 72);
        $binary = ob_get_clean();
        imagedestroy($canvas);

        return $saved && is_string($binary) && $binary !== '' ? $binary : null;
    }

    private static function orient(\GdImage $image, string $contents): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contents);
        rewind($stream);
        $exif = @exif_read_data($stream);
        fclose($stream);

        $rotated = match ($exif['Orientation'] ?? 1) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated !== $image && $rotated !== false) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private static function extension(): string
    {
        return 'webp';
    }
}
