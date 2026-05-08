<?php

namespace Ometra\HelaSdk\Exceptions;

use InvalidArgumentException;

class MissingAppConfigurationException extends InvalidArgumentException
{
    public static function forApp(string $app): self
    {
        return new self(sprintf('Missing HELA app configuration for "%s".', $app));
    }

    public static function missingBaseUrl(string $app): self
    {
        return new self(sprintf('Missing HELA app base URL configuration for "%s".', $app));
    }
}
