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
