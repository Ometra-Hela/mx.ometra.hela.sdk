<?php

namespace Ometra\HelaSdk\Dtos;

final class PortalUserDto extends DataTransferObject
{
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly int|string|null $clientId = null,
        public readonly ?string $email = null,
        public readonly ?string $name = null,
        public readonly array $roles = [],
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            $data,
            $data['id'] ?? null,
            $data['client_id'] ?? null,
            self::nullableString($data['email'] ?? null),
            self::nullableString($data['name'] ?? null),
            is_array($data['roles'] ?? null) ? $data['roles'] : [],
        );
    }
}
