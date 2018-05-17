<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches\Command;

use Magento\SetPatches\Patch\Action\RevertAction;
use Magento\SetPatches\Patch\LockStorageFactory;
use Symfony\Component\Console\Command\Command;
use Composer\Composer;
use Magento\SetPatches\Instance\InstanceProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Remove extends Command
{
    const NAME = 'remove';

    const OPTION_PACKAGE_NAME = 'package-name';

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var InstanceProvider
     */
    private $instanceProvider;

    /**
     * @var LockStorageFactory
     */
    private $lockStorageFactory;

    /**
     * @var RevertAction
     */
    private $revertAction;

    /**
     * @param Composer $composer
     * @param InstanceProvider $instanceProvider
     * @param LockStorageFactory $lockStorageFactory
     * @param RevertAction $revertAction
     */
    public function __construct(
        Composer $composer,
        InstanceProvider $instanceProvider,
        LockStorageFactory $lockStorageFactory,
        RevertAction $revertAction
    ) {
        $this->composer = $composer;
        $this->instanceProvider = $instanceProvider;
        $this->lockStorageFactory = $lockStorageFactory;
        $this->revertAction = $revertAction;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Revert all patches')
            ->addOption(
                self::OPTION_PACKAGE_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Package name'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc
     *
     * @throws \RuntimeException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Reverting patches');

        $package = $this->composer->getRepositoryManager()->findPackage(
            $input->getOption(self::OPTION_PACKAGE_NAME),
            $this->getPackageVersion( $input->getOption(self::OPTION_PACKAGE_NAME))
        );

        $instance = $this->instanceProvider->getRootInstance();

        $patches = $this->lockStorageFactory->create($instance)->get($package);
        foreach (array_reverse($patches) as $patch) {
            $this->revertAction->execute($patch, $instance);
            $output->writeln(sprintf('Patch  %s has been reverted.', $patch->getName()));
        }
        $output->writeln(sprintf('All patches has been reverted.'));
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
