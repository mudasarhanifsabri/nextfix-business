<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthSessionRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_html_is_never_cached_with_an_old_csrf_token(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
    }

    public function test_expired_form_recovers_instead_of_showing_419(): void
    {
        Route::post('/expired-session-probe', fn () => throw new TokenMismatchException);

        $this->post('/expired-session-probe')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your session expired safely. Please sign in again to continue.');
    }
}
