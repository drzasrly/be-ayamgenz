<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityTest extends TestCase
{
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}