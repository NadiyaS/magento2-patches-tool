<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches\Command;

use Composer\Composer;
use Magento\SetPatches\Data\Patch;
use Magento\SetPatches\Filesystem\FilesystemDriver;
use Magento\SetPatches\Patch\JsonStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FindRequirePatches extends Command
{
    const NAME = 'find-require-patches';
    const OPTION_PATCH_ID = 'patch-id';
    const OPTION_SOURCE_PACKAGE = 'source-package';

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
     * @param Composer $composer
     * @param JsonStorage $jsonStorage
     * @param FilesystemDriver $filesystemDriver
     */
    public function __construct(
        Composer $composer,
        JsonStorage $jsonStorage,
        FilesystemDriver $filesystemDriver
    ) {
        parent::__construct();
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
            ->setDescription('Find require patches for provided patch id')
            ->addOption(
                self::OPTION_PATCH_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'Patch id'
            )
            ->addOption(
                self::OPTION_SOURCE_PACKAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Source package'
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
            $this->getPackageVersion($input->getOption(self::OPTION_PACKAGE_VERSION))
        );
        $patches = $this->jsonStorage->get($package);
        $newPatchId = $input->getOption(self::OPTION_PATCH_ID);

        if (!isset($patches[$newPatchId])) {
            throw new \RuntimeException('Patch with id: ' . $newPatchId . ' is not found');
        }

        $newPatchFiles = $this->getFiles($patches[$newPatchId]);

        $requirePatches = [];
        /** @var Patch $patch */
        foreach ($patches as $patch) {
            if ($patch->getId() == $newPatchId) {
                continue;
            }
            $modifiedFiles = $this->getFiles($patch);
            foreach ($newPatchFiles as $file) {
                if (in_array($file, $modifiedFiles)) {
                    $requirePatches[] = $patch->getId();
                }
            }
        }
        if (!empty($requirePatches)) {
            $output->writeln('Found required patches for patch with id: ' . $newPatchId);
            $output->writeln('Required patches ids: ');
            $output->writeln(implode(', ', $requirePatches));
        } else {
            $output->writeln('Required patches not found');
        }
    }

    /**
     * @param Patch $patch
     * @return array
     *
     *
     * Move to StructureBuilder
     */
    private function getFiles(Patch $patch)
    {
        if ($this->filesystemDriver->isExists($patch->getAbsolutePath())) {
            preg_match_all(
                '/---\s(?<fileName>.*)\s/',
                $this->filesystemDriver->fileGetContents($patch->getAbsolutePath()),
                $matches
            );
        }
        return $matches['fileName'] ?? [];
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
