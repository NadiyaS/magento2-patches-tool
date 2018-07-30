<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches\Command;

use Composer\Composer;
use Magento\SetPatches\Data\Patch;
use Magento\SetPatches\Data\Instance;
use Magento\SetPatches\Instance\InstanceProvider;
use Magento\SetPatches\Patch\Action\ActionInterface;
use Magento\SetPatches\Patch\Action\ApplyAction;
use Magento\SetPatches\Patch\Action\CouldNotApplyException;
use Magento\SetPatches\Patch\Action\CouldNotRevertException;
use Magento\SetPatches\Patch\Action\RevertAction;
use Magento\SetPatches\Patch\PatchesProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class Apply extends Command
{
    const NAME = 'apply';

    const OPTION_APPLY_FROM = 'apply-from';
    const OPTION_INSTANCE_PATH = 'instance-path';
    const OPTION_INSTANCE_VERSION = 'instance-version';
    const OPTION_PACKAGE_NAME = 'package-name';
    const OPTION_PACKAGE_VERSION = 'package-version';

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var InstanceProvider
     */
    private $instanceProvider;

    /**
     * @var PatchesProvider
     */
    private $patchesProvider;

    /**
     * @var ActionInterface[]
     */
    private $actionPool;

    /**
     * @param Composer $composer
     * @param InstanceProvider $instanceProvider
     * @param PatchesProvider $patchesProvider
     * @param array $actionPool
     */
    public function __construct(
        Composer $composer,
        InstanceProvider $instanceProvider,
        PatchesProvider $patchesProvider,
        array $actionPool
    ) {
        $this->composer = $composer;
        $this->instanceProvider = $instanceProvider;
        $this->patchesProvider = $patchesProvider;
        $this->actionPool = $actionPool;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Applies patches')
            ->addOption(
                self::OPTION_APPLY_FROM,
                null,
                InputArgument::OPTIONAL,
                'Source of patches'
            )->addOption(
                self::OPTION_INSTANCE_PATH,
                null,
                InputArgument::OPTIONAL,
                'Apply patches to specific instance'
            )->addOption(
                self::OPTION_INSTANCE_VERSION,
                null,
                InputArgument::OPTIONAL,
                'Instance version'
            )->addOption(
                self::OPTION_PACKAGE_NAME,
                null,
                InputArgument::REQUIRED,
                'Package name'
            )->addOption(
                self::OPTION_PACKAGE_VERSION,
                null,
                InputArgument::REQUIRED,
                'Package version'
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
        $package = $this->composer->getRepositoryManager()->findPackage(
            $input->getOption(self::OPTION_PACKAGE_NAME),
            $input->getOption(self::OPTION_PACKAGE_VERSION)
        );

        $patchingEnabled = $package->getExtra()['enable-patching'] ?? false;

        if (!$patchingEnabled) {
            $output->writeln('Patching skipped.');

            return;
        }

        if ($input->getOption(Apply::OPTION_INSTANCE_PATH)) {
            $instance = $this->instanceProvider->getByPath($input->getOption(Apply::OPTION_INSTANCE_PATH));
        } elseif ($input->getOption(Apply::OPTION_INSTANCE_VERSION)) {
            $instance = $this->instanceProvider->getByVersion($input->getOption(Apply::OPTION_INSTANCE_VERSION));
        } else {
            $instance = $this->instanceProvider->getRootInstance();
        }

        $patches = $this->patchesProvider->get(
            $instance,
            $package,
            $input->getOption(Apply::OPTION_APPLY_FROM)
        );

        if ($patches) {
            $output->writeln('Patching started.');
        }

        /** @var Patch $patch */
        foreach ($patches as $patch) {

            if (isset($this->actionPool[$patch->getAction()])) {
                $action = $this->actionPool[$patch->getAction()];
                try {
                    $action->execute($patch, $instance);

                    if ($action instanceof ApplyAction) {
                        $output->writeln(sprintf('Patch %s has been applied.', $patch->getName()));
                    } elseif ($action instanceof RevertAction) {
                        $output->writeln(sprintf('Patch %s has been reverted.', $patch->getName()));
                    }
                } catch (CouldNotApplyException $exception) {
                    $output->writeln(sprintf('Patch %s could not be applied.', $patch->getName()));
                } catch (CouldNotRevertException $exception) {
                    $output->writeln(sprintf('Patch %s could not be reverted.', $patch->getName()));
                }
            } else {
                $output->writeln($patch->getAction() . ' action could not be found.');
            }
        }
        die("aaa");
        if ($patches) {
            $output->writeln('Patching finished.');
        }
    }
}
