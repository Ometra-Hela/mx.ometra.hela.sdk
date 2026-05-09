<?php

namespace Ometra\HelaSdk\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class HelaRequestException extends RuntimeException
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly array $errors = [],
        public readonly ?Response $response = null,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(Response $response): self
    {
        $payload = $response->json();
        $payload = is_array($payload) ? $payload : [];

        $message = isset($payload['message'])
            ? (string) $payload['message']
            : ($response->body() ?: 'HELA request failed.');

        $errors = isset($payload['errors']) && is_array($payload['errors'])
            ? $payload['errors']
            : [];

        return new self($message, $response->status(), $errors, $response);
    }
}
