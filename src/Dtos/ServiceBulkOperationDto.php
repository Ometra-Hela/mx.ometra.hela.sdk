<?php

namespace Ometra\HelaSdk\Dtos;

final class ServiceBulkOperationDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly int|string|null $idServiceBulkOperation = null,
        public readonly int|string|null $idServiceGroup = null,
        public readonly int|string|null $idOrder = null,
        public readonly ?string $action = null,
        public readonly ?string $actionLabel = null,
        public readonly ?string $status = null,
        public readonly ?bool $isTerminal = null,
        public readonly ?int $totalCount = null,
        public readonly ?int $pendingCount = null,
        public readonly ?int $successCount = null,
        public readonly ?int $failedCount = null,
        public readonly ?int $progressPercent = null,
        public readonly ?float $totalAmount = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        if (isset($data['operation']) && is_array($data['operation'])) {
            $data = $data['operation'];
        }

        return new self(
            attributes: $data,
            id: self::firstValue($data, ['id', 'id_serviceBulkOperation']),
            idServiceBulkOperation: self::firstValue($data, ['id_serviceBulkOperation', 'id']),
            idServiceGroup: self::firstValue($data, ['id_serviceGroup', 'group_id']),
            idOrder: self::firstValue($data, ['id_order']),
            action: self::nullableString($data['action'] ?? null),
            actionLabel: self::nullableString(self::firstValue($data, ['action_label', 'actionLabel'])),
            status: self::nullableString($data['status'] ?? null),
            isTerminal: isset($data['is_terminal']) ? (bool) $data['is_terminal'] : null,
            totalCount: is_numeric($data['total_count'] ?? null) ? (int) $data['total_count'] : null,
            pendingCount: is_numeric($data['pending_count'] ?? null) ? (int) $data['pending_count'] : null,
            successCount: is_numeric($data['success_count'] ?? null) ? (int) $data['success_count'] : null,
            failedCount: is_numeric($data['failed_count'] ?? null) ? (int) $data['failed_count'] : null,
            progressPercent: is_numeric($data['progress_percent'] ?? null) ? (int) $data['progress_percent'] : null,
            totalAmount: self::nullableFloat($data['total_amount'] ?? null),
        );
    }
}
