<?php

declare(strict_types=1);
namespace PeclInfo\Pecl\Package\Version;

use JsonSerializable;
use RuntimeException;

class RequiredPackage implements JsonSerializable
{
    private string $name;

    private string $minVersion;

    private string $maxVersion;

    /**
     * @var string[]
     */
    private array $excludedVersions;

    private string $uri;

    /**
     * @param string[] $excludedVersions
     */
    public function __construct(string $name, string $minVersion, string $maxVersion, array $excludedVersions, string $uri)
    {
        $this->name = $name;
        $this->minVersion = $minVersion;
        $this->maxVersion = $maxVersion;
        usort($excludedVersions, static function (string $a, string $b): int {
            return version_compare($a, $b);
        });
        $this->excludedVersions = array_values($excludedVersions);
        $this->uri = $uri;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMinVersion(): string
    {
        return $this->minVersion;
    }

    public function getMaxVersion(): string
    {
        return $this->maxVersion;
    }

    /**
     * @return string[]
     */
    public function getExcludedVersions(): array
    {
        return $this->excludedVersions;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        $result = [
            'n' => $this->getName(),
        ];
        if (($minVersion = $this->getMinVersion()) !== '') {
            $result['m'] = $minVersion;
        }
        if (($maxVersion = $this->getMaxVersion()) !== '') {
            $result['M'] = $maxVersion;
        }
        if (($excludedVersions = $this->getExcludedVersions()) !== []) {
            $result['x'] = $excludedVersions;
        }
        if (($uri = $this->getUri()) !== '') {
            $result['u'] = $uri;
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
        $name = $value['n'] ?? null;
        if (!is_string($name) || $name === '') {
            throw new RuntimeException('Missing/invalid name key for ' . __CLASS__);
        }
        $minVersion = $value['m'] ?? '';
        if (!is_string($minVersion)) {
            throw new RuntimeException('Invalid minVersion key for ' . __CLASS__);
        }
        $maxVersion = $value['M'] ?? '';
        if (!is_string($maxVersion)) {
            throw new RuntimeException('Invalid maxVersion key for ' . __CLASS__);
        }
        $excludedVersions = $value['x'] ?? [];
        if (!is_array($excludedVersions)) {
            throw new RuntimeException('Invalid excludedVersions key for ' . __CLASS__);
        }
        foreach ($excludedVersions as $excludedVersion) {
            if (!is_string($excludedVersion) || $excludedVersion === '') {
                throw new RuntimeException('Invalid excludedVersion key for ' . __CLASS__);
            }
        }
        if (array_values(array_unique($excludedVersions)) !== $excludedVersions) {
            throw new RuntimeException('Invalid excludedVersion key for ' . __CLASS__);
        }
        $uri = $value['u'] ?? '';
        if (!is_string($uri)) {
            throw new RuntimeException('Invalid uri key for ' . __CLASS__);
        }

        return new static($name, $minVersion, $maxVersion, $excludedVersions, $uri);
    }
}
