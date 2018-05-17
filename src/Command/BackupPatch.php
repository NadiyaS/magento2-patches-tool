<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches\Command;

use Composer\Composer;
use Composer\Json\JsonFile;
use Magento\SetPatches\Filesystem\FilesystemDriver;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class BackupPatch extends Command
{
    const NAME = 'backup-patches';
    const OPTION_PACKAGE_NAME = 'package-name';

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var FilesystemDriver
     */
    private $filesystemDriver;

    /**
     * @param Composer $composer
     * @param FilesystemDriver $filesystemDriver
     */
    public function __construct(Composer $composer, FilesystemDriver $filesystemDriver)
    {
        $this->composer = $composer;
        parent::__construct();
        $this->filesystemDriver = $filesystemDriver;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Backup patches')
            ->addOption(
                self::OPTION_PACKAGE_NAME,
                null,
                InputArgument::REQUIRED,
                'Package name'
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return  void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $package = $this->composer->getRepositoryManager()->findPackage(
            $input->getOption(self::OPTION_PACKAGE_NAME),
            $this->getPackageVersion($input->getOption(self::OPTION_PACKAGE_NAME))
        );

        $patchDir = $package->getExtra()['patch-dir'] ?? 'patches';
        $destination = $this->composer->getConfig()->get('vendor-dir')
            . '/../'
            . 'var/patches'
            . '/' . $package->getName()
            . '/' . $patchDir;

        if (!$this->filesystemDriver->isDirectory($destination)) {
            $this->filesystemDriver->createDirectory($destination);
        }

        $this->filesystemDriver->copyDirectory(
            $this->composer->getInstallationManager()->getInstallPath($package) . '/' . $patchDir,
            $destination
        );

    }

    /**
     * @param string $packageName
     * @return string
     */
    private function getPackageVersion(string $packageName): string
    {
        $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();

        foreach ($localRepo->getPackages() as $package) {
            if ($package->getName() === $packageName) {
                return $package->getVersion();
            }
        }
    }
}
