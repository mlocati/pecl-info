<?php

declare(strict_types=1);
namespace PeclInfo\Pecl\Package\Version\Details;

use DOMDocument;
use DOMXPath;
use PeclInfo\Pecl\Package\Version;
use PeclInfo\Pecl\Package\Version\ConfigureOption;
use PeclInfo\Pecl\Package\Version\Details;
use Phar;
use PharData;
use PharException;
use RuntimeException;

class Fetcher
{
    protected const VERSIONINFO_URL = 'https://pecl.php.net/rest/r/%1$s/%2$s.xml';

    private Version $packageVersion;

    private ?Details $details = null;

    public function __construct(Version $packageVersion)
    {
        $this->packageVersion = $packageVersion;
    }

    public function getPackageVersion(): Version
    {
        return $this->packageVersion;
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
        return DIR_XML . '/' . $this->getPackageVersion()->getPackageName() . '/' . $this->getPackageVersion()->getVersion() . '.xml';
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
        $tgzFile = tempnam(sys_get_temp_dir(), 'dpx');
        try {
            file_put_contents($tgzFile, file_get_contents($url));
            $archive = new PharData($tgzFile);
            try {
                $archive->decompress('tar');
                $tarFile = preg_replace('/\.\w+$/', '.tar', $tgzFile);
                try {
                    $extractedDir = preg_replace('/\.tar$/', '.decompressed', $tarFile);
                    mkdir($extractedDir);
                    try {
                        try {
                            $archive->extractTo($extractedDir, 'package2.xml');
                            rename("{$extractedDir}/package2.xml", "{$extractedDir}/package.xml");
                        } catch (PharException $x) {
                            $archive->extractTo($extractedDir, 'package.xml');
                        }
                        $xml = file_get_contents("{$extractedDir}/package.xml");
                        unlink("{$extractedDir}/package.xml");

                        return $xml;
                    } finally {
                        rmdir($extractedDir);
                    }
                } finally {
                    unlink($tarFile);
                }
            } finally {
                unset($archive);
                Phar::unlinkArchive($tgzFile);
            }
        } finally {
            if (is_file($tgzFile)) {
                unlink($tgzFile);
            }
        }
    }

    /**
     * @throws \RuntimeException
     */
    protected function parseXML(string $xml): Details
    {
        $ignoreDOMWarnings = $this->shouldIgnoreDOMWarnings();
        $dom = new DOMDocument();
        if ($ignoreDOMWarnings) {
            set_error_handler(static function () {}, -1);
        }
        $loaded = false;
        try {
            $loaded = $dom->loadXML($xml);
        } finally {
            if ($ignoreDOMWarnings) {
                restore_error_handler();
            }
        }
        if (!$loaded) {
            throw new RuntimeException('Failed to parse the XML of package version details.');
        }
        $xpath = new DOMXPath($dom);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('v20', 'http://pear.php.net/dtd/package-2.0');
        $xpath->registerNamespace('v21', 'http://pear.php.net/dtd/package-2.1');
        if ($xpath->query('/v20:package/v20:dependencies')->count() === 1) {
            $ns = 'v20:';
        } elseif ($xpath->query('/v21:package/v21:dependencies')->count() === 1) {
            $ns = 'v21:';
        } elseif ($xpath->query('/package')->count() === 1) {
            $ns = '';
        } else {
            throw new RuntimeException('Unsupported namespace of the XML of package version details');
        }
        $minPHPVersionNodes = $xpath->query("/{$ns}package/{$ns}dependencies/{$ns}required/{$ns}php/{$ns}min");
        $maxPHPVersionNodes = $xpath->query("/{$ns}package/{$ns}dependencies/{$ns}required/{$ns}php/{$ns}max");
        $details = new Details(
            $this->getPackageVersion(),
            $minPHPVersionNodes->count() === 0 ? '' : $minPHPVersionNodes[0]->nodeValue,
            $maxPHPVersionNodes->count() === 0 ? '' : $maxPHPVersionNodes[0]->nodeValue
        );
        foreach ($xpath->query("/{$ns}package/{$ns}extsrcrelease/{$ns}configureoption") as $configureOptionNode) {
            $details->addConfigureOption(new ConfigureOption(
                $configureOptionNode->getAttribute('name'),
                $configureOptionNode->getAttribute('prompt'),
                $configureOptionNode->hasAttribute('default') ? $configureOptionNode->getAttribute('default') : null
            ));
        }

        return $details;
    }

    protected function shouldIgnoreDOMWarnings(): bool
    {
        switch ($this->getPackageVersion()->getPackageName() . '-' . $this->getPackageVersion()->getVersion()) {
            case 'APC-3.0.9': // DOMDocument::loadXML(): xmlns:xsi: 'http://www.w3.org/2001/XMLSchema-inst ance' is not a valid URI in Entity, line: 2
            case 'APC-3.0.10': // DOMDocument::loadXML(): xmlns:xsi: 'http://www.w3.org/2001/XMLSchema-inst ance' is not a valid URI in Entity, line: 2
            case 'APC-3.0.11': // DOMDocument::loadXML(): xmlns:xsi: 'http://www.w3.org/2001/XMLSchema-inst ance' is not a valid URI in Entity, line: 2
            case 'APC-3.0.12': // DOMDocument::loadXML(): xmlns:xsi: 'http://www.w3.org/2001/XMLSchema-inst ance' is not a valid URI in Entity, line: 2
            case 'APC-3.0.12p1': // DOMDocument::loadXML(): xmlns:xsi: 'http://www.w3.org/2001/XMLSchema-inst ance' is not a valid URI in Entity, line: 2
            case 'APC-3.0.12p2': // DOMDocument::loadXML(): xmlns:xsi: 'http://www.w3.org/2001/XMLSchema-inst ance' is not a valid URI in Entity, line: 2
            case 'event-1.10.2': // DOMDocument::loadXML(): Input is not proper UTF-8, indicate encoding
            case 'trader-0.2': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww  w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'trader-0.2.1': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww  w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'trader-0.2.2': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww  w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'trader-0.3.0': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww  w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-0.3': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-0.4': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-0.4.1': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-0.6': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-0.7': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-0.8': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-0.9': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-0.9.1': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-0.9.2': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-0.9.3': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'varnish-1.0.0': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww    w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
            case 'xmldiff-0.8.0': // DOMDocument::loadXML(): xmlns:xsi: 'http://ww  w.w3.org/2001/XMLSchema-instance' is not a valid URI in Entity, line: 2
                return true;
            default:
                return false;
        }
    }

    protected function getDownloadURL(): string
    {
        switch ($this->getPackageVersion()->getPackageName() . '-' . $this->getPackageVersion()->getVersion()) {
            // https://bugs.php.net/bug.php?id=80615
            case 'big_int-1.0.1':
            case 'bz2_filter-1.0':
            case 'couchbase-2.0.6':
            case 'cvsclient-0.1':
            case 'ds-1.2.7':
            case 'DTrace-1.0.0':
            case 'event-1.10.2':
            case 'hprose-1.0.0':
            case 'hprose-1.1.0':
            case 'hprose-1.2.0':
            case 'imlib2-0.1.00':
            case 'libsodium-0.1.2':
            case 'libsodium-2.0.14':
            case 'libsodium-2.0.15':
            case 'lua-2.0.0':
            case 'mailparse-0.9.3':
            case 'mailparse-0.9.4':
            case 'mustache-0.8.1':
            case 'Net_Gopher-0.1':
            case 'oggvorbis-0.1':
            case 'oggvorbis-0.2':
            case 'openal-0.1':
            case 'opendirectory-0.2.1':
            case 'opendirectory-0.2.2':
            case 'opendirectory-0.2.3':
            case 'opendirectory-0.2.4':
            case 'opendirectory-0.2.5':
            case 'opendirectory-0.2.6':
            case 'Paradox-1.0':
            case 'PDO_INFORMIX-0.1':
            case 'PECL_Gen-0.8.2':
            case 'pecl_http-2.4.2':
            case 'ps-1.1.0':
            case 'psr-0.6.1':
            case 'SCA_SDO-0.5.0':
            case 'SCA_SDO-0.5.1':
            case 'SCA_SDO-0.5.2':
            case 'SCA_SDO-0.6.1':
            case 'SCA_SDO-0.7.0':
            case 'SCA_SDO-0.7.1':
            case 'SCA_SDO-0.9.0':
            case 'SCA_SDO-1.0.0':
            case 'SCA_SDO-1.0.1':
            case 'SCA_SDO-1.0.2':
            case 'SCA_SDO-1.0.3':
            case 'SCA_SDO-1.0.4':
            case 'tidy-0.5.2':
            case 'tidy-0.5.3':
            case 'tidy-0.7':
            case 'tidy-1.0':
            case 'tidy-1.1':
            case 'win32ps-1.0.0':
            case 'win32ps_dll-1.0.0':
            case 'ZendOpcache-7.0.0':
            case 'zeroconf-0.1':
            case 'zeroconf-0.1.1':
            case 'zeroconf-0.1.2':
            case 'zlib_filter-1.0.1':
                $packageName = $this->getPackageVersion()->getPackageName();
                $map1 = [
                    'DTrace' => 'dtrace',
                    'Paradox' => 'paradox',
                ];
                if (isset($map1[$packageName])) {
                    $packageName = $map1[$packageName];
                }
                $packageAndVersion = "{$packageName}-{$this->getPackageVersion()->getVersion()}";
                $map2 = [
                ];
                if (isset($map2[$packageAndVersion])) {
                    $packageAndVersion = $map2[$packageAndVersion];
                }

                return "http://pecl.php.net/get/{$packageAndVersion}";
        }
        $url = sprintf(self::VERSIONINFO_URL, strtolower($this->getPackageVersion()->getPackageName()), $this->getPackageVersion()->getVersion());
        $xml = file_get_contents($url);
        $dom = new DOMDocument();
        if (!$dom->loadXML($xml)) {
            throw new RuntimeException('Failed to parse the XML of the version details.');
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('n', 'http://pear.php.net/dtd/rest.release');
        $nodes = $xpath->query('/n:r/n:g');
        if ($nodes->count() !== 1) {
            throw new RuntimeException('Invalid XML of the version details.');
        }

        return $nodes[0]->nodeValue;
    }
}
