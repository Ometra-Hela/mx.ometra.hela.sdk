<?php

namespace Ometra\HelaSdk\Dtos;

final class FederatedSessionDto extends DataTransferObject
{
    public function __construct(
        array $attributes = [],
        public readonly ?PortalUserDto $user = null,
        public readonly ?string $accessToken = null,
        public readonly ?string $refreshToken = null,
        public readonly ?string $accessTokenExpiresAt = null,
        public readonly ?string $absoluteExpiresAt = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            $data,
            isset($data['user']) ? PortalUserDto::from($data['user']) : null,
            self::nullableString($data['access_token'] ?? null),
            self::nullableString($data['refresh_token'] ?? null),
            self::nullableString($data['access_token_expires_at'] ?? null),
            self::nullableString($data['absolute_expires_at'] ?? null),
        );
    }
}
