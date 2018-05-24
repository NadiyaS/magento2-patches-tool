<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SetPatches\Patch;

use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\Package\PackageInterface;
use Magento\SetPatches\Data\Patch;

class JsonStorage
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var PatchStructureBuilder
     */
    private $patchStructureBuilder;

    /**
     * @param Composer $composer
     * @param PatchStructureBuilder $patchStructureBuilder
     */
    public function __construct(Composer $composer, PatchStructureBuilder $patchStructureBuilder)
    {
        $this->composer = $composer;
        $this->patchStructureBuilder = $patchStructureBuilder;
    }

    /**
     * @param PackageInterface $package
     * @return array
     */
    public function get(PackageInterface $package): array
    {
        return $this->patchStructureBuilder->build($this->loadPatches($package), $package);
    }

    /**
     * @param PackageInterface $package
     * @return array
     */
    private function loadPatches(PackageInterface $package)
    {
        $jsonFile = new JsonFile(BASE_DIR . '/composer.json');
        if ($jsonFile->exists()
            && isset($jsonFile->read()['extra']['patches'][$package->getName()])
        ) {
            return $jsonFile->read()['extra']['patches'][$package->getName()];
        }

        $installPath = $this->composer->getInstallationManager()->getInstallPath($package);
        $jsonFile = new JsonFile($installPath . '/composer.json');

        if ($jsonFile->exists()) {
            return $jsonFile->read()['extra']['patches'];
        }

        return [];
    }
}
