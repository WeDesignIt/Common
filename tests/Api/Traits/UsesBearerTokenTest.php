<?php

namespace WeDesignIt\Common\Tests\Api\Traits;

use PHPUnit\Framework\TestCase;
use WeDesignIt\Common\Api\Traits\UsesBearerToken;

class UsesBearerTokenTest extends TestCase
{
    public function test_adds_bearer_token_to_headers()
    {
        $class = new class {
            use UsesBearerToken;
        };
        // use reflection to access protected property $bearerToken
        $reflection = new \ReflectionClass($class);
        $property = $reflection->getProperty('bearerToken');
        $property->setAccessible(true);
        $property->setValue($class, 'bliep');

        // use reflection to access protected method prepareHeaders
        $method = $reflection->getMethod('prepareHeaders');
        $method->setAccessible(true);
        // Call the method with an empty array
        // does:
        // $headers = $class->prepareHeaders([]);
        $headers = $method->invoke($class, []);

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals('Bearer bliep', $headers['Authorization']);
    }
}
