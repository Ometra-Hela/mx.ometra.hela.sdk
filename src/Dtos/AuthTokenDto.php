<?php

namespace Ometra\HelaSdk\Dtos;

final class AuthTokenDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly ?string $token = null,
        public readonly ?string $clientUserUri = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            attributes: $data,
            token: self::nullableString(self::firstValue($data, ['token', 'value'])),
            clientUserUri: self::nullableString(self::firstValue($data, ['clientUserUri', 'uri_clientUser', 'client_user_uri'])),
        );
    }
}
