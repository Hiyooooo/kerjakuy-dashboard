<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->withoutVite()->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee('Belum ada data operasional');
    }
}
