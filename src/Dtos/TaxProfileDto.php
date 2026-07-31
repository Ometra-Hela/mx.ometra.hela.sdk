<?php

namespace Ometra\HelaSdk\Dtos;

final class TaxProfileDto extends DataTransferObject
{
    public function __construct(array $attributes = [], public readonly ?string $uid = null, public readonly ?string $rfc = null, public readonly ?string $name = null, public readonly ?string $taxRegime = null, public readonly ?string $cfdiUse = null)
    {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        return new self($data, self::nullableString($data['uid'] ?? null), self::nullableString($data['rfc'] ?? null), self::nullableString(self::firstValue($data, ['name', 'business_name'])), self::nullableString(self::firstValue($data, ['tax_regime', 'fiscal_regime'])), self::nullableString(self::firstValue($data, ['cfdi_use', 'use_cfdi'])));
    }
}
