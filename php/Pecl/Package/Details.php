<?php

declare(strict_types=1);

namespace PeclInfo\Pecl\Package;

class Details
{
    private string $packageName;

    private string $license;

    private string $summary;

    private string $description;

    public function __construct(string $packageName, string $license, string $summary, string $description)
    {
        $this->packageName = $packageName;
        $this->license = $license;
        $this->summary = $summary;
        $this->description = $description;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function getLicense(): string
    {
        return $this->license;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
