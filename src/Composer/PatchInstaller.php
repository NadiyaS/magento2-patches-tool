<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use Magento\SetPatches\Application;
use Magento\SetPatches\Command\Apply;
use Magento\SetPatches\Command\Remove;
use Magento\SetPatches\Command\BackupPatch;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class PatchInstaller extends LibraryInstaller
{
    /**
     * package type for magento2 patches
     */
    const MAGENTO2_PATCHES_TYPE = 'magento2-patches';

    /**
     * @var Application
     */
    private $application;

    /**
     * @param bool $force
     * @return Application
     */
    private function getApplication($force = false)
    {
        if (!$this->application || $force) {
            $this->application = new \Magento\SetPatches\Application();
        }
        return $this->application;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return self::MAGENTO2_PATCHES_TYPE === $packageType;
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $initial
     * @param PackageInterface $target
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $output = new ConsoleOutput();
        $backupCommand = $this->getApplication()->find(BackupPatch::NAME);
        $backupCommand->run(
            new ArrayInput([
                '--' . Apply::OPTION_PACKAGE_NAME => $target->getName()
            ]),
            $output
        );

        parent::update($repo, $initial, $target);

        $applyCommand = $this->getApplication(true)->find(Apply::NAME);
        $applyCommand->run(
            new ArrayInput([
                '--' . Apply::OPTION_APPLY_FROM => 'json',
                '--' . Apply::OPTION_PACKAGE_NAME => $target->getName(),
                '--' . Apply::OPTION_PACKAGE_VERSION => $target->getVersion()
            ]),
            $output
        );
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $input = new ArrayInput([
            '--' . Apply::OPTION_PACKAGE_NAME => $package->getName(),
            '--' . Apply::OPTION_PACKAGE_VERSION => $package->getVersion()
        ]);
        $output = new ConsoleOutput();

        parent::install($repo, $package);

        $backupCommand = $this->getApplication()->find(BackupPatch::NAME);
        $backupCommand->run($input, $output);

        $applyCommand = $this->getApplication()->find(Apply::NAME);
        $applyCommand->run($input, $output);
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $input = new ArrayInput([
            '--' . Apply::OPTION_PACKAGE_NAME => $package->getName(),
            '--' . Apply::OPTION_PACKAGE_VERSION => $package->getVersion()
        ]);
        $output = new ConsoleOutput();

        $backupCommand = $this->getApplication()->find(BackupPatch::NAME);
        $backupCommand->run($input, $output);

        parent::uninstall($repo, $package);

        $removeCommand = $this->getApplication(true)->find(Remove::NAME);
        $removeCommand->run(
            new ArrayInput([
                '--' . Apply::OPTION_PACKAGE_NAME => $package->getName()
            ]),
            $output
        );
    }
}
