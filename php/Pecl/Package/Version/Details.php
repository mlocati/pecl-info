<?php

declare(strict_types=1);
namespace PeclInfo\Pecl\Package\Version;

use PeclInfo\Pecl\Package\Version;

class Details
{
    private Version $packageVersion;

    private string $minPHPVersion;

    private string $maxPHPVersion;

    /**
     * @var \PeclInfo\Pecl\Package\Version\ConfigureOption[]
     */
    private array $configureOptions = [];

    public function __construct(Version $packageVersion, string $minPHPVersion, string $maxPHPVersion = '')
    {
        $this->packageVersion = $packageVersion;
        $this->minPHPVersion = $minPHPVersion;
        $this->maxPHPVersion = $maxPHPVersion;
    }

    public function getPackageVersion(): Version
    {
        return $this->version;
    }

    public function getMinPHPVersion(): string
    {
        return $this->minPHPVersion;
    }

    public function getMaxPHPVersion(): string
    {
        return $this->maxPHPVersion;
    }

    /**
     * @return $this
     */
    public function addConfigureOption(ConfigureOption $value): self
    {
        $this->configureOptions[] = $value;

        return $this;
    }

    /**
     * @return \PeclInfo\Pecl\Package\Version\ConfigureOption[]
     */
    public function getConfigureOptions(): array
    {
        return $this->configureOptions;
    }
}
