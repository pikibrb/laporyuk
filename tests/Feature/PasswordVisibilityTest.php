<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_offers_a_password_visibility_toggle(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('x-data="{ showPassword: false }"', false)
            ->assertSee("x-bind:type=\"showPassword ? 'text' : 'password'\"", false)
            ->assertSee('Tampilkan atau sembunyikan kata sandi')
            ->assertSee('x-bind:aria-pressed="showPassword"', false);
    }

    public function test_registration_screen_toggles_both_password_fields(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('x-data="{ showPassword: false }"', false)
            ->assertSee("x-bind:type=\"showPassword ? 'text' : 'password'\"", false)
            ->assertSee('id="password"', false)
            ->assertSee('id="password_confirmation"', false);

        $this->assertSame(2, substr_count($this->get(route('register'))->getContent(), 'x-bind:aria-pressed="showPassword"'));
    }
}
