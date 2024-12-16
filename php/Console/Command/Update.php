<?php

declare(strict_types=1);

namespace PeclInfo\Console\Command;

use Closure;
use InvalidArgumentException;
use PeclInfo\Pecl\Package\Details as PackageDetails;
use PeclInfo\Pecl\Package\Details\Fetcher as PackageDetailsFetcher;
use PeclInfo\Pecl\Package\Lister as PackageLister;
use PeclInfo\Pecl\Package\Version;
use PeclInfo\Pecl\Package\Version\Details\Fetcher as PackageVersionDetailsFetcher;
use PeclInfo\Pecl\Package\Version\Lister as PackageVersionLister;
use PeclInfo\Summary;
use PeclInfo\Summary\Package;
use PeclInfo\Summary\Package\CompatibleConfigureOptions;
use PeclInfo\Summary\Package\CompatiblePHPVersions;
use PeclInfo\Summary\Package\CompatibleRequiredPackages;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends Command
{
    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update the data about the PECL packages')
            ->addOption('update-packages', 'p', InputOption::VALUE_NONE, 'Look for new packages')
            ->addOption('update-package-details', 'd', InputOption::VALUE_NONE, 'Look for updates of package details')
            ->addOption('update-versions', 'w', InputOption::VALUE_NONE, 'Look for new package versions')
            ->addArgument('packages', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, "Limit the operations to the specified packages (if not specified, we'll update all the packages)")
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $progressBarSuffix = getenv('CI') === 'true' ? "\n" : '';
        ProgressBar::setFormatDefinition('getting-package-details', 'Getting package details %bar% %current%/%max% [%remaining% remaining] %message%' . $progressBarSuffix);
        ProgressBar::setFormatDefinition('listing-versions', 'Listing versions %bar% %current%/%max% [%remaining% remaining] %message%' . $progressBarSuffix);
        ProgressBar::setFormatDefinition('analizyng-package-version-details', 'Inspecting versions %bar% %current%/%max% [%remaining% remaining] %message%' . $progressBarSuffix);
        $output->write('Reading package summary... ');
        $summary = $input->getArgument('packages') === [] ? new Summary() : $this->loadSummary();
        $output->writeln('done.');
        $output->write('Retrieving package list... ');
        $packageNames = $this->getPackageNames($input);
        $numPackages = count($packageNames);
        $output->writeln("done ({$numPackages} packages found).");
        $numPackageVersions = 0;
        $packagesDetails = [];
        $packagesVersions = [];
        if ($numPackages > 0) {
            $progress = new ProgressBar($output, $numPackages);
            $progress->setFormat('getting-package-details');
            $progress->setMessage('Initializing');
            $progress->start();
            $startTime = microtime(true);
            foreach ($packageNames as $packageName) {
                $progress->setMessage($packageName);
                $progress->advance();
                if ($input->getOption('update-package-details')) {
                    $progress->display();
                }
                $packagesDetails[$packageName] = $this->getPackageDetails($packageName, $input);
            }
            $progress->finish();
            $progress->clear();
            $output->writeln('Details of packages retrieved in ' . number_format(microtime(true) - $startTime, 2) . ' seconds.');
            $progress = new ProgressBar($output, $numPackages);
            $progress->setFormat('listing-versions');
            $progress->setMessage('Initializing');
            $progress->start();
            $startTime = microtime(true);
            foreach ($packageNames as $packageName) {
                $progress->setMessage($packageName);
                $progress->advance();
                if ($input->getOption('update-versions')) {
                    $progress->display();
                }
                $packageVersions = $this->getPackageVersions($packageName, $input);
                $numPackageVersions += count($packageVersions);
                $packagesVersions[$packageName] = $packageVersions;
            }
            $progress->finish();
            $progress->clear();
            $output->writeln("{$numPackageVersions} package versions found in " . number_format(microtime(true) - $startTime, 2) . ' seconds.');
            $progress = new ProgressBar($output, $numPackages + $numPackageVersions);
            $progress->setFormat('analizyng-package-version-details');
            $progress->setMessage('Initializing');
            $progress->start();
            $startTime = microtime(true);
            foreach ($packageNames as $packageName) {
                $progress->setMessage($packageName);
                $this->processPackageVersions(
                    $summary,
                    $packagesDetails[$packageName],
                    $packagesVersions[$packageName],
                    static function (?Version $packageVersion) use ($packageName, $progress) {
                        if ($packageVersion === null) {
                            $progress->setMessage($packageName);
                        } else {
                            $progress->setMessage("{$packageVersion->getPackageName()} v{$packageVersion->getVersion()}");
                        }
                        $progress->advance();
                    }
                );
            }
            $progress->finish();
            $progress->clear();
            $output->writeln('Package versions updated in ' . number_format(microtime(true) - $startTime, 2) . ' seconds.');
        }
        $output->write('Saving package summary... ');
        $this->saveSummary($summary);
        $output->writeln('done.');

        return Command::SUCCESS;
    }

    protected function getSummaryFilename(bool $generated, bool $minified): string
    {
        return ($generated ? DIR_DOCS : DIR_PUBLIC) . '/data/summary' . ($minified ? '.min.json' : '.json');
    }

    protected function loadSummary(): Summary
    {
        $filename = $this->getSummaryFilename(false, false);
        if (is_file($filename)) {
            $json = file_get_contents($filename);
            $data = json_decode($json, true, 2147483646, JSON_THROW_ON_ERROR);
            $summary = Summary::fromJSON($data);
        } else {
            $summary = new Summary();
        }

        return $summary;
    }

    protected function saveSummary(Summary $summary): void
    {
        $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;
        foreach ([false, true] as $generated) {
            foreach ([false, true] as $minify) {
                $filename = $this->getSummaryFilename($generated, $minify);
                $dirname = dirname($filename);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0777, true);
                }
                file_put_contents($filename, json_encode($summary, $jsonOptions | ($minify ? 0 : JSON_PRETTY_PRINT)));
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return string[]
     */
    protected function getPackageNames(InputInterface $input): array
    {
        $packageLister = new PackageLister();
        $forceReload = $input->getOption('update-packages');
        $availablePackageNames = $packageLister->getPackageNames($forceReload);
        $wantedPackageNames = $input->getArgument('packages');
        if ($wantedPackageNames === []) {
            return $availablePackageNames;
        }
        $result = [];
        foreach ($availablePackageNames as $availablePackageName) {
            foreach ($wantedPackageNames as $index => $wantedPackageName) {
                if (strcasecmp($wantedPackageName, $availablePackageName) === 0) {
                    $result[] = $availablePackageName;
                    unset($wantedPackageNames[$index]);
                    break;
                }
            }
        }
        if ($wantedPackageNames !== []) {
            throw new InvalidArgumentException('Unrecognized package names specified: ' . implode(', ', $wantedPackageNames));
        }

        return $result;
    }

    protected function getPackageDetails(string $packageName, InputInterface $input): PackageDetails
    {
        $packageDetailsFetcher = new PackageDetailsFetcher($packageName);
        $forceReload = $input->getOption('update-package-details');

        return $packageDetailsFetcher->getDetails($forceReload);
    }

    /**
     * @return \PeclInfo\Pecl\Package\Version[]
     */
    protected function getPackageVersions(string $packageName, InputInterface $input): array
    {
        $packageVersionsLister = new PackageVersionLister($packageName);
        $forceReload = $input->getOption('update-versions');

        return $packageVersionsLister->getPackageVersions($forceReload);
    }

    /**
     * @param \PeclInfo\Pecl\Package\Version[] $packageVersions
     */
    protected function processPackageVersions(Summary $summary, PackageDetails $packageDetails, array $packageVersions, Closure $callback): void
    {
        $package = new Package($packageDetails);
        $callback(null);
        $currentCompatiblePHPVersions = null;
        $currentCompatibleConfigureOptions = null;
        $currentCompatibleRequiredPackages = null;
        foreach ($packageVersions as $packageVersion) {
            $callback($packageVersion);
            $detailsFetcher = new PackageVersionDetailsFetcher($packageVersion);
            $details = $detailsFetcher->getDetails();
            if ($currentCompatiblePHPVersions === null || !$currentCompatiblePHPVersions->isCompatibleWith($details)) {
                $currentCompatiblePHPVersions = new CompatiblePHPVersions($details->getMinPHPVersion(), $details->getMaxPHPVersion());
                $package->addCompatiblePHPVersions($currentCompatiblePHPVersions);
            }
            $currentCompatiblePHPVersions->addVersion($packageVersion->getVersion());
            if ($currentCompatibleConfigureOptions === null || !$currentCompatibleConfigureOptions->isCompatibleWith($details)) {
                $currentCompatibleConfigureOptions = new CompatibleConfigureOptions($details->getConfigureOptions());
                $package->addCompatibleConfigureOptions($currentCompatibleConfigureOptions);
            }
            $currentCompatibleConfigureOptions->addVersion($packageVersion->getVersion());
            if ($currentCompatibleRequiredPackages === null || !$currentCompatibleRequiredPackages->isCompatibleWith($details)) {
                $currentCompatibleRequiredPackages = new CompatibleRequiredPackages($details->getRequiredPackages());
                $package->addCompatibleRequiredPackages($currentCompatibleRequiredPackages);
            }
            $currentCompatibleRequiredPackages->addVersion($packageVersion->getVersion());
        }
        $summary->setPackage($package);
    }
}
