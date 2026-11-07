<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_the_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }

    public function test_application_uses_dubai_gulf_standard_time(): void
    {
        $this->assertSame('Asia/Dubai', config('app.timezone'));
        $this->assertSame('+04:00', now()->format('P'));
    }
}
