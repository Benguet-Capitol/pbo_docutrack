<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Accounts are managed by an administrator (Users): the profile page shows, but can't change or delete the account */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();
    }

    public function test_profile_information_can_not_be_changed_by_the_user(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', ['name' => 'New Name', 'username' => $user->username])
            ->assertSessionHasErrors('profile');

        $this->assertSame('Original Name', $user->fresh()->name);
    }

    public function test_users_can_not_delete_their_own_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrors('profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_self_registration_is_disabled(): void
    {
        $this->from('/register')
            ->post('/register', ['name' => 'Someone', 'username' => 'someone', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrors('registration');

        $this->assertNull(User::where('username', 'someone')->first());
    }
}
