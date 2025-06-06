<?php

namespace WeDesignIt\Common\Tests\Api;

use PHPUnit\Framework\TestCase;
use WeDesignIt\Common\Api\ApiClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;

class ApiClientTest extends TestCase
{
    public function test_request_makes_psr_call()
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $response = new Response(200, [], json_encode(['ok' => true]));

        $requestFactory->method('createRequest')
            ->willReturn(new Request('GET', 'http://test.local/me'));

        $client->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $apiClient = new class ('http://test.local', $client, $requestFactory) extends ApiClient {
            public function getMe()
            {
                return $this->request('GET', '/me');
            }
        };

        $result = $apiClient->getMe();
        $this->assertEquals(['ok' => true], $result);
    }
}
