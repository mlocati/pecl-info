<?php

declare(strict_types=1);

namespace PeclInfo\Pecl\Package\Version;

use PeclInfo\Pecl\Package\Version;
use RuntimeException;

class Details
{
    private Version $packageVersion;

    private string $minPHPVersion;

    private string $maxPHPVersion;

    /**
     * @var \PeclInfo\Pecl\Package\Version\ConfigureOption[]
     */
    private array $configureOptions = [];

    /**
     * @var \PeclInfo\Pecl\Package\Version\RequiredPackage[]
     */
    private array $requiredPackages = [];

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

    /**
     * @return $this
     */
    public function addRequiredPackage(RequiredPackage $value): self
    {
        $requiredPackages = $this->requiredPackages;
        $requiredPackages[] = $value;
        usort($requiredPackages, static function (RequiredPackage $a, RequiredPackage $b): int {
            $cmp = strnatcasecmp($a->getName(), $b->getName());
            if ($cmp === 0) {
                throw new RuntimeException("Duplicated required package: {$a->getName()}");
            }

            return $cmp;
        });
        $this->requiredPackages = $requiredPackages;

        return $this;
    }

    /**
     * @return \PeclInfo\Pecl\Package\Version\ConfigureOption[]
     */
    public function getRequiredPackages(): array
    {
        return $this->requiredPackages;
    }
}
