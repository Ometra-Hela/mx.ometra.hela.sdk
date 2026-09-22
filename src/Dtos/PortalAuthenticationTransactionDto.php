<?php

namespace Ometra\HelaSdk\Dtos;

final class PortalAuthenticationTransactionDto extends DataTransferObject
{
    public function __construct(array $attributes = [], public readonly ?PortalUserDto $user = null, public readonly ?string $authenticationId = null)
    {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self($data, isset($data['user']) ? PortalUserDto::from($data['user']) : null, self::nullableString($data['authentication_id'] ?? null));
    }
}
