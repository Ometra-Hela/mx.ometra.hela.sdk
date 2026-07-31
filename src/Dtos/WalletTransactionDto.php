<?php

namespace Ometra\HelaSdk\Dtos;

final class WalletTransactionDto extends DataTransferObject
{
    public function __construct(array $attributes = [], public readonly int|string|null $id = null, public readonly int|float|null $amount = null, public readonly ?string $type = null, public readonly ?string $status = null, public readonly ?string $createdAt = null)
    {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        $amount = $data['amount'] ?? null;
        return new self($data, self::firstValue($data, ['id', 'id_transaction', 'uid']), is_int($amount) || is_float($amount) ? $amount : (is_numeric($amount) ? (float) $amount : null), self::nullableString(self::firstValue($data, ['type', 'transaction_type'])), self::nullableString($data['status'] ?? null), self::nullableString(self::firstValue($data, ['created_at', 'dt_created'])));
    }
}
