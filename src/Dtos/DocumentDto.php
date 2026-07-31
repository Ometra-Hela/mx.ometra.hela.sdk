<?php

namespace Ometra\HelaSdk\Dtos;

final class DocumentDto extends DataTransferObject
{
    public function __construct(array $attributes = [], public readonly int|string|null $id = null, public readonly ?string $documentKey = null, public readonly ?string $status = null, public readonly ?int $version = null, public readonly ?string $expiresAt = null)
    {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        return new self($data, self::firstValue($data, ['id', 'id_document']), self::nullableString($data['document_key'] ?? null), self::nullableString($data['status'] ?? null), is_numeric($data['version'] ?? null) ? (int) $data['version'] : null, self::nullableString(self::firstValue($data, ['expires_at', 'dt_expiry'])));
    }
}
