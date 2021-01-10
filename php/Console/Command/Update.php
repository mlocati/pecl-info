<?php

declare(strict_types=1);
namespace PeclInfo\Console\Command;

use Closure;
use InvalidArgumentException;
use PeclInfo\Pecl\Package\Lister as PackageLister;
use PeclInfo\Pecl\Package\Version;
use PeclInfo\Pecl\Package\Version\Details\Fetcher;
use PeclInfo\Pecl\Package\Version\Lister as PackageVersionLister;
use PeclInfo\Summary;
use PeclInfo\Summary\Package;
use PeclInfo\Summary\Package\CompatibleConfigureOptions;
use PeclInfo\Summary\Package\CompatiblePHPVersions;
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
        ProgressBar::setFormatDefinition('listing-versions', 'Listing versions %bar% %current%/%max% %message% [%remaining% remaining]');
        ProgressBar::setFormatDefinition('analizyng-packages', 'Inspecting versions %bar% %current%/%max% %message% [%remaining% remaining]');
        $output->write('Reading package summary... ');
        $summary = $input->getArgument('packages') === [] ? new Summary() : $this->loadSummary();
        $output->writeln('done.');
        $output->write('Retrieving package list... ');
        $packageNames = $this->getPackageNames($input);
        $numPackages = count($packageNames);
        $output->writeln("done ({$numPackages} packages found).");
        $numPackageVersions = 0;
        $packagesAndVersions = [];
        if ($numPackages > 0) {
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
                $packagesAndVersions[] = [$packageName, $packageVersions];
            }
            $progress->finish();
            $progress->clear();
            $output->writeln("{$numPackageVersions} package versions found in " . number_format(microtime(true) - $startTime, 2) . ' seconds.');
        }
        if ($numPackageVersions > 0) {
            $progress = new ProgressBar($output, $numPackageVersions);
            $progress->setFormat('analizyng-packages');
            $progress->setMessage('Initializing');
            $progress->start();
            $startTime = microtime(true);
            foreach ($packagesAndVersions as [$packageName, $packageVersions]) {
                $progress->setMessage($packageName);
                $this->processPackageVersions(
                    $summary,
                    $packageName,
                    $packageVersions,
                    static function (Version $packageVersion) use ($progress) {
                        $progress->setMessage("{$packageVersion->getPackageName()} v{$packageVersion->getVersion()}");
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

    protected function getSummaryFilename(bool $minified): string
    {
        return DIR_WEB . '/summary' . ($minified ? '.min.json' : '.json');
    }

    protected function loadSummary(): Summary
    {
        $filename = $this->getSummaryFilename(false);
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
        foreach ([false, true] as $minify) {
            $filename = $this->getSummaryFilename($minify);
            $dirname = dirname($filename);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0777, true);
            }
            file_put_contents($filename, json_encode($summary, $jsonOptions | ($minify ? 0 : JSON_PRETTY_PRINT)));
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
    protected function processPackageVersions(Summary $summary, string $packageName, array $packageVersions, Closure $callback): void
    {
        $package = new Package($packageName);
        $currentCompatiblePHPVersions = null;
        $currentCompatibleConfigureOptions = null;
        foreach ($packageVersions as $packageVersion) {
            $callback($packageVersion);
            $detailsFetcher = new Fetcher($packageVersion);
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
        }
        $summary->setPackage($package);
    }
}
