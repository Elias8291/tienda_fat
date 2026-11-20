<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $products = Product::with('images')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map->toCatalog();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => ['q' => $search],
            'stats' => [
                'total' => Product::count(),
                'active' => Product::active()->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', ['product' => null]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $product = Product::create([
                ...$request->safe()->except(['images', 'remove_images']),
                'position' => ((int) Product::min('position')) - 1,
            ]);
            $this->storeImages($request, $product);
        });

        return redirect()->route('admin.products.index')->with('success', 'Producto creado.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => $product->load('images')->toCatalog(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($request->safe()->except(['images', 'remove_images']));

            $product->images()
                ->whereIn('id', $request->input('remove_images', []))
                ->get()
                ->each->delete();

            $this->storeImages($request, $product);
        });

        return redirect()->route('admin.products.index')->with('success', 'Producto actualizado.');
    }

    public function move(Request $request, Product $product): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $neighbor = Product::query()
            ->when($direction === 'up', fn ($query) => $query->where('position', '<', $product->position)->orderByDesc('position'))
            ->when($direction === 'down', fn ($query) => $query->where('position', '>', $product->position)->orderBy('position'))
            ->orderBy('id')
            ->first();

        if ($neighbor) {
            [$product->position, $neighbor->position] = [$neighbor->position, $product->position];
            $product->save();
            $neighbor->save();
        }

        return back();
    }

    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['active' => ! $product->active]);

        return back()->with('success', $product->active ? 'Producto visible en la tienda.' : 'Producto oculto de la tienda.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        DB::transaction(function () use ($product) {
            $product->images->each->delete();
            $product->delete();
        });

        return redirect()->route('admin.products.index')->with('success', 'Producto eliminado.');
    }

    private function storeImages(ProductRequest $request, Product $product): void
    {
        $position = (int) $product->images()->max('position');

        foreach ($request->file('images', []) as $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'position' => ++$position,
            ]);
        }
    }
}
