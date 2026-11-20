<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function __invoke(): Response
    {
        $products = Product::active()
            ->with('images')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map->toCatalog();

        return Inertia::render('Store', [
            'products' => $products,
            'whatsapp' => config('services.whatsapp.number'),
        ]);
    }
}
