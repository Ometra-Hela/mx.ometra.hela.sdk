<?php

namespace Ometra\HelaSdk\Dtos;

final class PortabilityDto extends DataTransferObject
{
    public function __construct(array $attributes = [], public readonly int|string|null $id = null, public readonly ?string $msisdn = null, public readonly ?string $status = null, public readonly ?string $transitoryMsisdn = null)
    {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        return new self($data, self::firstValue($data, ['id', 'id_portability']), self::nullableString(self::firstValue($data, ['msisdn', 'number_to_port'])), self::nullableString($data['status'] ?? null), self::nullableString(self::firstValue($data, ['transitory_msisdn', 'temporary_msisdn'])));
    }
}
