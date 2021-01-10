<?php

declare(strict_types=1);
namespace PeclInfo\Pecl\Package;

class Version
{
    private string $packageName;

    private string $version;

    private string $stability;

    public function __construct(string $packageName, string $version, string $stability)
    {
        $this->packageName = $packageName;
        $this->version = $version;
        $this->stability = $stability;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getStability(): string
    {
        return $this->stability;
    }
}
