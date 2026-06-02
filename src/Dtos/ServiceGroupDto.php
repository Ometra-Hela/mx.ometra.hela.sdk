<?php

namespace Ometra\HelaSdk\Dtos;

final class ServiceGroupDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly int|string|null $idServiceGroup = null,
        public readonly ?string $name = null,
        public readonly ?string $icon = null,
        public readonly ?int $servicesCount = null,
        public readonly ?string $dtCreated = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        if (isset($data['group']) && is_array($data['group'])) {
            $data = $data['group'];
        }

        return new self(
            attributes: $data,
            id: self::firstValue($data, ['id', 'id_serviceGroup', 'service_group_id']),
            idServiceGroup: self::firstValue($data, ['id_serviceGroup', 'id', 'service_group_id']),
            name: self::nullableString($data['name'] ?? null),
            icon: self::nullableString($data['icon'] ?? null),
            servicesCount: is_numeric($data['services_count'] ?? null) ? (int) $data['services_count'] : null,
            dtCreated: self::nullableString(self::firstValue($data, ['dt_created', 'created_at'])),
        );
    }
}
