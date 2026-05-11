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
        public readonly int|string|null $offerId = null,
        public readonly ?string $msisdn = null,
        public readonly ?string $status = null,
        public readonly ?string $altanStatus = null,
        public readonly ?string $serviceType = null,
        public readonly ?string $dtServiceExpirity = null,
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
            id: self::firstValue($data, ['id', 'id_service', 'service_id']),
            clientId: self::firstValue($data, ['clientId', 'id_client', 'client_id']),
            offerId: self::firstValue($data, ['offerId', 'offer_id']),
            msisdn: self::nullableString($data['msisdn'] ?? null),
            status: self::nullableString($data['status'] ?? null),
            altanStatus: self::nullableString(self::firstValue($data, ['altanStatus', 'altan_status'])),
            serviceType: self::nullableString(self::firstValue($data, ['serviceType', 'service_type'])),
            dtServiceExpirity: self::nullableString(self::firstValue($data, ['dtServiceExpirity', 'dt_service_expirity'])),
            users: is_array($data['users'] ?? null) ? $data['users'] : [],
        );
    }
}
