<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    // move this to a API Test Trait
    public function assertResultHasKey($response, $key)
    {
        $result = (array)json_decode($response->getContent());

        return $this->assertArrayHasKey($key, $result);
    }
}
