<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches\Command;

use Composer\Composer;
use Magento\SetPatches\Data\Patch;
use Magento\SetPatches\Filesystem\FilesystemDriver;
use Magento\SetPatches\Instance\InstanceProvider;
use Magento\SetPatches\Patch\Action\RevertAction;
use Magento\SetPatches\Patch\JsonStorage;
use Magento\SetPatches\Shell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;

class CheckMerged extends Command
{
    const NAME = 'check-merged';

    const OPTION_RELEASE_LINE = 'release-line';
    const OPTION_SOURCE_PACKAGE = 'source-package';
    const OPTION_PACKAGE_VERSION = 'package-version';

    /**
     * @var Shell
     */
    private $shell;

    /**
     * @var InstanceProvider
     */
    private $instanceProvider;

    /**
     * @var RevertAction
     */
    private $revertAction;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var JsonStorage
     */
    private $jsonStorage;

    /**
     * @var FilesystemDriver
     */
    private $filesystemDriver;

    /**
     * @param InstanceProvider $instanceProvider
     * @param RevertAction $revertAction
     * @param JsonStorage $jsonStorage
     * @param FilesystemDriver $filesystemDriver
     * @param Shell $shell
     * @param Composer $composer
     * @param null $name
     */
    public function __construct(
        InstanceProvider $instanceProvider,
        RevertAction $revertAction,
        JsonStorage $jsonStorage,
        FilesystemDriver $filesystemDriver,
        Shell $shell,
        Composer $composer,
        $name = null
    ) {
        parent::__construct($name);
        $this->shell = $shell;
        $this->instanceProvider = $instanceProvider;
        $this->revertAction = $revertAction;
        $this->composer = $composer;
        $this->jsonStorage = $jsonStorage;
        $this->filesystemDriver = $filesystemDriver;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Check')
            ->addOption(
                self::OPTION_RELEASE_LINE,
                null,
                InputOption::VALUE_REQUIRED,
                'Release line'
            )->addOption(
                self::OPTION_SOURCE_PACKAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Source package'
            )->addOption(
                self::OPTION_PACKAGE_VERSION,
                null,
                InputOption::VALUE_REQUIRED,
                'Package version'
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $package = $this->composer->getRepositoryManager()->findPackage(
            $input->getOption(self::OPTION_SOURCE_PACKAGE),
            $input->getOption(self::OPTION_PACKAGE_VERSION)
        );
        $patches = $this->jsonStorage->get($package);

        $instances = $this->instanceProvider->getByReleaseLine($input->getOption(self::OPTION_RELEASE_LINE));

        foreach ($instances as $instance) {
            /** @var Patch $patch */
            foreach ($patches as $patch) {
                $patch->setAction(Patch::ACTION_CHECK_REVERT);
                try {
                    $this->revertAction->execute($patch, $instance);
                    $message = sprintf('Patch %s has been merged to %s', $patch->getName(), $instance->getPath());
                } catch (\Exception $exception) {
                    $message = sprintf(
                        'Patch %s has not been merged to %s',
                        $patch->getName(),
                        $instance->getPath()
                    );
                }

                $output->writeln($message);
            }

            $this->filesystemDriver->deleteDirectory($instance->getPath());
        }
    }
}
