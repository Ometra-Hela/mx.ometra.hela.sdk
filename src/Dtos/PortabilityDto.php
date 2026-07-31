<?php

namespace Ometra\HelaSdk\Dtos;

final class PortabilityDto extends DataTransferObject
{
    public const SUBSCRIBER_TYPE_INDIVIDUAL = 'INDIVIDUAL';
    public const SUBSCRIBER_TYPE_BUSINESS = 'BUSINESS';

    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly ?string $msisdn = null,
        public readonly ?string $status = null,
        public readonly ?string $transitoryMsisdn = null,
        public readonly ?string $subscriberType = null,
    )
    {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        return new self(
            $data,
            self::firstValue($data, ['id', 'id_portability']),
            self::nullableString(self::firstValue($data, ['msisdn', 'number_to_port'])),
            self::nullableString(self::firstValue($data, ['status', 'state'])),
            self::nullableString(self::firstValue($data, ['transitory_msisdn', 'temporary_msisdn'])),
            self::nullableString(self::firstValue($data, ['subscriber_type', 'subscriberType'])),
        );
    }
}
