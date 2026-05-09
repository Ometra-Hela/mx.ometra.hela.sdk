<?php

namespace Ometra\HelaSdk\Dtos;

final class UserProfileDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $clientId = null,
        public readonly int|string|null $userId = null,
        public readonly ?string $email = null,
        public readonly ?string $name = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        if (isset($data['user']) && is_array($data['user'])) {
            $data = $data['user'];
        }

        return new self(
            attributes: $data,
            clientId: self::firstValue($data, ['id_client', 'client_id']),
            userId: self::firstValue($data, ['id_user', 'id_clientUser', 'user_id']),
            email: self::nullableString($data['email'] ?? null),
            name: self::nullableString(self::firstValue($data, ['name', 'full_name'])),
        );
    }
}
