<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthAunaTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    public function test_login()
    {
        $data = [
            "Codigo" => "03150",
            "Password" => "00000"
        ];

        $response = $this->postJson('/api/login', $data);

        $response->assertStatus(200);
    }
}
