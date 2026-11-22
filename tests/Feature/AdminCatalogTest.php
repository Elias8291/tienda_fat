<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        return Product::create($attributes + ['name' => 'Blusa', 'price' => 20, 'active' => true]);
    }

    public function test_guests_are_sent_to_the_login(): void
    {
        $this->get('/admin/productos')->assertRedirect('/admin/login');
        $this->post('/admin/productos', ['name' => 'X'])->assertRedirect('/admin/login');
    }

    public function test_there_is_no_public_registration(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_staff_can_log_in_and_out(): void
    {
        $user = User::factory()->create(['password' => 'secreto123']);

        $this->post('/admin/login', ['username' => $user->username, 'password' => 'mala'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->post('/admin/login', ['username' => $user->email, 'password' => 'secreto123'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->post('/admin/login', ['username' => ' '.strtoupper($user->username), 'password' => 'secreto123'])
            ->assertRedirect('/admin/productos');
        $this->assertAuthenticatedAs($user);

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_login_is_locked_after_too_many_attempts(): void
    {
        $user = User::factory()->create(['password' => 'secreto123']);

        foreach (range(1, 5) as $_) {
            $this->post('/admin/login', ['username' => $user->username, 'password' => 'mala']);
        }

        $this->post('/admin/login', ['username' => $user->username, 'password' => 'secreto123'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_staff_can_create_a_product_with_photos(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post('/admin/productos', [
                'name' => 'Set rosa',
                'price' => '25',
                'old_price' => '30',
                'active' => '1',
                'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
            ])
            ->assertRedirect('/admin/productos');

        $product = Product::with('images')->sole();
        $this->assertSame('Set rosa', $product->name);
        $this->assertCount(2, $product->images);
        Storage::disk('public')->assertExists($product->images[0]->path);
        $this->assertMatchesRegularExpression('#^/storage/products/.+\.(jpe?g|png)$#', $product->images[0]->url);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('products.0.images.0.url', $product->images[0]->url));
    }

    public function test_an_uploaded_photo_can_be_opened_from_the_catalog(): void
    {
        $directory = storage_path('app/public/products');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $name = 'catalog-test-'.uniqid().'.jpg';
        file_put_contents($directory.DIRECTORY_SEPARATOR.$name, 'foto');

        try {
            $this->get('/storage/products/'.$name)->assertOk();
            $this->get('/storage/'.rawurlencode('../.env'))->assertNotFound();
        } finally {
            @unlink($directory.DIRECTORY_SEPARATOR.$name);
        }
    }

    public function test_the_catalog_keeps_the_chosen_crop(): void
    {
        $product = $this->product(['name' => 'Crop', 'active' => true]);
        $image = $product->images()->create(['path' => 'https://example.com/a.jpg', 'focus_x' => 50, 'focus_y' => 50]);

        $this->actingAs(User::factory()->create())
            ->post("/admin/productos/{$product->id}", [
                '_method' => 'put',
                'name' => 'Crop',
                'price' => '20',
                'active' => '1',
                'focus' => [['id' => $image->id, 'x' => 15, 'y' => 80]],
            ])
            ->assertRedirect('/admin/productos');

        $this->assertSame(15, $image->refresh()->focus_x);
        $this->assertSame(80, $image->focus_y);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('products.0.images.0.focus_x', 15)
            ->where('products.0.images.0.focus_y', 80));
    }

    public function test_a_new_product_needs_a_photo_and_a_valid_old_price(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/productos', ['name' => 'Set', 'price' => '25', 'old_price' => '10'])
            ->assertSessionHasErrors(['images', 'old_price']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_staff_can_edit_and_remove_photos(): void
    {
        Storage::fake('public');
        $product = $this->product();
        $old = $product->images()->create(['path' => UploadedFile::fake()->image('a.jpg')->store('products', 'public')]);

        $this->actingAs(User::factory()->create())
            ->post("/admin/productos/{$product->id}", [
                '_method' => 'put',
                'name' => 'Blusa nueva',
                'price' => '18',
                'active' => '1',
                'remove_images' => [$old->id],
                'images' => [UploadedFile::fake()->image('c.jpg')],
            ])
            ->assertRedirect('/admin/productos');

        $product->refresh()->load('images');
        $this->assertSame('Blusa nueva', $product->name);
        $this->assertCount(1, $product->images);
        Storage::disk('public')->assertMissing($old->path);
    }

    public function test_hidden_products_do_not_appear_in_the_store(): void
    {
        $product = $this->product(['name' => 'Oculto']);

        $this->actingAs(User::factory()->create())
            ->patch("/admin/productos/{$product->id}/visibilidad");

        $this->assertFalse($product->refresh()->active);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->component('Store')
            ->has('products', 0));
    }

    public function test_moving_a_product_changes_the_catalog_order(): void
    {
        $first = $this->product(['name' => 'Primero', 'position' => 1]);
        $second = $this->product(['name' => 'Segundo', 'position' => 2]);

        $this->patch("/admin/productos/{$second->id}/posicion", ['direction' => 'up'])
            ->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->create())
            ->patch("/admin/productos/{$second->id}/posicion", ['direction' => 'up'])
            ->assertRedirect();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('products.0.name', 'Segundo')
            ->where('products.1.name', 'Primero'));
    }

    public function test_staff_can_delete_a_product(): void
    {
        Storage::fake('public');
        $product = $this->product();
        $image = $product->images()->create(['path' => UploadedFile::fake()->image('a.jpg')->store('products', 'public')]);

        $this->actingAs(User::factory()->create())
            ->delete("/admin/productos/{$product->id}")
            ->assertRedirect('/admin/productos');

        $this->assertModelMissing($product);
        Storage::disk('public')->assertMissing($image->path);
    }
}
