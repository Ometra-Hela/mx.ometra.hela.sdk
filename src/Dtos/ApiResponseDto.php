<?php

namespace Ometra\HelaSdk\Dtos;

use Illuminate\Http\Client\Response;

final class ApiResponseDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $headers
     */
    public function __construct(
        array $attributes,
        public readonly mixed $data = null,
        public readonly ?string $message = null,
        public readonly int $status = 200,
        public readonly bool $successful = true,
        public readonly array $headers = [],
    ) {
        parent::__construct($attributes);
    }

    public static function fromResponse(Response $response): self
    {
        $payload = $response->json();
        $attributes = is_array($payload) ? $payload : [];

        return new self(
            attributes: $attributes,
            data: $attributes['data'] ?? null,
            message: isset($attributes['message']) ? (string) $attributes['message'] : null,
            status: $response->status(),
            successful: $response->successful(),
            headers: $response->headers(),
        );
    }
}
