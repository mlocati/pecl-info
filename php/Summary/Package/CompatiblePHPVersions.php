<?php

declare(strict_types=1);
namespace PeclInfo\Summary\Package;

use JsonSerializable;
use PeclInfo\Pecl\Package\Version\Details;
use RuntimeException;

class CompatiblePHPVersions implements JsonSerializable
{
    private string $minPHPVersion;

    private string $maxPHPVersion;

    /**
     * @var string[]
     */
    private array $versions = [];

    public function __construct(string $minPHPVersion, string $maxPHPVersion)
    {
        $this->minPHPVersion = $minPHPVersion;
        $this->maxPHPVersion = $maxPHPVersion;
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
    public function addVersion(string $value): self
    {
        $this->versions[] = $value;
        usort(
            $this->versions,
            static function (string $a, string $b): int {
                return version_compare($a, $b);
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
        return $this->getMinPHPVersion() === $details->getMinPHPVersion() && $this->getMaxPHPVersion() === $details->getMaxPHPVersion();
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        $result = [
            'v' => $this->getVersions(),
        ];
        $minPHPVersion = $this->getMinPHPVersion();
        if ($minPHPVersion !== '') {
            $result['min'] = $minPHPVersion;
        }
        $maxPHPVersion = $this->getMaxPHPVersion();
        if ($maxPHPVersion !== '') {
            $result['max'] = $maxPHPVersion;
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
        $minPHPVersion = $value['min'] ?? null;
        if ($minPHPVersion === null) {
            $minPHPVersion = '';
        } elseif (!is_string($minPHPVersion)) {
            throw new RuntimeException('Missing/invalid min key for ' . __CLASS__);
        }
        $maxPHPVersion = $value['max'] ?? null;
        if ($maxPHPVersion === null) {
            $maxPHPVersion = '';
        } elseif (!is_string($maxPHPVersion)) {
            throw new RuntimeException('Missing/invalid max key for ' . __CLASS__);
        }
        $result = new static($minPHPVersion, $maxPHPVersion);
        foreach ($versions as $version) {
            if (!is_string($version) || $version === '') {
                throw new RuntimeException('Missing/invalid versions key for ' . __CLASS__);
            }
            $result->addVersion($version);
        }

        return $result;
    }
}
