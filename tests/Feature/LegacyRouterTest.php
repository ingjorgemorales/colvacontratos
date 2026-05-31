<?php

namespace Tests\Feature;

use Tests\TestCase;

final class LegacyRouterTest extends TestCase
{
    public function test_home_responds(): void
    {
        $response = $this->get('/?r=dashboard');
        $this->assertNotEquals(500, $response->getStatusCode());
    }
}
