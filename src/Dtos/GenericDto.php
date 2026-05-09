<?php

namespace Ometra\HelaSdk\Dtos;

final class GenericDto extends DataTransferObject
{
    public static function from(mixed $payload): static
    {
        return new self(self::normalize($payload));
    }
}
