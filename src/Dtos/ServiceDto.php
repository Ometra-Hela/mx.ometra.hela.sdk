<?php

namespace Ometra\HelaSdk\Dtos;

final class ServiceDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<int, mixed> $users
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly int|string|null $clientId = null,
        public readonly ?string $msisdn = null,
        public readonly ?string $status = null,
        public readonly array $users = [],
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        if (isset($data['service']) && is_array($data['service'])) {
            $data = $data['service'];
        }

        return new self(
            attributes: $data,
            id: self::firstValue($data, ['id_service', 'service_id', 'id']),
            clientId: self::firstValue($data, ['id_client', 'client_id']),
            msisdn: self::nullableString($data['msisdn'] ?? null),
            status: self::nullableString($data['status'] ?? null),
            users: is_array($data['users'] ?? null) ? $data['users'] : [],
        );
    }
}
