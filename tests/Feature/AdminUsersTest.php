<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_users(): void
    {
        $this->get('/admin/usuarios')->assertRedirect('/admin/login');
        $this->post('/admin/usuarios', ['name' => 'Ana'])->assertRedirect('/admin/login');
    }

    public function test_staff_can_create_a_user_who_can_log_in(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/usuarios', [
                'name' => 'Lucía',
                'username' => ' Lucia.Admin ',
                'password' => 'clave1234',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $created = User::where('username', 'lucia.admin')->first();
        $this->assertNotNull($created);
        $this->assertSame('Lucía', $created->name);

        $this->post('/admin/logout');
        $this->post('/admin/login', ['username' => 'lucia.admin', 'password' => 'clave1234'])
            ->assertRedirect('/admin/productos');
        $this->assertAuthenticatedAs($created);
    }

    public function test_a_duplicate_username_is_rejected(): void
    {
        User::factory()->create(['username' => 'jacque']);

        $this->actingAs(User::factory()->create())
            ->post('/admin/usuarios', [
                'name' => 'Otra',
                'username' => 'Jacque',
                'password' => 'clave1234',
            ])
            ->assertSessionHasErrors('username');

        $this->assertSame(1, User::where('username', 'jacque')->count());
    }

    public function test_staff_can_delete_another_user_but_not_themselves(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($me)
            ->delete("/admin/usuarios/{$me->id}")
            ->assertForbidden();
        $this->assertModelExists($me);

        $this->actingAs($me)
            ->delete("/admin/usuarios/{$other->id}")
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertModelMissing($other);
    }
}
