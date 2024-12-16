<?php

declare(strict_types=1);

namespace PeclInfo\Pecl\Package\Version;

use DOMDocument;
use DOMXPath;
use PeclInfo\Pecl\Package\Version;
use PeclInfo\Pecl\Stability;
use PeclInfo\Pecl\VersionComparer;
use RuntimeException;

class Lister
{
    protected const URL = 'https://pecl.php.net/rest/r/%1$s/allreleases.xml';

    protected const NO_VERSIONS_XML = 'n/a';

    private string $packageName;

    /**
     * @var \PeclInfo\Pecl\Package\Version[]|null
     */
    private ?array $packageVersions = null;

    public function __construct(string $packageName)
    {
        $this->packageName = $packageName;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * @throws \RuntimeException
     *
     * @return \PeclInfo\Pecl\Package\Version[]
     */
    public function getPackageVersions(bool $forceReload = false): array
    {
        if ($this->packageVersions === null || $forceReload === true) {
            [$xml, $saveXML] = $this->getXML($forceReload);
            $this->packageVersions = $xml === null ? [] : $this->parseXML($xml);
            if ($saveXML) {
                $filename = $this->getXMLFileName();
                $dirname = dirname($filename);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0777, true);
                }
                file_put_contents($filename, $xml === null ? self::NO_VERSIONS_XML : $xml);
            }
        }

        return $this->packageVersions;
    }

    protected function getXMLFileName(): string
    {
        return DIR_XML . '/' . $this->getPackageName() . '-versions.xml';
    }

    protected function getXML(bool $forceReload): array
    {
        $filename = $this->getXMLFileName();
        if ($forceReload || !is_file($filename)) {
            $xml = $this->fetchXML();
            $saveXML = true;
        } else {
            $xml = file_get_contents($filename);
            if ($xml === self::NO_VERSIONS_XML) {
                $xml = null;
            }
            $saveXML = false;
        }

        return [$xml, $saveXML];
    }

    protected function fetchXML(): ?string
    {
        try {
            return file_get_contents(sprintf(static::URL, strtolower($this->getPackageName())));
        } catch (RuntimeException $x) {
            if (preg_match('/file_get_contents\b.*\bfailed to open stream: HTTP request failed! .* 404 Not Found/i', $x->getMessage())) {
                return null;
            }
            throw $x;
        }
    }

    /**
     * @throws \RuntimeException
     *
     * @return \PeclInfo\Pecl\Package\Version[]
     */
    protected function parseXML(string $xml): array
    {
        $dom = new DOMDocument();
        if (!$dom->loadXML($xml)) {
            throw new RuntimeException('Failed to parse the XML list of package versions.');
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('n', 'http://pear.php.net/dtd/rest.allreleases');
        $nodes = $xpath->query('/n:a/n:r');
        if ($nodes->count() === 0) {
            throw new RuntimeException('No packages found in the XML list of package versions.');
        }
        $packageVersions = [];
        foreach ($nodes as $node) {
            $versionNodes = $xpath->query('./n:v', $node);
            if ($versionNodes->count() !== 1) {
                throw new RuntimeException('Missing version in the XML node of a package version.');
            }
            $version = $versionNodes[0]->nodeValue;
            if ($this->shouldIgnoreVersion($version)) {
                continue;
            }
            $stabilityNodes = $xpath->query('./n:s', $node);
            if ($stabilityNodes->count() !== 1) {
                throw new RuntimeException('Missing stability in the XML node of a package version.');
            }
            $stability = $stabilityNodes[0]->nodeValue;
            if (!in_array($stability, [Stability::STABLE, Stability::BETA, Stability::ALPHA, Stability::DEVEL, Stability::SNAPSHOT], true)) {
                throw new RuntimeException("Unrecognized stability ({$stability}) stability in the XML node of a package version.");
            }
            $packageVersions[] = new Version($this->getPackageName(), $version, $stability);
        }
        usort($packageVersions, static function (Version $a, Version $b): int {
            return VersionComparer::compare($a->getVersion(), $b->getVersion());
        });

        return $packageVersions;
    }

    protected function shouldIgnoreVersion(string $version): bool
    {
        switch ("{$this->getPackageName()}-{$version}") {
            case 'operator-0.1': // file_get_contents(http://pecl.php.net/get/operator-0.1): failed to open stream: HTTP request failed! HTTP/1.0 404 Not Found
            case 'operator-0.2': // file_get_contents(http://pecl.php.net/get/operator-0.2): failed to open stream: HTTP request failed! HTTP/1.0 404 Not Found
            case 'operator-0.3': // file_get_contents(http://pecl.php.net/get/operator-0.3): failed to open stream: HTTP request failed! HTTP/1.0 404 Not Found
            case 'PDO_SQLANYWHERE-0.1.0': // file_get_contents(http://pecl.php.net/get/PDO_SQLANYWHERE-0.1.0): failed to open stream: HTTP request failed! HTTP/1.0 404 Not Found
            case 'win32ps-1.0.0': // empty package.xml file
            case 'win32ps_dll-1.0.0': // empty package.xml file
                return true;
        }

        return false;
    }
}
