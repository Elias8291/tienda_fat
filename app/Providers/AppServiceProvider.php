<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::disk('public')->serveUsing(function ($request, $path) {
            abort_unless(Storage::disk('public')->exists($path), 404);

            return Storage::disk('public')->response($path, null, [
                'Cache-Control' => 'public, max-age=604800',
                'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            ]);
        });
    }
}
