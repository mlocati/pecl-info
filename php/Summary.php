<?php

declare(strict_types=1);

namespace PeclInfo;

use JsonSerializable;
use PeclInfo\Summary\Package;
use RuntimeException;

class Summary implements JsonSerializable
{
    /**
     * @var \PeclInfo\Summary\Package[]
     */
    private $packages = [];

    /**
     * @return \PeclInfo\Summary\Package[]
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

    /**
     * @return $this
     */
    public function setPackage(Package $value): self
    {
        $existingIndex = null;
        foreach ($this->packages as $index => $package) {
            if (strcasecmp($package->getDetails()->getPackageName(), $value->getDetails()->getPackageName()) === 0) {
                $existingIndex = $index;
                break;
            }
        }
        if ($existingIndex === null) {
            $this->packages[] = $value;
            usort(
                $this->packages,
                static function (Package $a, Package $b): int {
                    return strnatcasecmp($a->getDetails()->getPackageName(), $b->getDetails()->getPackageName());
                }
            );
        } else {
            $this->packages[$existingIndex] = $value;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(): array
    {
        return $this->getPackages();
    }

    /**
     * @throws \RuntimeException
     *
     * @return static
     */
    public static function fromJSON(array $value): self
    {
        $result = new static();
        foreach ($value as $item) {
            if (!is_array($item)) {
                throw new RuntimeException('Missing/invalid package key for ' . __CLASS__);
            }
            $result->setPackage(Package::fromJSON($item));
        }

        return $result;
    }
}
