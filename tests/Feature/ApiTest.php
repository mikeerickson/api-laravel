<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use refreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    /** @test */
    public function should_perform_simple_test()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

}
