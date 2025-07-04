<?php

namespace WeDesignIt\Common\Tests\Http\Traits;

use PHPUnit\Framework\TestCase;
use WeDesignIt\Common\Http\Traits\UsesBearerToken;

class UsesBearerTokenTest extends TestCase
{
    public function test_adds_bearer_token_to_headers() : void
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
        /** @var array<string, string> $headers */
        // Call the method with an empty array
        // does:
        // $headers = $class->prepareHeaders([]);
        $headers = $method->invoke($class, []);

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals('Bearer bliep', $headers['Authorization']);
    }
}
