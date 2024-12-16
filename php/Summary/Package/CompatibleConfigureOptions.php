<?php

declare(strict_types=1);

namespace PeclInfo\Summary\Package;

use JsonSerializable;
use PeclInfo\Pecl\Package\Version\ConfigureOption;
use PeclInfo\Pecl\Package\Version\Details;
use PeclInfo\Pecl\VersionComparer;
use RuntimeException;

class CompatibleConfigureOptions implements JsonSerializable
{
    /**
     * @var \PeclInfo\Pecl\Package\Version\ConfigureOption[]
     */
    private array $configureOptions = [];

    /**
     * @var string[]
     */
    private array $versions = [];

    /**
     * @param \PeclInfo\Pecl\Package\Version\ConfigureOption[] $configureOptions
     */
    public function __construct(array $configureOptions)
    {
        $this->configureOptions = $configureOptions;
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

    public function isCompatibleWith(Details $details): bool
    {
        return json_encode($this->getConfigureOptions()) === json_encode($details->getConfigureOptions());
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

        $configureOptions = $this->getConfigureOptions();
        if ($configureOptions !== []) {
            $result['opts'] = $configureOptions;
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
        $serializedConfigureOptions = $value['opts'] ?? [];
        if (!is_array($serializedConfigureOptions)) {
            throw new RuntimeException('Missing/invalid configure options key for ' . __CLASS__);
        }
        $configureOptions = [];
        foreach ($serializedConfigureOptions as $serializedConfigureOption) {
            if (!is_array($serializedConfigureOption)) {
                throw new RuntimeException('Missing/invalid configure options key for ' . __CLASS__);
            }
            $configureOptions[] = ConfigureOption::fromJSON($serializedConfigureOption);
        }
        $result = new static($configureOptions);
        foreach ($versions as $version) {
            if (!is_string($version) || $version === '') {
                throw new RuntimeException('Missing/invalid versions key for ' . __CLASS__);
            }
            $result->addVersion($version);
        }

        return $result;
    }
}
