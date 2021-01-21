<?php

declare(strict_types=1);

namespace PeclInfo\Pecl\Package\Details;

use DOMDocument;
use DOMXPath;
use PeclInfo\Pecl\Package\Details;
use RuntimeException;

class Fetcher
{
    protected const PACKAGEINFO_URL = 'https://pecl.php.net/rest/p/%s/info.xml';

    private string $packageName;

    private ?Details $details = null;

    public function __construct(string $packageName)
    {
        $this->packageName = $packageName;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function getDetails(bool $forceReload = false): Details
    {
        if ($this->details === null || $forceReload === true) {
            [$xml, $saveXML] = $this->getXML($forceReload);
            $this->details = $this->parseXML($xml);
            if ($saveXML) {
                $filename = $this->getXMLFileName();
                $dirname = dirname($filename);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0777, true);
                }
                file_put_contents($filename, $xml);
            }
        }

        return $this->details;
    }

    protected function getXMLFileName(): string
    {
        return DIR_XML . '/' . $this->getPackageName() . '-details.xml';
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
        $url = $this->getDownloadURL();

        return file_get_contents($url);
    }

    /**
     * @throws \RuntimeException
     */
    protected function parseXML(string $xml): Details
    {
        $dom = new DOMDocument();
        if (!$dom->loadXML($xml)) {
            throw new RuntimeException('Failed to parse the XML of package details.');
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ns', 'http://pear.php.net/dtd/rest.package');
        $licenseNodes = $xpath->query('/ns:p/ns:l');
        $summaryNodes = $xpath->query('/ns:p/ns:s');
        $descriptionNodes = $xpath->query('/ns:p/ns:d');
        if ($licenseNodes->count() !== 1 || $summaryNodes->count() !== 1 || $descriptionNodes->count() !== 1) {
            throw new RuntimeException('Unsupported namespace of the XML of package details');
        }

        return new Details(
            $this->getPackageName(),
            $this->trim($licenseNodes[0]->nodeValue),
            $this->trim($summaryNodes[0]->nodeValue),
            $this->trim($descriptionNodes[0]->nodeValue),
        );
    }

    protected function getDownloadURL(): string
    {
        return sprintf(self::PACKAGEINFO_URL, strtolower($this->getPackageName()));
    }

    protected function trim(string $value): string
    {
        $value = trim($value);
        $value = str_replace("\r", "\n", str_replace("\r\n", "\n", trim($value)));
        $value = preg_replace('/[ \t]+/', ' ', $value);
        $value = preg_replace('/ *\n */', "\n", $value);

        return $value;
    }
}
