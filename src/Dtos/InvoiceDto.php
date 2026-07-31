<?php

namespace Ometra\HelaSdk\Dtos;

final class InvoiceDto extends DataTransferObject
{
    public function __construct(array $attributes = [], public readonly int|string|null $id = null, public readonly ?string $uid = null, public readonly ?string $folio = null, public readonly ?float $total = null, public readonly ?string $status = null, public readonly ?string $issuedAt = null)
    {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        return new self($data, self::firstValue($data, ['id', 'id_clientInvoice', 'id_invoice']), self::nullableString($data['uid'] ?? null), self::nullableString(self::firstValue($data, ['folio', 'invoice_number'])), self::nullableFloat(self::firstValue($data, ['total', 'amount'])), self::nullableString($data['status'] ?? null), self::nullableString(self::firstValue($data, ['issued_at', 'dt_issued', 'created_at'])));
    }
}
