<?php

declare(strict_types=1);
namespace PeclInfo\Summary;

use JsonSerializable;
use PeclInfo\Summary\Package\CompatibleConfigureOptions;
use PeclInfo\Summary\Package\CompatiblePHPVersions;
use RuntimeException;

class Package implements JsonSerializable
{
    private string $name;

    /**
     * @var \PeclInfo\Summary\Package\CompatiblePHPVersions[]
     */
    private $compatiblePHPVersions = [];

    /**
     * @var \PeclInfo\Summary\Package\CompatibleConfigureOptions[]
     */
    private $compatibleConfigureOptions = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return \PeclInfo\Summary\Package\CompatiblePHPVersions[]
     */
    public function getCompatiblePHPVersions(): array
    {
        return $this->compatiblePHPVersions;
    }

    /**
     * @return $this
     */
    public function addCompatiblePHPVersions(CompatiblePHPVersions $value): self
    {
        $this->compatiblePHPVersions[] = $value;

        return $this;
    }

    /**
     * @return \PeclInfo\Summary\Package\CompatibleConfigureOptions[]
     */
    public function getCompatibleConfigureOptions(): array
    {
        return $this->compatibleConfigureOptions;
    }

    /**
     * @return $this
     */
    public function addCompatibleConfigureOptions(CompatibleConfigureOptions $value): self
    {
        $this->compatibleConfigureOptions[] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        $result = [
            'name' => $this->getName(),
        ];
        $compatiblePHPVersions = $this->getCompatiblePHPVersions();
        if ($compatiblePHPVersions !== []) {
            $result['phpv'] = $compatiblePHPVersions;
        }
        $compatibleConfigureOptions = $this->getCompatibleConfigureOptions();
        if ($compatiblePHPVersions !== []) {
            $result['confopts'] = $compatibleConfigureOptions;
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
        $name = $value['name'] ?? null;
        if (!is_string($name) || $name === '') {
            throw new RuntimeException('Missing/invalid name key for ' . __CLASS__);
        }
        $result = new static($name);
        $list = $value['phpv'] ?? [];
        if (!is_array($list)) {
            throw new RuntimeException('Missing/invalid compatible PHP versions key for ' . __CLASS__);
        }
        foreach ($list as $item) {
            if (!is_array($item)) {
                throw new RuntimeException('Missing/invalid compatible PHP versions key for ' . __CLASS__);
            }
            $result->addCompatiblePHPVersions(CompatiblePHPVersions::fromJSON($item));
        }
        $list = $value['confopts'] ?? [];
        if (!is_array($list)) {
            throw new RuntimeException('Missing/invalid compatible configure optipns key for ' . __CLASS__);
        }
        foreach ($list as $item) {
            if (!is_array($item)) {
                throw new RuntimeException('Missing/invalid compatible configure optipns key for ' . __CLASS__);
            }
            $result->addCompatibleConfigureOptions(CompatibleConfigureOptions::fromJSON($item));
        }

        return $result;
    }
}
