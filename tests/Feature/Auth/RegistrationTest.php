<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'username' => 'test_reader',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('library.index', absolute: false));

        $user = auth()->user();

        $this->assertSame('test_reader', $user->username);
        $this->assertSame('test@example.com', $user->email);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_registration_requires_a_unique_email_address()
    {
        User::factory()->create(['email' => 'reader@example.com']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'username' => 'another_reader',
            'email' => 'reader@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_requires_a_unique_username()
    {
        User::factory()->create(['username' => 'nora_reader']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'username' => 'nora_reader',
            'email' => 'another@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
