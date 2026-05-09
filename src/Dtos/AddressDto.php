<?php

namespace Ometra\HelaSdk\Dtos;

final class AddressDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly ?string $postalCode = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        if (isset($data['address']) && is_array($data['address'])) {
            $data = $data['address'];
        }

        return new self(
            attributes: $data,
            id: self::firstValue($data, ['id_address', 'address_id', 'id']),
            postalCode: self::nullableString(self::firstValue($data, ['cp', 'postal_code', 'zip_code'])),
        );
    }
}
