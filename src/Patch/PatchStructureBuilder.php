<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SetPatches\Patch;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Magento\SetPatches\Data\Patch;

class PatchStructureBuilder
{
    const AFTER = 'after';
    const DEPENDANTS = 'dependants';
    const REQUIRE = 'require';
    const PATCH_NAME = 'name';
    const ACTION = 'action';
    const STATUS = 'status';

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @param Composer $composer
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    /**
     * @param array $data
     * @param PackageInterface $package
     * @return array
     */
    public function build(array $data, PackageInterface $package): array
    {
        $patches = [];

        foreach ($data as $index => $patchData) {
            $patch = new Patch();
            $patch->setId($index);
            $patch->setName($patchData[self::PATCH_NAME]);
            $patch->setAbsolutePath($this->getPatchPath($patchData[self::PATCH_NAME], $package));
            $patch->setRequire($patchData[self::REQUIRE] ?? []);
            $patch->setAfter($patchData[self::AFTER] ?? null);
            $patch->setAction($patchData[self::ACTION] ?? null);
            $patch->setStatus($patchData[self::STATUS] ?? null);
            $patch->setPackage($package);

            $patches[$index] = $patch;
        }
        foreach ($patches as $patch) {
            $patch->setDependants(
                $this->getPatchDependants($patch->getId(), $patches) ?? null
            );
        }
        uasort($patches, [$this, 'sortPatches']);

        return $patches;
    }

    /**
     * @param $patchIndex
     * @param array $patches
     * @return array
     */
    private function getPatchDependants($patchIndex, array $patches): array
    {
        $dependants = [];
        /** @var Patch $patch */
        foreach ($patches as $index => $patch) {
            if ($patch->getAfter() !== null && $patch->getAfter() == $patchIndex) {
                $dependants[$index] = $patch;
                $dependants = array_replace($dependants, $this->getPatchDependants($index, $patches));
            }
        }
        return $dependants;
    }

    /**
     * @param Patch $patchOne
     * @param Patch $patchTwo
     * @return int
     */
    private function sortPatches(Patch $patchOne, Patch $patchTwo)
    {
        if ($patchOne->getId() == $patchTwo->getAfter()) {
            return 0;
        }
        return ($patchOne->getId() < $patchTwo->getAfter()) ? -1 : 1;
    }

    /**
     * @param $patchName
     * @param PackageInterface $package
     * @return bool|string
     */
    private function getPatchPath($patchName, PackageInterface $package)
    {
        return realpath($this->composer->getConfig()->get('vendor-dir')
            . '/../var/patches'
            . '/' . $package->getName()
            . '/' . $package->getExtra()['patch-dir']
            . '/' . $patchName
            . '.patch');
    }
}
