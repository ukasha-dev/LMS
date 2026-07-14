<?php

final class PilotAccessGate
{
    public static function isAllowed(string $environment): bool
    {
        return $environment === 'development';
    }
}
