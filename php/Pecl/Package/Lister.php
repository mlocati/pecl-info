<?php

declare(strict_types=1);

namespace PeclInfo\Pecl\Package;

use DOMDocument;
use DOMXPath;
use RuntimeException;

class Lister
{
    protected const URL = 'https://pecl.php.net/rest/p/packages.xml';

    /**
     * @var string[]|null
     */
    private ?array $packageNames = null;

    /**
     * @throws \RuntimeException
     *
     * @return string[]
     */
    public function getPackageNames(bool $forceReload = false): array
    {
        if ($this->packageNames === null || $forceReload === true) {
            [$xml, $saveXML] = $this->getXML($forceReload);
            $this->packageNames = $this->parseXML($xml);
            if ($saveXML) {
                $filename = $this->getXMLFileName();
                $dirname = dirname($filename);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0777, true);
                }
                file_put_contents($filename, $xml);
            }
        }

        return $this->packageNames;
    }

    protected function getXMLFileName(): string
    {
        return DIR_XML . '/_packages.xml';
    }

    protected function getXML(bool $forceReload): array
    {
        $filename = $this->getXMLFileName();
        if ($forceReload || !is_file($filename)) {
            $xml = $this->fetchXML();
            $saveXML = true;
        } else {
            $xml = file_get_contents($filename);
            $saveXML = false;
        }

        return [$xml, $saveXML];
    }

    protected function fetchXML(): string
    {
        return file_get_contents(static::URL);
    }

    /**
     * @throws \RuntimeException
     *
     * @return string[]
     */
    protected function parseXML(string $xml): array
    {
        $dom = new DOMDocument();
        if (!$dom->loadXML($xml)) {
            throw new RuntimeException('Failed to parse the XML list of packages.');
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('n', 'http://pear.php.net/dtd/rest.allpackages');
        $nodes = $xpath->query('/n:a/n:p');
        if ($nodes->count() === 0) {
            throw new RuntimeException('No packages found in the XML list of packages.');
        }
        $packageNames = [];
        foreach ($nodes as $node) {
            $packageNames[] = $node->nodeValue;
        }
        usort($packageNames, 'strnatcasecmp');

        return $packageNames;
    }
}
