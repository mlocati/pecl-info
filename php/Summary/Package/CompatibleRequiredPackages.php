<?php

declare(strict_types=1);

namespace PeclInfo\Summary\Package;

use JsonSerializable;
use PeclInfo\Pecl\Package\Version\Details;
use PeclInfo\Pecl\Package\Version\RequiredPackage;
use PeclInfo\Pecl\VersionComparer;
use RuntimeException;

class CompatibleRequiredPackages implements JsonSerializable
{
    /**
     * @var \PeclInfo\Pecl\Package\Version\RequiredPackage[]
     */
    private array $requiredPackages = [];

    /**
     * @var string[]
     */
    private array $versions = [];

    /**
     * @param \PeclInfo\Pecl\Package\Version\RequiredPackage[] $requiredPackages
     */
    public function __construct(array $requiredPackages)
    {
        $this->requiredPackages = $requiredPackages;
    }

    /**
     * @return \PeclInfo\Pecl\Package\Version\RequiredPackage[]
     */
    public function getRequiredPackages(): array
    {
        return $this->requiredPackages;
    }

    /**
     * @return $this
     */
    public function addVersion(string $value): self
    {
        $this->versions[] = $value;
        usort(
            $this->versions,
            static function (string $a, string $b): int {
                return VersionComparer::compare($a, $b);
            }
        );

        return $this;
    }

    /**
     * @return string[]
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    public function isCompatibleWith(Details $info): bool
    {
        return json_encode($this->getRequiredPackages()) === json_encode($info->getRequiredPackages());
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(): array
    {
        $result = [
            'v' => $this->getVersions(),
        ];

        $requiredPackages = $this->getRequiredPackages();
        if ($requiredPackages !== []) {
            $result['pkgs'] = $requiredPackages;
        }

        return $result;
    }

    /**
     * @throws \RuntimeException
     *
     * @return static
     */
    public static function fromJSON(array $value): self
    {
        $versions = $value['v'] ?? null;
        if (!is_array($versions) || $versions === []) {
            throw new RuntimeException('Missing/invalid versions key for ' . __CLASS__);
        }
        $serializedRequiredPackages = $value['pkgs'] ?? [];
        if (!is_array($serializedRequiredPackages)) {
            throw new RuntimeException('Missing/invalid required packages key for ' . __CLASS__);
        }
        $requiredPackages = [];
        foreach ($serializedRequiredPackages as $serializedRequiredPackage) {
            if (!is_array($serializedRequiredPackage)) {
                throw new RuntimeException('Missing/invalid required packages key for ' . __CLASS__);
            }
            $requiredPackages[] = RequiredPackage::fromJSON($serializedRequiredPackage);
        }
        $result = new static($requiredPackages);
        foreach ($versions as $version) {
            if (!is_string($version) || $version === '') {
                throw new RuntimeException('Missing/invalid versions key for ' . __CLASS__);
            }
            $result->addVersion($version);
        }

        return $result;
    }
}
