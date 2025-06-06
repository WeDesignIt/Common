<?php

namespace WeDesignIt\Common\Http\Response;

use Psr\Http\Message\ResponseInterface;

class ResponseSerializer
{
    /**
     * @var callable
     */
    private $responseFactory;

    /**
     * @param callable|null $responseFactory fn(int $status, array $headers, string $body): ResponseInterface
     */
    public function __construct(?callable $responseFactory = null)
    {
        if ($responseFactory) {
            $this->responseFactory = $responseFactory;
        } else {
            $this->responseFactory = self::detectResponseFactory();
        }
    }

    public static function detectResponseFactory(): callable
    {
        if (class_exists('\GuzzleHttp\Psr7\Response')) {
            return fn($status, $headers, $body) => new \GuzzleHttp\Psr7\Response($status, $headers, $body);
        }
        if (class_exists('\Nyholm\Psr7\Response')) {
            return fn($status, $headers, $body) => new \Nyholm\Psr7\Response($status, $headers, $body);
        }
        if (class_exists('\Laminas\Diactoros\Response')) {
            // Laminas construction
            return function ($status, $headers, $body) {
                $response = new \Laminas\Diactoros\Response('php://temp', $status, $headers);
                $response->getBody()->write($body);
                return $response;
            };
        }
        throw new \RuntimeException('No PSR-7 Response implementation found.');
    }

    /**
     * Serialize ResponseInterface to array.
     *
     * @return array{status: int, headers: array<string, array<int, string>>, body: string}
     */
    public function serialize(ResponseInterface $response): array
    {
        return [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string)$response->getBody(),
        ];
    }

    /**
     * Deserialize array to PSR-7 response object.
     *
     * @param array<string, mixed> $data
     */
    public function deserialize(array $data): ResponseInterface
    {
        return call_user_func(
            $this->responseFactory,
            $data['status'] ?? 200,
            $data['headers'] ?? [],
            $data['body'] ?? ''
        );
    }
}
