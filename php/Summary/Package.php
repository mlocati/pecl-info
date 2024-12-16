<?php

declare(strict_types=1);

namespace PeclInfo\Summary;

use JsonSerializable;
use PeclInfo\Pecl\Package\Details;
use PeclInfo\Summary\Package\CompatibleConfigureOptions;
use PeclInfo\Summary\Package\CompatiblePHPVersions;
use PeclInfo\Summary\Package\CompatibleRequiredPackages;
use RuntimeException;

class Package implements JsonSerializable
{
    private Details $details;

    /**
     * @var \PeclInfo\Summary\Package\CompatiblePHPVersions[]
     */
    private $compatiblePHPVersions = [];

    /**
     * @var \PeclInfo\Summary\Package\CompatibleConfigureOptions[]
     */
    private $compatibleConfigureOptions = [];

    /**
     * @var \PeclInfo\Summary\Package\CompatibleRequiredPackages[]
     */
    private $compatibleRequiredPackages = [];

    public function __construct(Details $details)
    {
        $this->details = $details;
    }

    public function getDetails(): Details
    {
        return $this->details;
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
     * @return \PeclInfo\Summary\Package\CompatibleRequiredPackages[]
     */
    public function getCompatibleRequiredPackages(): array
    {
        return $this->compatibleRequiredPackages;
    }

    /**
     * @return $this
     */
    public function addCompatibleRequiredPackages(CompatibleRequiredPackages $value): self
    {
        $this->compatibleRequiredPackages[] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->getDetails()->getPackageName(),
            'license' => $this->getDetails()->getLicense(),
            'summary' => $this->getDetails()->getSummary(),
            'description' => $this->getDetails()->getDescription(),
        ];
        $compatiblePHPVersions = $this->getCompatiblePHPVersions();
        if ($compatiblePHPVersions !== []) {
            $result['phpv'] = $compatiblePHPVersions;
        }
        $compatibleConfigureOptions = $this->getCompatibleConfigureOptions();
        if ($compatibleConfigureOptions !== []) {
            if (count($compatibleConfigureOptions) > 1 || $compatibleConfigureOptions[0]->getConfigureOptions() !== []) {
                $result['confopts'] = $compatibleConfigureOptions;
            }
        }
        $compatibleRequiredPackages = $this->getCompatibleRequiredPackages();
        if ($compatibleRequiredPackages !== []) {
            if (count($compatibleRequiredPackages) > 1 || $compatibleRequiredPackages[0]->getRequiredPackages() !== []) {
                $result['reqpkgs'] = $compatibleRequiredPackages;
            }
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
        $license = $value['license'] ?? null;
        if (!is_string($license) || $license === '') {
            throw new RuntimeException('Missing/invalid license key for ' . __CLASS__);
        }
        $summary = $value['summary'] ?? null;
        if (!is_string($summary) || $summary === '') {
            throw new RuntimeException('Missing/invalid summary key for ' . __CLASS__);
        }
        $description = $value['description'] ?? null;
        if (!is_string($description) || $description === '') {
            throw new RuntimeException('Missing/invalid description key for ' . __CLASS__);
        }
        $result = new static(new Details($name, $license, $summary, $description));
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
            throw new RuntimeException('Missing/invalid compatible configure options key for ' . __CLASS__);
        }
        foreach ($list as $item) {
            if (!is_array($item)) {
                throw new RuntimeException('Missing/invalid compatible configure options key for ' . __CLASS__);
            }
            $result->addCompatibleConfigureOptions(CompatibleConfigureOptions::fromJSON($item));
        }
        $list = $value['reqpkgs'] ?? [];
        if (!is_array($list)) {
            throw new RuntimeException('Missing/invalid compatible required packages key for ' . __CLASS__);
        }
        foreach ($list as $item) {
            if (!is_array($item)) {
                throw new RuntimeException('Missing/invalid compatible required packages key for ' . __CLASS__);
            }
            $result->addCompatibleRequiredPackages(CompatibleRequiredPackages::fromJSON($item));
        }

        return $result;
    }
}
