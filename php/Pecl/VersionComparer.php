<?php

declare(strict_types=1);

namespace PeclInfo\Pecl;

class VersionComparer
{
    public static function compare(string $versionA, string $versionB): int
    {
        return version_compare(self::normalize($versionA), self::normalize($versionB));
    }

    private static function normalize(string $version): string
    {
        $version = strtolower($version);
        // sqlsrv and pdo_sqlsrv used to use "preview" instead of "beta" - see https://github.com/microsoft/msphpsql/issues/1222
        $version = preg_replace('/([^a-z])preview(\b|\d)/', '\\1beta\\2', $version);
        // We have a WinCache version "1.1.0stable" (after "1.1.0beta2" and before "1.3.0")
        $version = preg_replace('/(\d)stable$/', '\\1.0', $version);

        return $version;
    }
}
